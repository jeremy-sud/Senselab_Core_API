<?php

namespace App\Services;

use App\Exceptions\FailedBookingAuditException;
use App\Exceptions\SeatHoldConflictException;
use App\Exceptions\TripChangeNotAllowedException;
use App\Models\AsientoBloqueoTemporal;
use App\Models\HorarioRuta;
use App\Models\TiqueteDetalle;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TransportSelfServiceTicketService
{
    /**
     * Mínimo de horas previas a la salida para permitir cambios de viaje.
     */
    public const DEFAULT_MIN_HOURS_BEFORE_DEPARTURE = 2;

    public function __construct(
        private readonly WebhookDispatcherService $webhookDispatcher
    ) {}

    /**
     * Procesa la solicitud de autoservicio 'Cambia tu viaje'.
     *
     * @param array{
     *     codigo_boleto: string,
     *     identificacion_pasajero: string,
     *     nuevo_horario_ruta_id: int,
     *     nuevo_asiento_numero: string,
     *     motivo?: string|null,
     *     horas_minimas_anticipacion?: int|null
     * } $data
     * @return array<string, mixed>
     *
     * @throws TripChangeNotAllowedException
     * @throws SeatHoldConflictException
     */
    public function changeTrip(array $data): array
    {
        $codigoBoleto = trim($data['codigo_boleto']);
        $identificacion = trim($data['identificacion_pasajero']);
        $cleanId = preg_replace('/[^0-9A-Za-z]/', '', $identificacion);
        $newTripId = (int) $data['nuevo_horario_ruta_id'];
        $newSeatNumber = (string) $data['nuevo_asiento_numero'];
        $motivo = $data['motivo'] ?? 'Cambio voluntario de viaje (Autoservicio TIG)';
        $minHours = (int) ($data['horas_minimas_anticipacion'] ?? self::DEFAULT_MIN_HOURS_BEFORE_DEPARTURE);

        // 1. Validar boleto actual y documento de identidad del pasajero
        $ticket = TiqueteDetalle::with(['horarioRuta.ruta', 'horarioRuta.bus'])
            ->where(function ($query) use ($codigoBoleto) {
                $query->where('codigo_boleto', $codigoBoleto)
                    ->orWhere('id', $codigoBoleto);
            })
            ->where(function ($query) use ($identificacion, $cleanId) {
                $query->where('identificacion_pasajero', $identificacion)
                    ->orWhereRaw("REPLACE(REPLACE(identificacion_pasajero, '-', ''), ' ', '') = ?", [$cleanId]);
            })
            ->where('eliminado', false)
            ->first();

        if (!$ticket) {
            throw new TripChangeNotAllowedException(
                "No se encontró ningún boleto activo para el código '{$codigoBoleto}' asociado a la identificación proporcionada.",
                404
            );
        }

        // 2. Validar estado del boleto
        if (in_array($ticket->estado, ['Cancelado', 'Usado', 'Cambiado'])) {
            throw new TripChangeNotAllowedException(
                "El boleto #{$codigoBoleto} se encuentra en estado '{$ticket->estado}' y no admite cambios de viaje.",
                422
            );
        }

        $horarioActual = $ticket->horarioRuta;
        if (!$horarioActual) {
            throw new TripChangeNotAllowedException("El boleto no tiene un horario de ruta válido asociado.", 422);
        }

        // 3. Verificar que falten más de X horas para la salida original
        $fechaSalidaStr = $horarioActual->fecha_salida ? $horarioActual->fecha_salida->format('Y-m-d') : now()->format('Y-m-d');
        $horaSalidaStr = $horarioActual->hora_salida ?: '00:00:00';
        $salidaOriginal = Carbon::parse("{$fechaSalidaStr} {$horaSalidaStr}");

        $horasRestantes = now()->diffInHours($salidaOriginal, false);

        if ($horasRestantes < $minHours) {
            throw new TripChangeNotAllowedException(
                "Política de autoservicio no superada: El viaje original sale en {$horasRestantes} hora(s). Se requiere un mínimo de {$minHours} horas de anticipación a la salida para autorizar cambios.",
                422
            );
        }

        // 4. Validar nuevo viaje seleccionado
        $newTrip = HorarioRuta::with(['ruta', 'bus'])->find($newTripId);
        if (!$newTrip) {
            throw new TripChangeNotAllowedException("El nuevo viaje seleccionado (ID {$newTripId}) no existe.", 404);
        }

        if ($newTrip->id === $horarioActual->id && (string) $newSeatNumber === (string) $ticket->asiento_numero) {
            throw new TripChangeNotAllowedException("El nuevo viaje y asiento seleccionados son idénticos a los actuales.", 422);
        }

        // 5. Transacción atómica: verificar disponibilidad del nuevo asiento, liberar el actual y emitir comprobante
        return DB::transaction(function () use (
            $ticket,
            $horarioActual,
            $newTrip,
            $newSeatNumber,
            $motivo,
            $salidaOriginal
        ) {
            // Verificar si el nuevo asiento ya está vendido
            $occupiedInNewTrip = TiqueteDetalle::where('horario_ruta_id', $newTrip->id)
                ->where('asiento_numero', $newSeatNumber)
                ->where('eliminado', false)
                ->where('estado', '!=', 'Cancelado')
                ->lockForUpdate()
                ->first();

            if ($occupiedInNewTrip) {
                throw new SeatHoldConflictException("El asiento #{$newSeatNumber} en el nuevo viaje ya ha sido adquirido por otro pasajero.", 409);
            }

            // Verificar si el nuevo asiento está en hold vigente por otro usuario
            $heldInNewTrip = AsientoBloqueoTemporal::where('horario_ruta_id', $newTrip->id)
                ->where('asiento_numero', $newSeatNumber)
                ->where('estado', 'bloqueado')
                ->where('expira_en', '>', now())
                ->lockForUpdate()
                ->first();

            if ($heldInNewTrip) {
                throw new SeatHoldConflictException("El asiento #{$newSeatNumber} en el nuevo viaje se encuentra retenido temporalmente por otro cliente.", 409);
            }

            // 6. Cálculo de diferencia tarifaria
            $precioAnterior = (float) $ticket->precio_final_tiquete;
            $precioNuevo = (float) ($newTrip->ruta?->precio_general ?? $newTrip->ruta?->precio ?? $precioAnterior);
            $diferencia = round($precioNuevo - $precioAnterior, 2);

            // 7. Generar nuevo código de boleto
            $nuevoCodigoBoleto = 'TIG-' . strtoupper(Str::random(8));

            // 8. Crear nuevo tiquete emitido para el nuevo viaje
            $nuevoTiquete = TiqueteDetalle::create([
                'empresa_id' => $newTrip->empresa_id ?? $ticket->empresa_id ?? 1,
                'detalle_venta_id' => $ticket->detalle_venta_id,
                'horario_ruta_id' => $newTrip->id,
                'parada_origen_id' => $ticket->parada_origen_id,
                'parada_destino_id' => $ticket->parada_destino_id,
                'asiento_numero' => $newSeatNumber,
                'codigo_boleto' => $nuevoCodigoBoleto,
                'pasarela_referencia' => $ticket->pasarela_referencia,
                'nombre_pasajero' => $ticket->nombre_pasajero,
                'identificacion_pasajero' => $ticket->identificacion_pasajero,
                'precio_final_tiquete' => $precioNuevo,
                'tipo_tarifa' => $ticket->tipo_tarifa ?? 'general',
                'estado' => 'Vendido',
                'estado_pago' => 'pagado',
                'motivo_cambio' => "Emitido en sustitución de boleto #{$ticket->codigo_boleto} (Asiento #{$ticket->asiento_numero})",
                'diferencia_tarifa_cambio' => $diferencia,
                'activo' => true,
                'eliminado' => false,
            ]);

            // 9. Actualizar y liberar boleto original
            $ticket->update([
                'estado' => 'Cambiado',
                'cambiado_a_tiquete_id' => $nuevoTiquete->id,
                'motivo_cambio' => $motivo,
                'diferencia_tarifa_cambio' => $diferencia,
            ]);

            // Si existía un bloqueo temporal para el tiquete original, liberarlo
            AsientoBloqueoTemporal::where('horario_ruta_id', $horarioActual->id)
                ->where('asiento_numero', $ticket->asiento_numero)
                ->where('estado', 'bloqueado')
                ->update(['estado' => 'liberado']);

            // 10. Estructurar comprobante de cambio y saldo / nota de crédito
            $comprobanteCambioId = 'COMP-CAMBIO-' . strtoupper(Str::random(10));
            $notaCredito = null;

            if ($diferencia < 0) {
                $montoFavor = abs($diferencia);
                $notaCredito = [
                    'numero_nota_credito' => 'NC-TIG-' . strtoupper(Str::random(8)),
                    'monto_saldo_favor' => $montoFavor,
                    'moneda' => 'CRC',
                    'vigencia_dias' => 90,
                    'beneficiario' => $ticket->nombre_pasajero,
                    'cedula' => $ticket->identificacion_pasajero,
                    'mensaje' => "Saldo a favor generado por cambio a tarifa menor. Canjeable en taquillas o próximo viaje."
                ];
            }

            Log::info("[Autoservicio TIG] Cambio de viaje completado exitosamente", [
                'boleto_original' => $ticket->codigo_boleto,
                'boleto_nuevo' => $nuevoCodigoBoleto,
                'viaje_anterior' => $horarioActual->id,
                'viaje_nuevo' => $newTrip->id,
                'diferencia' => $diferencia,
            ]);

            return [
                'success' => true,
                'mensaje' => 'Cambio de viaje procesado con éxito.',
                'comprobante_cambio' => [
                    'comprobante_id' => $comprobanteCambioId,
                    'fecha_cambio' => now()->toIso8601String(),
                    'motivo' => $motivo,
                    'tarifa_original' => $precioAnterior,
                    'tarifa_nueva' => $precioNuevo,
                    'diferencia_tarifa' => $diferencia,
                    'tipo_liquidacion' => $diferencia > 0 ? 'cobro_adicional' : ($diferencia < 0 ? 'saldo_a_favor' : 'sin_diferencia'),
                    'monto_adicional_a_pagar' => max(0, $diferencia),
                    'saldo_a_favor' => max(0, -$diferencia),
                    'nota_credito' => $notaCredito,
                ],
                'boleto_anterior' => [
                    'id' => $ticket->id,
                    'codigo_boleto' => $ticket->codigo_boleto ?? (string) $ticket->id,
                    'asiento_numero' => $ticket->asiento_numero,
                    'horario_ruta_id' => $horarioActual->id,
                    'salida_original' => $salidaOriginal->toIso8601String(),
                    'estado' => 'Cambiado (Asiento liberado)',
                ],
                'boleto_nuevo' => [
                    'id' => $nuevoTiquete->id,
                    'codigo_boleto' => $nuevoCodigoBoleto,
                    'asiento_numero' => $nuevoTiquete->asiento_numero,
                    'horario_ruta_id' => $newTrip->id,
                    'ruta' => ($newTrip->ruta?->origen ?? 'Origen') . ' - ' . ($newTrip->ruta?->destino ?? 'Destino'),
                    'fecha_salida' => $newTrip->fecha_salida?->format('Y-m-d'),
                    'hora_salida' => $newTrip->hora_salida,
                    'pasajero' => $nuevoTiquete->nombre_pasajero,
                    'identificacion' => $nuevoTiquete->identificacion_pasajero,
                    'precio_final' => $nuevoTiquete->precio_final_tiquete,
                    'estado' => $nuevoTiquete->estado,
                ],
            ];
        });
    }

    /**
     * Endpoint de auditoría rápida para 'Reserva fallida' o pagos desincronizados.
     *
     * @param array{
     *     referencia_pasarela: string,
     *     action?: 'audit'|'reissue'|'refund'|null,
     *     session_id?: string|null,
     *     horario_ruta_id?: int|null,
     *     asiento_numero?: string|null
     * } $data
     * @return array<string, mixed>
     *
     * @throws FailedBookingAuditException
     */
    public function verifyFailedBooking(array $data): array
    {
        $referencia = trim($data['referencia_pasarela']);
        $action = $data['action'] ?? 'audit';
        $sessionId = $data['session_id'] ?? null;
        $tripId = isset($data['horario_ruta_id']) ? (int) $data['horario_ruta_id'] : null;
        $seatNumber = isset($data['asiento_numero']) ? (string) $data['asiento_numero'] : null;

        // 1. Localizar registros asociados a la referencia en bloqueos temporales, tiquetes o ventas
        $bloqueo = AsientoBloqueoTemporal::with(['horarioRuta.ruta'])
            ->where(function ($q) use ($referencia) {
                $q->where('pasarela_referencia', $referencia)
                    ->orWhere('token_bloqueo', $referencia);
            })
            ->orWhere(function ($q) use ($sessionId) {
                if ($sessionId) {
                    $q->where('session_id', $sessionId);
                }
            })
            ->latest('id')
            ->first();

        $tiquete = TiqueteDetalle::with(['horarioRuta.ruta'])
            ->where(function ($q) use ($referencia) {
                $q->where('pasarela_referencia', $referencia)
                    ->orWhere('codigo_boleto', $referencia);
            })
            ->latest('id')
            ->first();

        $venta = Venta::where('clave_referencia_doc', $referencia)
            ->orWhere('numero_comprobante', $referencia)
            ->first();

        // 2. Determinar diagnóstico
        $estadoCobro = 'confirmado'; // En auditoría de banco/pasarela la referencia existe
        $estadoEmision = 'no_emitido';
        $diagnostico = '';
        $empresaId = $bloqueo?->empresa_id ?? $tiquete?->empresa_id ?? $venta?->empresa_id ?? 1;

        if ($tiquete && $tiquete->estado === 'Vendido') {
            $estadoEmision = 'emitido_correctamente';
            $diagnostico = "El boleto #{$tiquete->codigo_boleto} ya fue emitido y confirmado previamente para el asiento #{$tiquete->asiento_numero}.";
        } elseif ($tiquete && in_array($tiquete->estado, ['Pendiente', 'Fallido'])) {
            $estadoEmision = 'interrumpido';
            $diagnostico = "El cobro se registró pero la emisión del boleto quedó en estado '{$tiquete->estado}'.";
        } elseif ($bloqueo) {
            $estadoEmision = 'pendiente_de_emision';
            $diagnostico = "Se localizó el intento de reserva para el asiento #{$bloqueo->asiento_numero} (Viaje #{$bloqueo->horario_ruta_id}) sin boleto final generado.";
        } else {
            $diagnostico = "Se registró la referencia de pasarela bancaria. No se encontró boleto asociado previamente emitido.";
        }

        // 3. Ejecutar acción solicitada
        $resultadoAccion = null;

        if ($action === 'reissue') {
            // Reemitir el boleto
            $targetTripId = $tripId ?? $bloqueo?->horario_ruta_id ?? $tiquete?->horario_ruta_id;
            $targetSeat = $seatNumber ?? $bloqueo?->asiento_numero ?? $tiquete?->asiento_numero;

            if (!$targetTripId || !$targetSeat) {
                throw new FailedBookingAuditException("No es posible reemitir el boleto sin identificar el viaje y asiento solicitado.", 422);
            }

            $trip = HorarioRuta::with(['ruta'])->find($targetTripId);
            if (!$trip) {
                throw new FailedBookingAuditException("El viaje #{$targetTripId} no existe en el sistema.", 404);
            }

            // Verificar si el asiento original sigue libre
            $seatTaken = TiqueteDetalle::where('horario_ruta_id', $targetTripId)
                ->where('asiento_numero', $targetSeat)
                ->where('eliminado', false)
                ->where('estado', '!=', 'Cancelado')
                ->where('pasarela_referencia', '!=', $referencia)
                ->exists();

            if ($seatTaken) {
                // Asiento ya ocupado: notificar conflicto
                $resultadoAccion = [
                    'status' => 'conflicto_asiento_ocupado',
                    'mensaje' => "El asiento solicitado (#{$targetSeat}) fue tomado por otro usuario mientras se resolvía la transacción. Se recomienda activar el reembolso o seleccionar otro asiento disponible.",
                    'accion_sugerida' => 'refund'
                ];
            } else {
                // Reemitir con éxito
                $codigoBoleto = 'TIG-' . strtoupper(Str::random(8));
                $nuevoBoleto = TiqueteDetalle::updateOrCreate(
                    [
                        'pasarela_referencia' => $referencia,
                        'horario_ruta_id' => $targetTripId,
                        'asiento_numero' => $targetSeat,
                    ],
                    [
                        'empresa_id' => $empresaId,
                        'codigo_boleto' => $codigoBoleto,
                        'nombre_pasajero' => $bloqueo?->cliente?->nombre ?? 'Pasajero TIG (Auditoría)',
                        'identificacion_pasajero' => $bloqueo?->cliente?->identificacion ?? 'N/A',
                        'precio_final_tiquete' => $bloqueo?->monto_pago ?? $trip->ruta?->precio_general ?? 3500,
                        'estado' => 'Vendido',
                        'estado_pago' => 'pagado',
                        'motivo_cambio' => "Reemisión automática por resolución de reserva fallida (Ref: {$referencia})",
                        'activo' => true,
                        'eliminado' => false,
                    ]
                );

                if ($bloqueo) {
                    $bloqueo->update(['estado' => 'comprado']);
                }

                $resultadoAccion = [
                    'status' => 'reemitido_exitosamente',
                    'boleto_id' => $nuevoBoleto->id,
                    'codigo_boleto' => $codigoBoleto,
                    'asiento_numero' => $targetSeat,
                    'viaje_id' => $targetTripId,
                    'mensaje' => 'Boleto reemitido y confirmado con éxito.'
                ];
            }
        } elseif ($action === 'refund') {
            // Disparar Webhook de reembolso
            $payloadReembolso = [
                'evento' => 'transport.booking.refund_triggered',
                'referencia_pasarela' => $referencia,
                'fecha_solicitud' => now()->toIso8601String(),
                'monto' => $bloqueo?->monto_pago ?? $tiquete?->precio_final_tiquete ?? 0,
                'motivo' => 'Reserva fallida / Fallo de sincronización en emisión de boleto',
                'viaje_id' => $tripId ?? $bloqueo?->horario_ruta_id,
                'asiento_numero' => $seatNumber ?? $bloqueo?->asiento_numero,
            ];

            $this->webhookDispatcher->despachar('transport.booking.refund_triggered', $empresaId, $payloadReembolso);

            if ($bloqueo) {
                $bloqueo->update(['estado' => 'liberado']);
            }
            if ($tiquete) {
                $tiquete->update(['estado' => 'Cancelado', 'estado_pago' => 'reembolsado']);
            }

            $resultadoAccion = [
                'status' => 'reembolso_solicitado',
                'webhook_disparado' => true,
                'evento' => 'transport.booking.refund_triggered',
                'mensaje' => 'Se ha disparado el webhook de reembolso hacia la pasarela/banco correspondiente.'
            ];
        }

        return [
            'success' => true,
            'referencia_pasarela' => $referencia,
            'auditoria' => [
                'estado_cobro' => $estadoCobro,
                'estado_emision' => $estadoEmision,
                'diagnostico' => $diagnostico,
                'accion_ejecutada' => $action,
                'tiempo_auditoria' => now()->toIso8601String(),
            ],
            'datos_encontrados' => [
                'bloqueo_previo' => $bloqueo ? [
                    'id' => $bloqueo->id,
                    'token' => $bloqueo->token_bloqueo,
                    'viaje_id' => $bloqueo->horario_ruta_id,
                    'asiento' => $bloqueo->asiento_numero,
                    'estado' => $bloqueo->estado,
                ] : null,
                'tiquete_asociado' => $tiquete ? [
                    'id' => $tiquete->id,
                    'codigo_boleto' => $tiquete->codigo_boleto,
                    'asiento' => $tiquete->asiento_numero,
                    'estado' => $tiquete->estado,
                    'estado_pago' => $tiquete->estado_pago,
                ] : null,
                'venta_registrada' => $venta ? [
                    'id' => $venta->id,
                    'consecutivo' => $venta->numero_comprobante,
                    'monto' => $venta->monto_total_venta,
                    'estado' => $venta->estado_venta,
                ] : null,
            ],
            'resultado_accion' => $resultadoAccion,
        ];
    }
}
