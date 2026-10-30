<?php

namespace App\Services;

use App\Exceptions\SeatHoldConflictException;
use App\Models\AsientoBloqueoTemporal;
use App\Models\BusAsiento;
use App\Models\HorarioRuta;
use App\Models\TiqueteDetalle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransportSeatHoldingService
{
    /**
     * Holding time estándar en minutos (TIG / e-commerce de transporte)
     */
    public const HOLD_DURATION_MINUTES = 10;

    /**
     * Obtiene el mapa físico de asientos y disponibilidad en tiempo real para un viaje.
     *
     * @param int $tripId
     * @param string|null $currentSessionId
     * @return array<string, mixed>
     */
    public function getSeatMap(int $tripId, ?string $currentSessionId = null): array
    {
        $trip = HorarioRuta::with(['bus.plantilla.asientos', 'bus.asientos', 'ruta'])
            ->findOrFail($tripId);

        $bus = $trip->bus;
        $totalCapacidad = (int) ($bus->capacidad_asientos ?? 40);
        $totalPisos = (int) ($bus->cantidad_pisos ?? 1);

        // 1. Tiquetes confirmados/vendidos (No cancelados)
        $bookedSeats = TiqueteDetalle::where('horario_ruta_id', $tripId)
            ->where('eliminado', 0)
            ->where('estado', '!=', 'Cancelado')
            ->pluck('asiento_numero')
            ->map(fn ($num) => (string) $num)
            ->flip()
            ->all();

        // 2. Bloqueos temporales activos (holding time vigente)
        $activeHolds = AsientoBloqueoTemporal::where('horario_ruta_id', $tripId)
            ->where('estado', 'bloqueado')
            ->where('expira_en', '>', now())
            ->get()
            ->keyBy(fn ($item) => (string) $item->asiento_numero);

        // 3. Obtener celdas físicas del bus o plantilla asociada
        $seatsConfig = collect();
        if ($bus && $bus->asientos->isNotEmpty()) {
            $seatsConfig = $bus->asientos;
        } elseif ($bus && $bus->plantilla && $bus->plantilla->asientos->isNotEmpty()) {
            $seatsConfig = $bus->plantilla->asientos;
        }

        $seatList = [];
        $totalFilas = 10;
        $totalColumnas = 4;

        if ($seatsConfig->isNotEmpty()) {
            $totalFilas = $seatsConfig->max('fila') ?: 10;
            $totalColumnas = $seatsConfig->max('columna') ?: 4;

            foreach ($seatsConfig->sortBy(['piso', 'fila', 'columna']) as $celda) {
                $seatNumber = (string) ($celda->numero_asiento ?? '');
                $status = 'available';
                $lockedUntil = null;
                $remainingSeconds = 0;
                $isCurrentSession = false;

                if (!empty($seatNumber) && isset($bookedSeats[$seatNumber])) {
                    $status = 'booked';
                } elseif (!empty($seatNumber) && isset($activeHolds[$seatNumber])) {
                    $hold = $activeHolds[$seatNumber];
                    $status = 'locked';
                    $lockedUntil = $hold->expira_en?->toIso8601String();
                    $remainingSeconds = max(0, now()->diffInSeconds($hold->expira_en, false));
                    $isCurrentSession = ($currentSessionId && $hold->session_id === $currentSessionId);
                } elseif (!$celda->activo) {
                    $status = 'inactive';
                }

                $seatList[] = [
                    'seat_number' => $seatNumber ?: null,
                    'piso' => (int) $celda->piso,
                    'row' => (int) $celda->fila,
                    'column' => (int) $celda->columna,
                    'element_type' => $celda->tipo_elemento ?? 'asiento',
                    'class' => $celda->clase ?? 'estandar',
                    'location' => $celda->ubicacion ?? 'ventana',
                    'is_seat' => (bool) $celda->es_asiento,
                    'status' => $celda->es_asiento ? $status : 'available',
                    'locked_until' => $lockedUntil,
                    'remaining_seconds' => $remainingSeconds,
                    'is_current_session' => $isCurrentSession,
                ];
            }
        } else {
            // Fallback estándar 2+2 (4 asientos por fila con pasillo central)
            $totalFilas = (int) ceil($totalCapacidad / 4);
            $totalColumnas = 4;
            $currentSeat = 1;

            for ($f = 1; $f <= $totalFilas; $f++) {
                for ($c = 1; $c <= $totalColumnas; $c++) {
                    if ($currentSeat > $totalCapacidad) {
                        break;
                    }

                    $seatNumber = (string) $currentSeat;
                    $status = 'available';
                    $lockedUntil = null;
                    $remainingSeconds = 0;
                    $isCurrentSession = false;

                    if (isset($bookedSeats[$seatNumber])) {
                        $status = 'booked';
                    } elseif (isset($activeHolds[$seatNumber])) {
                        $hold = $activeHolds[$seatNumber];
                        $status = 'locked';
                        $lockedUntil = $hold->expira_en?->toIso8601String();
                        $remainingSeconds = max(0, now()->diffInSeconds($hold->expira_en, false));
                        $isCurrentSession = ($currentSessionId && $hold->session_id === $currentSessionId);
                    }

                    $seatList[] = [
                        'seat_number' => $seatNumber,
                        'piso' => 1,
                        'row' => $f,
                        'column' => $c,
                        'element_type' => 'asiento',
                        'class' => 'estandar',
                        'location' => in_array($c, [1, 4]) ? 'ventana' : 'pasillo',
                        'is_seat' => true,
                        'status' => $status,
                        'locked_until' => $lockedUntil,
                        'remaining_seconds' => $remainingSeconds,
                        'is_current_session' => $isCurrentSession,
                    ];

                    $currentSeat++;
                }
            }
        }

        $totalBooked = count($bookedSeats);
        $totalLocked = $activeHolds->count();
        $totalAvailable = max(0, $totalCapacidad - ($totalBooked + $totalLocked));

        return [
            'trip' => [
                'id' => $trip->id,
                'ruta_id' => $trip->ruta_id,
                'ruta_nombre' => $trip->ruta?->nombre ?? 'Ruta Express',
                'origen' => $trip->ruta?->origen ?? 'San José',
                'destino' => $trip->ruta?->destino ?? 'Guanacaste',
                'fecha_salida' => $trip->fecha_salida?->format('Y-m-d'),
                'hora_salida' => $trip->hora_salida,
                'precio_base' => (float) ($trip->ruta?->tarifa_base ?? 5000),
                'estado' => $trip->estado,
            ],
            'bus_configuration' => [
                'bus_id' => $bus?->id,
                'placa' => $bus?->placa ?? 'N/D',
                'total_decks' => $totalPisos,
                'total_rows' => $totalFilas,
                'total_columns' => $totalColumnas,
                'aisle_column' => 2,
                'capacity' => $totalCapacidad,
            ],
            'summary' => [
                'total_seats' => $totalCapacidad,
                'available' => $totalAvailable,
                'locked' => $totalLocked,
                'booked' => $totalBooked,
            ],
            'seatmap' => $seatList,
        ];
    }

    /**
     * Bloqueo atómico pesimista de un asiento (Holding Time 10 minutos).
     *
     * @param int $tripId
     * @param string $seatNumber
     * @param string|null $sessionId
     * @param int|null $userId
     * @param string|null $ip
     * @return array<string, mixed>
     * @throws SeatHoldConflictException
     */
    public function holdSeat(
        int $tripId,
        string $seatNumber,
        ?string $sessionId = null,
        ?int $userId = null,
        ?string $ip = null
    ): array {
        return DB::transaction(function () use ($tripId, $seatNumber, $sessionId, $userId, $ip) {
            // 1. Bloqueo pesimista del horario de ruta
            $trip = HorarioRuta::where('id', $tripId)->lockForUpdate()->firstOrFail();

            // 2. Verificar si el asiento ya está comprado definitivamente
            $isBooked = TiqueteDetalle::where('horario_ruta_id', $tripId)
                ->where('asiento_numero', $seatNumber)
                ->where('eliminado', 0)
                ->where('estado', '!=', 'Cancelado')
                ->lockForUpdate()
                ->exists();

            if ($isBooked) {
                throw new SeatHoldConflictException("El asiento {$seatNumber} ya ha sido comprado.");
            }

            // 3. Consultar bloqueo previo con bloqueo pesimista
            $existingHold = AsientoBloqueoTemporal::where('horario_ruta_id', $tripId)
                ->where('asiento_numero', $seatNumber)
                ->lockForUpdate()
                ->first();

            $now = now();
            $expiresAt = $now->copy()->addMinutes(self::HOLD_DURATION_MINUTES);
            $token = (string) Str::uuid();

            if ($existingHold) {
                // Si el bloqueo está activo y vigente
                if ($existingHold->estado === 'bloqueado' && $existingHold->expira_en && $existingHold->expira_en->isFuture()) {
                    $isSameSession = ($sessionId && $existingHold->session_id === $sessionId);
                    $isSameUser = ($userId && $existingHold->usuario_id === $userId);

                    if ($isSameSession || $isSameUser) {
                        // Renovar el bloqueo para la misma sesión/usuario
                        $existingHold->update([
                            'expira_en' => $expiresAt,
                            'ip_address' => $ip ?? $existingHold->ip_address,
                        ]);

                        return [
                            'seat_number' => $seatNumber,
                            'trip_id' => $tripId,
                            'token_bloqueo' => $existingHold->token_bloqueo,
                            'locked_until' => $expiresAt->toIso8601String(),
                            'remaining_seconds' => self::HOLD_DURATION_MINUTES * 60,
                            'status' => 'locked',
                            'message' => "Bloqueo extendido por " . self::HOLD_DURATION_MINUTES . " minutos.",
                        ];
                    }

                    // Bloqueado por otra persona
                    throw new SeatHoldConflictException("El asiento {$seatNumber} está actualmente retenido por otro usuario.");
                }

                // El bloqueo previo expiró o fue liberado: sobreescribir atómicamente
                $existingHold->update([
                    'empresa_id' => $trip->empresa_id ?? 1,
                    'token_bloqueo' => $token,
                    'session_id' => $sessionId,
                    'usuario_id' => $userId,
                    'ip_address' => $ip,
                    'estado' => 'bloqueado',
                    'bloqueado_en' => $now,
                    'expira_en' => $expiresAt,
                ]);

                return [
                    'seat_number' => $seatNumber,
                    'trip_id' => $tripId,
                    'token_bloqueo' => $token,
                    'locked_until' => $expiresAt->toIso8601String(),
                    'remaining_seconds' => self::HOLD_DURATION_MINUTES * 60,
                    'status' => 'locked',
                    'message' => "Asiento retenido exitosamente por " . self::HOLD_DURATION_MINUTES . " minutos.",
                ];
            }

            // 4. Crear nuevo bloqueo temporal
            $newHold = AsientoBloqueoTemporal::create([
                'empresa_id' => $trip->empresa_id ?? 1,
                'horario_ruta_id' => $tripId,
                'asiento_numero' => $seatNumber,
                'token_bloqueo' => $token,
                'session_id' => $sessionId,
                'usuario_id' => $userId,
                'ip_address' => $ip,
                'estado' => 'bloqueado',
                'bloqueado_en' => $now,
                'expira_en' => $expiresAt,
            ]);

            return [
                'seat_number' => $seatNumber,
                'trip_id' => $tripId,
                'token_bloqueo' => $newHold->token_bloqueo,
                'locked_until' => $expiresAt->toIso8601String(),
                'remaining_seconds' => self::HOLD_DURATION_MINUTES * 60,
                'status' => 'locked',
                'message' => "Asiento retenido exitosamente por " . self::HOLD_DURATION_MINUTES . " minutos.",
            ];
        });
    }

    /**
     * Libera voluntariamente el asiento seleccionado.
     *
     * @param int $tripId
     * @param string $seatNumber
     * @param string|null $sessionId
     * @param int|null $userId
     * @return array<string, mixed>
     */
    public function releaseSeat(
        int $tripId,
        string $seatNumber,
        ?string $sessionId = null,
        ?int $userId = null
    ): array {
        return DB::transaction(function () use ($tripId, $seatNumber, $sessionId, $userId) {
            $query = AsientoBloqueoTemporal::where('horario_ruta_id', $tripId)
                ->where('asiento_numero', $seatNumber)
                ->where('estado', 'bloqueado')
                ->lockForUpdate();

            if ($sessionId) {
                $query->where('session_id', $sessionId);
            } elseif ($userId) {
                $query->where('usuario_id', $userId);
            }

            $hold = $query->first();

            if ($hold) {
                $hold->update([
                    'estado' => 'liberado',
                    'expira_en' => now(),
                ]);

                return [
                    'seat_number' => $seatNumber,
                    'trip_id' => $tripId,
                    'status' => 'available',
                    'released' => true,
                    'message' => "Asiento {$seatNumber} liberado exitosamente.",
                ];
            }

            return [
                'seat_number' => $seatNumber,
                'trip_id' => $tripId,
                'status' => 'available',
                'released' => false,
                'message' => "No se encontró un bloqueo activo para liberar en este asiento.",
            ];
        });
    }
}
