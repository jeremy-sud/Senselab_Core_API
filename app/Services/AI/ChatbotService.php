<?php

namespace App\Services\AI;

use App\Models\Venta;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\CuentaPorCobrar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Servicio de Chatbot Asistente para ERP
 *
 * Permite realizar consultas en lenguaje natural sobre:
 * - Ventas y facturación
 * - Inventario y productos
 * - Clientes y cuentas por cobrar
 * - Estadísticas y reportes
 *
 * Por defecto usa Gemini (GRATUITO)
 *
 * Desarrollado por Senselab
 */
class ChatbotService
{
    protected AIServiceInterface $aiService;
    protected ?int $empresaId = null;

    /**
     * Funciones disponibles para el chatbot (Function Calling)
     * @var array<int, string>
     */
    protected array $availableFunctions = [
        'get_sales_summary',
        'get_inventory_status',
        'get_pending_invoices',
        'get_top_products',
        'get_top_customers',
        'get_low_stock_products',
        'get_daily_stats',
        'search_product',
        'search_customer',
    ];

    public function __construct(?AIServiceInterface $aiService = null)
    {
        // Usar Gemini por defecto (gratuito), fallback a OpenAI
        if ($aiService) {
            $this->aiService = $aiService;
        } elseif (!empty(config('gemini.api_key'))) {
            $this->aiService = app(GeminiService::class);
        } else {
            $this->aiService = app(OpenAIService::class);
        }
    }

    /**
     * Establecer empresa para filtrar datos
     */
    public function setEmpresa(int $empresaId): self
    {
        $this->empresaId = $empresaId;
        return $this;
    }

    /**
     * Procesar mensaje del usuario
     *
     * @param string $message Mensaje en lenguaje natural
     * @param array<string, mixed> $context Contexto adicional (historial, usuario)
     * @return array<string, mixed> Respuesta estructurada
     */
    public function processMessage(string $message, array $context = []): array
    {
        if (!config('openai.features.chatbot.enabled', true)) {
            return [
                'success' => false,
                'error' => 'El chatbot está deshabilitado',
            ];
        }

        // Detectar intención y obtener datos relevantes
        $intent = $this->detectIntent($message);
        $data = $this->fetchRelevantData($intent, $message);

        // Construir contexto con datos del sistema
        $systemPrompt = $this->buildSystemPrompt($data);

        // Enviar a IA (Gemini o OpenAI) con contexto
        $result = $this->aiService->chat($message, [
            'system_prompt' => $systemPrompt,
            'history' => $context['history'] ?? [],
        ], [
            'temperature' => 0.7,
            'max_tokens' => 2048,
        ]);

        if (!$result['success']) {
            return $result;
        }

        return [
            'success' => true,
            'message' => $result['content'] ?? $result['message'] ?? '',
            'intent' => $intent,
            'data_used' => array_keys($data),
            'usage' => $result['usage'] ?? null,
            'provider' => $result['provider'] ?? 'unknown',
            'cost' => $result['cost'] ?? 0.00,
        ];
    }

