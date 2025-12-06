<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Caby;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Clasificación Automática CABYS
 *
 * Sugiere códigos CABYS automáticamente basado en:
 * - Descripción del producto
 * - Categoría
 * - Palabras clave
 *
 * El catálogo CABYS (Catálogo de Bienes y Servicios) de Costa Rica
 * contiene más de 10,000 códigos para facturación electrónica.
 *
 * Usa Gemini (GRATUITO) por defecto
 *
 * Desarrollado por Sistemas Ursol S.A.
 */
class CabysClassifierService
{
    protected AIServiceInterface $aiService;

    /**
     * Categorías principales CABYS con ejemplos
     */
    protected array $mainCategories = [
        '1' => ['nombre' => 'Productos alimenticios', 'ejemplos' => 'arroz, frijoles, carne, frutas, verduras, lácteos'],
        '2' => ['nombre' => 'Bebidas', 'ejemplos' => 'agua, refrescos, jugos, café, té, licores'],
        '3' => ['nombre' => 'Tabaco', 'ejemplos' => 'cigarrillos, puros, tabaco'],
        '4' => ['nombre' => 'Textiles y calzado', 'ejemplos' => 'ropa, zapatos, telas, uniformes'],
        '5' => ['nombre' => 'Productos de madera y papel', 'ejemplos' => 'muebles, papel, cartón, empaques'],
        '6' => ['nombre' => 'Productos químicos y farmacéuticos', 'ejemplos' => 'medicamentos, cosméticos, jabones'],
        '7' => ['nombre' => 'Productos de caucho y plástico', 'ejemplos' => 'llantas, bolsas plásticas, tuberías'],
        '8' => ['nombre' => 'Productos minerales', 'ejemplos' => 'cemento, vidrio, cerámica, piedra'],
        '9' => ['nombre' => 'Metales y productos metálicos', 'ejemplos' => 'hierro, acero, aluminio, herramientas'],
        '10' => ['nombre' => 'Maquinaria y equipo', 'ejemplos' => 'computadoras, electrodomésticos, maquinaria industrial'],
        '11' => ['nombre' => 'Equipo de transporte', 'ejemplos' => 'vehículos, repuestos, bicicletas'],
        '12' => ['nombre' => 'Servicios', 'ejemplos' => 'consultoría, reparaciones, alquileres, educación'],
    ];

    public function __construct(?AIServiceInterface $aiService = null)
    {
        if ($aiService) {
            $this->aiService = $aiService;
        } elseif (!empty(config('gemini.api_key'))) {
            $this->aiService = app(GeminiService::class);
        } else {
            $this->aiService = app(OpenAIService::class);
        }
    }

    /**
     * Clasificar producto y sugerir código CABYS
     */
    public function classifyProduct(array $producto): array
    {
        $descripcion = $producto['descripcion'] ?? $producto['nombre'] ?? '';
        $categoria = $producto['categoria'] ?? '';
        $unidadMedida = $producto['unidad_medida'] ?? '';

        if (empty($descripcion)) {
            return [
                'success' => false,
                'error' => 'Se requiere descripción o nombre del producto',
            ];
        }

        // Paso 1: Buscar coincidencias exactas en la base de datos
        $exactMatch = $this->searchExactMatch($descripcion);
        if ($exactMatch) {
            return [
                'success' => true,
                'metodo' => 'coincidencia_exacta',
                'sugerencias' => [$exactMatch],
                'confianza' => 'alta',
            ];
        }

        // Paso 2: Buscar por palabras clave
        $keywordMatches = $this->searchByKeywords($descripcion);
        if (count($keywordMatches) > 0 && $keywordMatches[0]['score'] > 0.7) {
            return [
                'success' => true,
                'metodo' => 'palabras_clave',
                'sugerencias' => array_slice($keywordMatches, 0, 5),
                'confianza' => 'media',
            ];
        }

        // Paso 3: Usar IA para clasificar
        return $this->classifyWithAI($descripcion, $categoria, $unidadMedida, $keywordMatches);
    }

