<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AuditoriaActividad;
use Illuminate\Http\Request;
use App\Traits\HasCacheableQueries;
use OpenApi\Attributes as OA;

class AuditoriaActividadController extends Controller
{
    use HasCacheableQueries;
    
    protected $cacheTags = ['auditoria_actividades', 'auditoria'];
    protected $cacheTTL = 1800; // 30 minutos

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/auditoria-actividades',
        summary: 'Listar auditorías de actividad',
        description: 'Obtiene un listado de registros de auditoría (solo lectura)',
        security: [['sanctum' => []]],
        tags: ['Auditoría Actividades'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                description: 'Cantidad de registros por página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
            new OA\Parameter(
                name: 'usuario_id',
                description: 'Filtrar por usuario',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'accion',
                description: 'Filtrar por acción (crear, actualizar, eliminar)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['crear', 'actualizar', 'eliminar'])
            ),
            new OA\Parameter(
                name: 'tabla',
                description: 'Filtrar por tabla',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'fecha_inicio',
                description: 'Fecha inicial del rango',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'fecha_fin',
                description: 'Fecha final del rango',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado obtenido exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'meta', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $this->authorize('viewAny', AuditoriaActividad::class);

        $cacheKey = $this->generateCacheKey('auditoria_actividades.index', $request->all());

        return $this->getCached($cacheKey, function () use ($request) {
            $perPage = $request->input('per_page', 15);
            
            $query = AuditoriaActividad::with(['usuario', 'empresa']);

            // Filtros
            if ($request->filled('usuario_id')) {
                $query->porUsuario($request->usuario_id);
            }

            if ($request->filled('accion')) {
                $query->accion($request->accion);
            }

            if ($request->filled('tabla')) {
                $query->tabla($request->tabla);
            }

            if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
                $query->entreFechas($request->fecha_inicio, $request->fecha_fin);
            }

            // Ordenar por más recientes primero
            $auditorias = $query->orderBy('id', 'desc')->cursorPaginate($perPage);

            // Incluir cambios calculados
            $auditorias->getCollection()->transform(function ($auditoria) {
                $auditoria->cambios = $auditoria->cambios;
                return $auditoria;
            });

            return response()->json($auditorias);
        });
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/auditoria-actividades/{id}',
        summary: 'Obtener auditoría específica',
        description: 'Obtiene los detalles de un registro de auditoría',
        security: [['sanctum' => []]],
        tags: ['Auditoría Actividades'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del registro de auditoría',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Auditoría obtenida exitosamente'
            )
        ]
    )]
    public function show(string $id)
    {
        $auditoria = AuditoriaActividad::with(['usuario', 'empresa'])->findOrFail($id);
        $this->authorize('view', $auditoria);

        $cacheKey = $this->generateCacheKey("auditoria_actividades.show.{$id}");

        return $this->getCached($cacheKey, function () use ($auditoria) {
            // Incluir cambios calculados
            $data = $auditoria->toArray();
            $data['cambios'] = $auditoria->cambios;
            
            return response()->json(['data' => $data]);
        });
    }

    /**
     * Obtener estadísticas de auditoría
     */
    #[OA\Get(
        path: '/api/auditoria-actividades/estadisticas',
        summary: 'Obtener estadísticas de auditoría',
        description: 'Obtiene estadísticas resumidas de actividad',
        security: [['sanctum' => []]],
        tags: ['Auditoría Actividades'],
        parameters: [
            new OA\Parameter(
                name: 'fecha_inicio',
                description: 'Fecha inicial del rango',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'fecha_fin',
                description: 'Fecha final del rango',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Estadísticas obtenidas exitosamente'
            )
        ]
    )]
    public function estadisticas(Request $request)
    {
        $this->authorize('viewAny', AuditoriaActividad::class);

        $query = AuditoriaActividad::query();

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->entreFechas($request->fecha_inicio, $request->fecha_fin);
        }

        $estadisticas = [
            'total_actividades' => $query->count(),
            'por_accion' => $query->selectRaw('accion, COUNT(*) as total')
                ->groupBy('accion')
                ->pluck('total', 'accion'),
            'por_tabla' => $query->selectRaw('tabla, COUNT(*) as total')
                ->groupBy('tabla')
                ->orderByDesc('total')
                ->limit(10)
                ->pluck('total', 'tabla'),
            'usuarios_mas_activos' => $query->with('usuario')
                ->selectRaw('usuario_id, COUNT(*) as total')
                ->groupBy('usuario_id')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'usuario' => $item->usuario ? $item->usuario->nombre : 'Usuario eliminado',
                        'total' => $item->total
                    ];
                }),
        ];

        return response()->json(['data' => $estadisticas]);
    }

    /**
     * Exportar auditorías
     */
    #[OA\Get(
        path: '/api/auditoria-actividades/exportar',
        summary: 'Exportar auditorías',
        description: 'Exporta registros de auditoría en formato CSV',
        security: [['sanctum' => []]],
        tags: ['Auditoría Actividades'],
        parameters: [
            new OA\Parameter(
                name: 'fecha_inicio',
                description: 'Fecha inicial del rango',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'fecha_fin',
                description: 'Fecha final del rango',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'usuario_id',
                description: 'Filtrar por usuario',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'tabla',
                description: 'Filtrar por tabla',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Exportación generada exitosamente',
                content: new OA\MediaType(
                    mediaType: 'text/csv',
                    schema: new OA\Schema(type: 'string', format: 'binary')
                )
            )
        ]
    )]
    public function exportar(Request $request)
    {
        $this->authorize('viewAny', AuditoriaActividad::class);

        $query = AuditoriaActividad::with(['usuario', 'empresa']);

        // Aplicar filtros
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->entreFechas($request->fecha_inicio, $request->fecha_fin);
        }

        if ($request->filled('usuario_id')) {
            $query->porUsuario($request->usuario_id);
        }

        if ($request->filled('tabla')) {
            $query->tabla($request->tabla);
        }

        $auditorias = $query->orderBy('created_at', 'desc')->get();

        // Generar CSV
        $csv = "ID,Fecha,Usuario,Empresa,Acción,Tabla,Registro ID,IP,User Agent\n";
        
        foreach ($auditorias as $auditoria) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $auditoria->id,
                $auditoria->created_at->format('Y-m-d H:i:s'),
                $auditoria->usuario ? $auditoria->usuario->nombre : 'N/A',
                $auditoria->empresa ? $auditoria->empresa->nombre : 'N/A',
                $auditoria->accion,
                $auditoria->tabla,
                $auditoria->registro_id ?? 'N/A',
                $auditoria->ip_address ?? 'N/A',
                str_replace(',', ';', $auditoria->user_agent ?? 'N/A')
            );
        }

        $filename = 'auditoria_actividades_' . now()->format('Y-m-d_His') . '.csv';

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