    /**
     * Detectar intención del mensaje
     */
    protected function detectIntent(string $message): string
    {
        $message = strtolower($message);

        $intents = [
            'sales' => ['ventas', 'vendimos', 'facturación', 'facturamos', 'ingresos', 'vendido'],
            'inventory' => ['inventario', 'stock', 'existencias', 'productos', 'agotado', 'bajo stock'],
            'customers' => ['clientes', 'cliente', 'compradores', 'deudores'],
            'invoices' => ['facturas', 'pendientes', 'por cobrar', 'morosos', 'cuentas'],
            'stats' => ['estadísticas', 'resumen', 'reporte', 'métricas', 'dashboard', 'hoy', 'mes'],
            'search' => ['buscar', 'busca', 'encuentra', 'dónde', 'cuál'],
        ];

        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword)) {
                    return $intent;
                }
            }
        }

        return 'general';
    }

    /**
     * Obtener datos relevantes según la intención
     *
     * @return array<string, mixed>
     */
    protected function fetchRelevantData(string $intent, string $message): array
    {
        $data = [];

        switch ($intent) {
            case 'sales':
                $data['ventas'] = $this->getSalesSummary();
                $data['top_productos'] = $this->getTopProducts(5);
                break;

            case 'inventory':
                $data['inventario'] = $this->getInventoryStatus();
                $data['bajo_stock'] = $this->getLowStockProducts(10);
                break;

            case 'customers':
                $data['clientes'] = $this->getCustomerStats();
                $data['top_clientes'] = $this->getTopCustomers(5);
                break;

            case 'invoices':
                $data['pendientes'] = $this->getPendingInvoices();
                break;

            case 'stats':
                $data['resumen_hoy'] = $this->getDailyStats();
                $data['resumen_mes'] = $this->getMonthlyStats();
                break;

            default:
                // Para consultas generales, incluir resumen básico
                $data['resumen_hoy'] = $this->getDailyStats();
        }

        return $data;
    }

    /**
     * Construir prompt del sistema con datos
     *
     * @param array<string, mixed> $data
     */
    protected function buildSystemPrompt(array $data): string
    {
        $dataJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Eres el asistente virtual de Senselab ERP, un sistema de gestión empresarial para empresas costarricenses.

Tu rol es ayudar a los usuarios a obtener información sobre su negocio de manera conversacional y amigable.

DATOS ACTUALES DEL SISTEMA:
{$dataJson}

INSTRUCCIONES:
1. Responde en español de manera clara y concisa
2. Usa los datos proporcionados para responder con precisión
3. Si no tienes datos para responder algo, indícalo amablemente
4. Puedes sugerir acciones o hacer recomendaciones basadas en los datos
5. Formatea números como moneda cuando sea apropiado (colones: ₡)
6. Si detectas problemas (bajo stock, facturas vencidas), menciónalos proactivamente
7. Sé profesional pero amigable

FORMATO:
- Usa viñetas para listas
- Destaca números importantes
- Mantén respuestas concisas (máximo 3-4 párrafos)
PROMPT;
    }

    /**
     * Obtener resumen de ventas
     *
     * @return array<string, mixed>
     */
    public function getSalesSummary(): array
    {
        $cacheKey = "chatbot_sales_{$this->empresaId}";

        return Cache::remember($cacheKey, 300, function () {
            $query = Venta::query();

            if ($this->empresaId) {
                $query->where('empresa_id', $this->empresaId);
            }

            $hoy = Carbon::today();
            $inicioMes = Carbon::now()->startOfMonth();
            $inicioSemana = Carbon::now()->startOfWeek();

            return [
                'hoy' => [
                    'total' => $query->clone()->whereDate('fecha_venta', $hoy)->sum('monto_total_venta'),
                    'cantidad' => $query->clone()->whereDate('fecha_venta', $hoy)->count(),
                ],
                'semana' => [
                    'total' => $query->clone()->where('fecha_venta', '>=', $inicioSemana)->sum('monto_total_venta'),
                    'cantidad' => $query->clone()->where('fecha_venta', '>=', $inicioSemana)->count(),
                ],
                'mes' => [
                    'total' => $query->clone()->where('fecha_venta', '>=', $inicioMes)->sum('monto_total_venta'),
                    'cantidad' => $query->clone()->where('fecha_venta', '>=', $inicioMes)->count(),
                ],
            ];
        });
    }

    /**
     * Obtener estado del inventario
     *
     * @return array<string, mixed>
     */
    public function getInventoryStatus(): array
    {
        $cacheKey = "chatbot_inventory_{$this->empresaId}";

        return Cache::remember($cacheKey, 300, function () {
            $query = Producto::query();

            if ($this->empresaId) {
                $query->where('empresa_id', $this->empresaId);
            }

            $totalProductos = $query->clone()->where('activo', true)->count();
            $conStock = $query->clone()->where('activo', true)->where('stock', '>', 0)->count();
            $sinStock = $query->clone()->where('activo', true)->where('stock', '<=', 0)->count();
            $bajoStock = $query->clone()
                ->where('activo', true)
                ->whereColumn('stock', '<=', 'stock_minimo')
                ->where('stock', '>', 0)
                ->count();

            return [
                'total_productos' => $totalProductos,
                'con_stock' => $conStock,
                'sin_stock' => $sinStock,
                'bajo_stock' => $bajoStock,
                'valor_inventario' => $query->clone()
                    ->where('activo', true)
                    ->selectRaw('SUM(stock * precio_costo) as valor')
                    ->value('valor') ?? 0,
            ];
        });
    }

    /**
     * Obtener facturas pendientes
     *
     * @return array<string, mixed>
     */
    public function getPendingInvoices(): array
    {
        $cacheKey = "chatbot_pending_{$this->empresaId}";

        return Cache::remember($cacheKey, 300, function () {
            $query = CuentaPorCobrar::query()->where('estado', 'pendiente');

            if ($this->empresaId) {
                $query->where('empresa_id', $this->empresaId);
            }

            $hoy = Carbon::today();

            $pendientes = $query->clone()->count();
            $montoPendiente = $query->clone()->sum('saldo_pendiente');
            $vencidas = $query->clone()->where('fecha_vencimiento', '<', $hoy)->count();
            $montoVencido = $query->clone()->where('fecha_vencimiento', '<', $hoy)->sum('saldo_pendiente');

            return [
                'cantidad_pendientes' => $pendientes,
                'monto_pendiente' => $montoPendiente,
                'cantidad_vencidas' => $vencidas,
                'monto_vencido' => $montoVencido,
            ];
        });
    }

    /**
     * Obtener productos más vendidos
     *
     * @return array<int, mixed>
     */
    public function getTopProducts(int $limit = 5): array
    {
        $cacheKey = "chatbot_top_products_{$this->empresaId}_{$limit}";

        return Cache::remember($cacheKey, 600, function () use ($limit) {
            $inicioMes = Carbon::now()->startOfMonth();

            $query = DB::table('detalle_ventas')
                ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
                ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
                ->where('ventas.fecha_venta', '>=', $inicioMes)
                ->where('ventas.estado', '!=', 'anulada');

            if ($this->empresaId) {
                $query->where('ventas.empresa_id', $this->empresaId);
            }

            return $query->select(
                'productos.nombre',
                DB::raw('SUM(detalle_ventas.cantidad) as cantidad_vendida'),
                DB::raw('SUM(detalle_ventas.subtotal) as total_vendido')
            )
                ->groupBy('productos.id', 'productos.nombre')
                ->orderByDesc('cantidad_vendida')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    /**
     * Obtener mejores clientes
     *
     * @return array<int, mixed>
     */
    public function getTopCustomers(int $limit = 5): array
    {
        $cacheKey = "chatbot_top_customers_{$this->empresaId}_{$limit}";

        return Cache::remember($cacheKey, 600, function () use ($limit) {
            $inicioMes = Carbon::now()->startOfMonth();

            $query = DB::table('ventas')
                ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
                ->where('ventas.fecha_venta', '>=', $inicioMes)
                ->where('ventas.estado', '!=', 'anulada');

            if ($this->empresaId) {
                $query->where('ventas.empresa_id', $this->empresaId);
            }

            return $query->select(
                'clientes.nombre',
                DB::raw('COUNT(*) as cantidad_compras'),
                DB::raw('SUM(ventas.monto_total_venta) as total_comprado')
            )
                ->groupBy('clientes.id', 'clientes.nombre')
                ->orderByDesc('total_comprado')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    /**
     * Obtener productos con bajo stock
     *
     * @return array<int, mixed>
     */
    public function getLowStockProducts(int $limit = 10): array
    {
        $cacheKey = "chatbot_low_stock_{$this->empresaId}_{$limit}";

        return Cache::remember($cacheKey, 300, function () use ($limit) {
            $query = Producto::query()
                ->where('activo', true)
                ->whereColumn('stock', '<=', 'stock_minimo')
                ->orderBy('stock');

            if ($this->empresaId) {
                $query->where('empresa_id', $this->empresaId);
            }

            return $query->select('nombre', 'stock', 'stock_minimo', 'codigo')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    /**
     * Obtener estadísticas del día
     *
     * @return array<string, mixed>
     */
    public function getDailyStats(): array
    {
        $cacheKey = "chatbot_daily_{$this->empresaId}_" . Carbon::today()->format('Y-m-d');

        return Cache::remember($cacheKey, 300, function () {
            $hoy = Carbon::today();

            $ventasQuery = Venta::whereDate('fecha_venta', $hoy);
            if ($this->empresaId) {
                $ventasQuery->where('empresa_id', $this->empresaId);
            }

            return [
                'fecha' => $hoy->format('Y-m-d'),
                'ventas_total' => $ventasQuery->clone()->sum('monto_total_venta'),
                'ventas_cantidad' => $ventasQuery->clone()->count(),
                'ticket_promedio' => $ventasQuery->clone()->avg('monto_total_venta'),
            ];
        });
    }

    /**
     * Obtener estadísticas del mes
     *
     * @return array<string, mixed>
     */
    public function getMonthlyStats(): array
    {
        $cacheKey = "chatbot_monthly_{$this->empresaId}_" . Carbon::now()->format('Y-m');

        return Cache::remember($cacheKey, 600, function () {
            $inicioMes = Carbon::now()->startOfMonth();

            $ventasQuery = Venta::where('fecha_venta', '>=', $inicioMes);
            if ($this->empresaId) {
                $ventasQuery->where('empresa_id', $this->empresaId);
            }

            return [
                'mes' => Carbon::now()->format('F Y'),
                'ventas_total' => $ventasQuery->clone()->sum('monto_total_venta'),
                'ventas_cantidad' => $ventasQuery->clone()->count(),
                'ticket_promedio' => $ventasQuery->clone()->avg('monto_total_venta'),
            ];
        });
    }

    /**
     * Obtener estadísticas de clientes
     *
     * @return array<string, mixed>
     *
     * @return array<string, mixed>
     */
    protected function getCustomerStats(): array
    {
        $query = Cliente::query();
        if ($this->empresaId) {
            $query->where('empresa_id', $this->empresaId);
        }

        return [
            'total' => $query->clone()->where('activo', true)->count(),
            'nuevos_mes' => $query->clone()
                ->where('created_at', '>=', Carbon::now()->startOfMonth())
                ->count(),
        ];
    }

    /**
     * Limpiar cache del chatbot
     */
    public function clearCache(): void
    {
        $patterns = [
            "chatbot_sales_{$this->empresaId}",
            "chatbot_inventory_{$this->empresaId}",
            "chatbot_pending_{$this->empresaId}",
            "chatbot_top_products_{$this->empresaId}_*",
            "chatbot_top_customers_{$this->empresaId}_*",
            "chatbot_low_stock_{$this->empresaId}_*",
            "chatbot_daily_{$this->empresaId}_*",
            "chatbot_monthly_{$this->empresaId}_*",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
}