    /**
     * Clasificar múltiples productos en lote
     */
    public function classifyBatch(array $productos): array
    {
        $results = [];
        $errors = [];

        foreach ($productos as $index => $producto) {
            try {
                $result = $this->classifyProduct($producto);
                $results[] = [
                    'producto' => $producto['nombre'] ?? $producto['descripcion'] ?? "Producto {$index}",
                    'producto_id' => $producto['id'] ?? null,
                    'resultado' => $result,
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'producto' => $producto['nombre'] ?? "Producto {$index}",
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => true,
            'total_procesados' => count($productos),
            'exitosos' => count($results),
            'fallidos' => count($errors),
            'resultados' => $results,
            'errores' => $errors,
        ];
    }

    /**
     * Buscar código CABYS por texto
     */
    public function searchCabys(string $query, int $limit = 10): array
    {
        $cacheKey = "cabys_search:" . md5($query);

        return Cache::remember($cacheKey, 3600, function () use ($query, $limit) {
            // Buscar en tabla cabys
            $results = DB::table('cabys')
                ->where('descripcion', 'LIKE', "%{$query}%")
                ->orWhere('codigo', 'LIKE', "{$query}%")
                ->limit($limit)
                ->select(['id', 'codigo', 'descripcion', 'impuesto'])
                ->get();

            return [
                'success' => true,
                'query' => $query,
                'total' => $results->count(),
                'resultados' => $results->toArray(),
            ];
        });
    }

    /**
     * Obtener información de un código CABYS específico
     */
    public function getCabysInfo(string $codigo): array
    {
        $caby = DB::table('cabys')
            ->where('codigo', $codigo)
            ->first();

        if (!$caby) {
            return [
                'success' => false,
                'error' => 'Código CABYS no encontrado',
            ];
        }

        return [
            'success' => true,
            'codigo' => $caby->codigo,
            'descripcion' => $caby->descripcion,
            'impuesto' => $caby->impuesto ?? 13,
            'categoria_principal' => substr($caby->codigo, 0, 1),
            'categoria_nombre' => $this->mainCategories[substr($caby->codigo, 0, 1)]['nombre'] ?? 'Desconocida',
        ];
    }

    /**
     * Validar si un código CABYS existe
     */
    public function validateCabys(string $codigo): array
    {
        $exists = DB::table('cabys')
            ->where('codigo', $codigo)
            ->exists();

        return [
            'success' => true,
            'codigo' => $codigo,
            'valido' => $exists,
            'mensaje' => $exists ? 'Código CABYS válido' : 'Código CABYS no encontrado en el catálogo',
        ];
    }

    /**
     * Obtener categorías principales CABYS
     */
    public function getMainCategories(): array
    {
        return [
            'success' => true,
            'categorias' => $this->mainCategories,
        ];
    }

    // ========== MÉTODOS PRIVADOS ==========

    /**
     * Buscar coincidencia exacta en la base de datos
     */
    protected function searchExactMatch(string $descripcion): ?array
    {
        $descripcion = strtolower(trim($descripcion));

        $match = DB::table('cabys')
            ->whereRaw('LOWER(descripcion) = ?', [$descripcion])
            ->first();

        if ($match) {
            return [
                'codigo' => $match->codigo,
                'descripcion' => $match->descripcion,
                'impuesto' => $match->impuesto ?? 13,
                'score' => 1.0,
            ];
        }

        return null;
    }

    /**
     * Buscar por palabras clave
     */
    protected function searchByKeywords(string $descripcion): array
    {
        // Extraer palabras significativas (>3 caracteres)
        $palabras = array_filter(
            explode(' ', strtolower($descripcion)),
            fn($p) => strlen($p) > 3
        );

        if (empty($palabras)) {
            return [];
        }

        // Construir query con OR para cada palabra
        $query = DB::table('cabys');

        foreach ($palabras as $palabra) {
            $query->orWhere('descripcion', 'LIKE', "%{$palabra}%");
        }

        $results = $query->limit(20)
            ->select(['id', 'codigo', 'descripcion', 'impuesto'])
            ->get();

        // Calcular score basado en coincidencias
        $scored = [];
        foreach ($results as $result) {
            $descLower = strtolower($result->descripcion);
            $matches = 0;

            foreach ($palabras as $palabra) {
                if (str_contains($descLower, $palabra)) {
                    $matches++;
                }
            }

            $score = count($palabras) > 0 ? $matches / count($palabras) : 0;

            $scored[] = [
                'codigo' => $result->codigo,
                'descripcion' => $result->descripcion,
                'impuesto' => $result->impuesto ?? 13,
                'score' => round($score, 2),
            ];
        }

        // Ordenar por score descendente
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        return $scored;
    }

    /**
     * Clasificar usando IA
     */
    protected function classifyWithAI(string $descripcion, string $categoria, string $unidadMedida, array $candidatos): array
    {
        // Preparar contexto con candidatos existentes
        $candidatosTexto = '';
        foreach (array_slice($candidatos, 0, 10) as $c) {
            $candidatosTexto .= "- {$c['codigo']}: {$c['descripcion']} (score: {$c['score']})\n";
        }

        // Preparar categorías principales
        $categoriasTexto = '';
        foreach ($this->mainCategories as $num => $cat) {
            $categoriasTexto .= "{$num}. {$cat['nombre']} (ej: {$cat['ejemplos']})\n";
        }

        $prompt = <<<PROMPT
Eres un experto en clasificación de productos para facturación electrónica de Costa Rica.
Tu tarea es sugerir el código CABYS más apropiado para un producto.

PRODUCTO A CLASIFICAR:
- Descripción: {$descripcion}
- Categoría: {$categoria}
- Unidad de medida: {$unidadMedida}

CATEGORÍAS PRINCIPALES CABYS:
{$categoriasTexto}

CANDIDATOS ENCONTRADOS EN BASE DE DATOS:
{$candidatosTexto}

INSTRUCCIONES:
1. Si algún candidato coincide bien, selecciónalo
2. Si no, sugiere el código CABYS más probable basado en la descripción
3. Proporciona nivel de confianza (alta/media/baja)
4. Explica brevemente tu razonamiento

Responde en formato JSON:
{{
    "codigo_sugerido": "código de 13 dígitos o el más cercano",
    "descripcion_cabys": "descripción del código sugerido",
    "confianza": "alta|media|baja",
    "razonamiento": "explicación breve",
    "alternativas": ["código1", "código2"]
}}
PROMPT;

        $result = $this->aiService->chat($prompt, [], [
            'temperature' => 0.2,
            'max_tokens' => 500,
        ]);

        if (!$result['success']) {
            // Fallback: devolver mejores candidatos
            return [
                'success' => true,
                'metodo' => 'fallback_keywords',
                'sugerencias' => array_slice($candidatos, 0, 5),
                'confianza' => 'baja',
                'nota' => 'IA no disponible, usando coincidencia por palabras clave',
            ];
        }

        // Parsear respuesta
        $content = $result['content'] ?? '';
        $content = preg_replace('/^```json\s*/i', '', $content);
        $content = preg_replace('/\s*```$/i', '', $content);

        $parsed = json_decode(trim($content), true);

        if (!$parsed) {
            return [
                'success' => true,
                'metodo' => 'ia_texto',
                'respuesta_ia' => $content,
                'sugerencias' => array_slice($candidatos, 0, 5),
                'confianza' => 'baja',
            ];
        }

        // Validar que el código sugerido existe
        $codigoValido = $this->validateCabys($parsed['codigo_sugerido'] ?? '');

        return [
            'success' => true,
            'metodo' => 'ia',
            'sugerencias' => [
                [
                    'codigo' => $parsed['codigo_sugerido'] ?? '',
                    'descripcion' => $parsed['descripcion_cabys'] ?? '',
                    'score' => $parsed['confianza'] === 'alta' ? 0.9 : ($parsed['confianza'] === 'media' ? 0.7 : 0.5),
                    'validado' => $codigoValido['valido'] ?? false,
                ],
            ],
            'confianza' => $parsed['confianza'] ?? 'media',
            'razonamiento' => $parsed['razonamiento'] ?? '',
            'alternativas' => $parsed['alternativas'] ?? [],
            'candidatos_db' => array_slice($candidatos, 0, 3),
            'provider' => $result['provider'] ?? 'unknown',
        ];
    }
}

