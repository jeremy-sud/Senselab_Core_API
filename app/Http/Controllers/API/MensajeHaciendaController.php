<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\MensajeHaciendaResource;
use App\Models\MensajeHacienda;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;

class MensajeHaciendaController extends Controller
{
    use HasCacheableQueries;

    protected array $cacheTags = ['mensajes-hacienda', 'hacienda', 'facturacion-electronica'];
    protected int $cacheTTL = 900; // 15 minutos - mensajes dinámicos de Hacienda

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', MensajeHacienda::class);

        $cacheKey = $this->getCacheKey('index', [
            'estado' => $request->get('estado'),
            'tipo_mensaje' => $request->get('tipo_mensaje'),
            'comprobante_id' => $request->get('comprobante_id'),
            'fecha_desde' => $request->get('fecha_desde'),
            'fecha_hasta' => $request->get('fecha_hasta'),
            'per_page' => $request->get('per_page', 20),
        ]);

        $mensajes = $this->cacheQueryIfEnabled($cacheKey, function () use ($request) {
            $query = MensajeHacienda::query()
                ->where('empresa_id', auth('sanctum')->user()->empresa_id)
                ->with(['comprobante']);

            if ($request->filled('estado')) {
                $query->where('estado', $request->get('estado'));
            }

            if ($request->filled('tipo_mensaje')) {
                $query->porTipo($request->get('tipo_mensaje'));
            }

            if ($request->filled('comprobante_id')) {
                $query->where('comprobante_id', $request->get('comprobante_id'));
            }

            if ($request->filled('fecha_desde')) {
                $query->where('fecha_emision', '>=', $request->get('fecha_desde'));
            }

            if ($request->filled('fecha_hasta')) {
                $query->where('fecha_emision', '<=', $request->get('fecha_hasta'));
            }

            return $query->orderBy('fecha_emision', 'desc')
                ->paginate($request->get('per_page', 20));
        });

        return MensajeHaciendaResource::collection($mensajes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', MensajeHacienda::class);

        $validator = Validator::make($request->all(), [
            'comprobante_id' => 'required|exists:comprobantes_recibidos_electronicos,id',
            'clave_numerica' => 'required|string|max:50',
            'tipo_mensaje' => 'required|string|in:aceptacion,rechazo_parcial,rechazo_total,confirmacion',
            'codigo_respuesta' => 'nullable|string|max:10',
            'detalle_mensaje' => 'nullable|string',
            'xml_respuesta' => 'nullable|string',
            'fecha_emision' => 'required|date',
            'estado' => 'required|string|in:pendiente,procesado,error',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $mensaje = MensajeHacienda::create([
            'empresa_id' => auth('sanctum')->user()->empresa_id,
            'comprobante_id' => $request->get('comprobante_id'),
            'clave_numerica' => $request->get('clave_numerica'),
            'tipo_mensaje' => $request->get('tipo_mensaje'),
            'codigo_respuesta' => $request->get('codigo_respuesta'),
            'detalle_mensaje' => $request->get('detalle_mensaje'),
            'xml_respuesta' => $request->get('xml_respuesta'),
            'fecha_emision' => $request->get('fecha_emision'),
            'fecha_procesamiento' => $request->get('estado') === 'procesado' ? now() : null,
            'estado' => $request->get('estado', 'pendiente'),
            'intentos_envio' => 0,
        ]);

        $this->flushCache();

        return (new MensajeHaciendaResource($mensaje))
            ->additional(['message' => 'Mensaje de Hacienda creado exitosamente'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(MensajeHacienda $mensajeHacienda): MensajeHaciendaResource
    {
        $this->authorize('view', $mensajeHacienda);

        $mensajeHacienda->load(['comprobante', 'empresa']);

        return new MensajeHaciendaResource($mensajeHacienda);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MensajeHacienda $mensajeHacienda): MensajeHaciendaResource
    {
        $this->authorize('update', $mensajeHacienda);

        $validator = Validator::make($request->all(), [
            'codigo_respuesta' => 'nullable|string|max:10',
            'detalle_mensaje' => 'nullable|string',
            'xml_respuesta' => 'nullable|string',
            'estado' => 'sometimes|string|in:pendiente,procesado,error',
            'ultimo_error' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            abort(422, 'Error de validación: ' . json_encode($validator->errors()));
        }

        $data = $request->only([
            'codigo_respuesta',
            'detalle_mensaje',
            'xml_respuesta',
            'estado',
            'ultimo_error',
        ]);

        if ($request->filled('estado') && $request->get('estado') === 'procesado') {
            $data['fecha_procesamiento'] = now();
        }

        $mensajeHacienda->update($data);

        $this->flushCache();

        return (new MensajeHaciendaResource($mensajeHacienda))
            ->additional(['message' => 'Mensaje de Hacienda actualizado exitosamente']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MensajeHacienda $mensajeHacienda): JsonResponse
    {
        $this->authorize('delete', $mensajeHacienda);

        $mensajeHacienda->eliminado = true;
        $mensajeHacienda->save();

        $this->flushCache();

        return response()->json([
            'message' => 'Mensaje de Hacienda eliminado exitosamente'
        ]);
    }