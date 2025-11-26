<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeCertificadoDigitalResource;
use App\Http\Requests\StoreFeCertificadoDigitalRequest;
use App\Http\Requests\UpdateFeCertificadoDigitalRequest;
use App\Models\FeCertificadoDigital;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FeCertificadoDigitalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FeCertificadoDigital::class);

        /** @var \App\Models\Usuario $user */
        $user = auth()->user();

        $query = FeCertificadoDigital::where('empresa_id', $user->empresa_id);

        // Filtrar por estado activo
        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        // Filtrar por ambiente
        if ($request->has('ambiente')) {
            $query->where('ambiente', $request->ambiente);
        }

        // Filtrar certificados vigentes
        if ($request->boolean('solo_vigentes')) {
            $query->where('fecha_vencimiento', '>=', now());
        }

        // Filtrar certificados próximos a vencer
        if ($request->has('dias_vencimiento')) {
            $diasVencimiento = (int) $request->dias_vencimiento;
            $query->whereDate('fecha_vencimiento', '<=', now()->addDays($diasVencimiento))
                ->whereDate('fecha_vencimiento', '>=', now());
        }

        $certificados = $query->latest()->get();

        return response()->json([
            'data' => FeCertificadoDigitalResource::collection($certificados),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFeCertificadoDigitalRequest $request): JsonResponse
    {
        $this->authorize('create', FeCertificadoDigital::class);

        /** @var \App\Models\Usuario $user */
        $user = auth()->user();

        $validated = $request->validated();

        // Manejar subida del archivo .p12
        if ($request->hasFile('archivo_certificado')) {
            $file = $request->file('archivo_certificado');
            $nombreOriginal = $file->getClientOriginalName();
            $nombreUnico = Str::uuid() . '.p12';
            
            // Guardar en almacenamiento seguro
            $ruta = $file->storeAs(
                'certificados_fe/' . $user->empresa_id,
                $nombreUnico,
                'private'
            );

            $validated['nombre_archivo_original'] = $nombreOriginal;
            $validated['ruta_archivo'] = $ruta;
        }

        // Si es el primer certificado o se marca como activo, desactivar otros
        if ($validated['activo'] ?? false) {
            FeCertificadoDigital::where('empresa_id', $user->empresa_id)
                ->where('ambiente', $validated['ambiente'])
                ->update(['activo' => false]);
        }

        $validated['empresa_id'] = $user->empresa_id;

        $certificado = FeCertificadoDigital::create($validated);

        return response()->json([
            'message' => 'Certificado digital creado exitosamente',
            'data' => new FeCertificadoDigitalResource($certificado),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(FeCertificadoDigital $certificado): JsonResponse
    {
        $this->authorize('view', $certificado);

        return response()->json([
            'data' => new FeCertificadoDigitalResource($certificado),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFeCertificadoDigitalRequest $request, FeCertificadoDigital $certificado): JsonResponse
    {
        $this->authorize('update', $certificado);

        /** @var \App\Models\Usuario $user */
        $user = auth()->user();

        $validated = $request->validated();

        // Si se marca como activo, desactivar otros certificados del mismo ambiente
        if (($validated['activo'] ?? false) && !$certificado->activo) {
            FeCertificadoDigital::where('empresa_id', $user->empresa_id)
                ->where('ambiente', $certificado->ambiente)
                ->where('id', '!=', $certificado->id)
                ->update(['activo' => false]);
        }

        $certificado->update($validated);

        return response()->json([
            'message' => 'Certificado digital actualizado exitosamente',
            'data' => new FeCertificadoDigitalResource($certificado),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FeCertificadoDigital $certificado): JsonResponse
    {
        $this->authorize('delete', $certificado);

        // Eliminar archivo físico
        if ($certificado->ruta_archivo && Storage::disk('private')->exists($certificado->ruta_archivo)) {
            Storage::disk('private')->delete($certificado->ruta_archivo);
        }

        $certificado->delete();

        return response()->json([
            'message' => 'Certificado digital eliminado exitosamente',
        ]);
    }

    /**
     * Activate a certificate.
     */
    public function activar(FeCertificadoDigital $certificado): JsonResponse
    {
        $this->authorize('activar', $certificado);

        /** @var \App\Models\Usuario $user */
        $user = auth()->user();

        // Verificar que no esté vencido
        if ($certificado->fecha_vencimiento < now()) {
            return response()->json([
                'message' => 'No se puede activar un certificado vencido',
            ], 422);
        }

        // Desactivar otros certificados del mismo ambiente
        FeCertificadoDigital::where('empresa_id', $user->empresa_id)
            ->where('ambiente', $certificado->ambiente)
            ->where('id', '!=', $certificado->id)
            ->update(['activo' => false]);

        $certificado->update(['activo' => true]);

        return response()->json([
            'message' => 'Certificado activado exitosamente',
            'data' => new FeCertificadoDigitalResource($certificado),
        ]);
    }

    /**
     * Deactivate a certificate.
     */
    public function desactivar(FeCertificadoDigital $certificado): JsonResponse
    {
        $this->authorize('update', $certificado);

        $certificado->update(['activo' => false]);

        return response()->json([
            'message' => 'Certificado desactivado exitosamente',
            'data' => new FeCertificadoDigitalResource($certificado),
        ]);
    }

    /**
     * Get the active certificate for an environment.
     */
    public function activo(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FeCertificadoDigital::class);

        /** @var \App\Models\Usuario $user */
        $user = auth()->user();

        $ambiente = $request->get('ambiente', 'produccion');

        $certificado = FeCertificadoDigital::where('empresa_id', $user->empresa_id)
            ->where('ambiente', $ambiente)
            ->where('activo', true)
            ->where('fecha_vencimiento', '>=', now())
            ->first();

        if (!$certificado) {
            return response()->json([
                'message' => 'No hay certificado activo para el ambiente ' . $ambiente,
            ], 404);
        }

        return response()->json([
            'data' => new FeCertificadoDigitalResource($certificado),
        ]);
    }

    /**
     * Get certificates expiring soon.
     */
    public function proximosVencer(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FeCertificadoDigital::class);

        /** @var \App\Models\Usuario $user */
        $user = auth()->user();

        $dias = $request->get('dias', 30);

        $certificados = FeCertificadoDigital::where('empresa_id', $user->empresa_id)
            ->whereDate('fecha_vencimiento', '<=', now()->addDays($dias))
            ->whereDate('fecha_vencimiento', '>=', now())
            ->orderBy('fecha_vencimiento')
            ->get();

        return response()->json([
            'data' => FeCertificadoDigitalResource::collection($certificados),
        ]);
    }
}
