<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

/**
 * Generador completo de módulos ERP
 * 
 * Genera automáticamente todos los componentes necesarios para un nuevo módulo:
 * - Modelo con traits multi-tenant
 * - Migración de base de datos
 * - Controller API con cache
 * - FormRequests (Store y Update)
 * - Resource para respuestas
 * - Policy RBAC
 * - Factory para testing
 * - Test Feature
 * - Rutas API
 * 
 * @package App\Console\Commands
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class MakeErpModule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:erp-module 
                            {name : Nombre del módulo en PascalCase (ej: OrdenTrabajo)}
                            {--fields= : Campos del modelo en formato campo:tipo,campo2:tipo2}
                            {--relations= : Relaciones en formato modelo:tipo (ej: Cliente:belongsTo,Productos:hasMany)}
                            {--no-migration : No crear migración}
                            {--no-factory : No crear factory}
                            {--no-test : No crear tests}
                            {--no-routes : No agregar rutas automáticamente}
                            {--soft-delete : Incluir soft delete personalizado}
                            {--force : Sobrescribir archivos existentes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera un módulo ERP completo con Model, Controller, Policy, Requests, Resource, Migration, Factory y Tests';

    /**
     * Nombre del módulo
     */
    protected string $moduleName;

    /**
     * Nombre en plural
     */
    protected string $modulePlural;

    /**
     * Nombre de la tabla
     */
    protected string $tableName;

    /**
     * Campos del módulo
     */
    protected array $fields = [];

    /**
     * Relaciones del módulo
     */
    protected array $relations = [];

    /**
     * Archivos generados
     */
    protected array $generatedFiles = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->moduleName = $this->argument('name');
        $this->modulePlural = Str::plural($this->moduleName);
        $this->tableName = Str::snake($this->modulePlural);
        
        $this->parseFields();
        $this->parseRelations();
        
        $this->info("🚀 Generando módulo ERP: {$this->moduleName}");
        $this->newLine();
        
        // Mostrar resumen antes de generar
        $this->showSummary();
        
        if (!$this->confirm('¿Deseas continuar con la generación?', true)) {
            $this->warn('Operación cancelada.');
            return Command::SUCCESS;
        }
        
        $this->newLine();
        
        // Generar todos los componentes
        $this->generateModel();
        $this->generateController();
        $this->generatePolicy();
        $this->generateFormRequests();
        $this->generateResource();
        
        if (!$this->option('no-migration')) {
            $this->generateMigration();
        }
        
        if (!$this->option('no-factory')) {
            $this->generateFactory();
        }
        
        if (!$this->option('no-test')) {
            $this->generateTest();
        }
        
        if (!$this->option('no-routes')) {
            $this->addRoutes();
        }
        
        $this->registerPolicy();
        
        $this->newLine();
        $this->showResults();
        
        return Command::SUCCESS;
    }

    /**
     * Parsear campos del input
     */
    protected function parseFields(): void
    {
        $fieldsOption = $this->option('fields');
        
        if (!$fieldsOption) {
            // Campos por defecto
            $this->fields = [
                'empresa_id' => 'foreignId',
                'nombre' => 'string',
                'descripcion' => 'text:nullable',
                'activo' => 'boolean:default:true',
            ];
            return;
        }
        
        // Siempre incluir empresa_id para multi-tenancy
        $this->fields['empresa_id'] = 'foreignId';
        
        $pairs = explode(',', $fieldsOption);
        foreach ($pairs as $pair) {
            $parts = explode(':', $pair, 2);
            $fieldName = trim($parts[0]);
            $fieldType = trim($parts[1] ?? 'string');
            $this->fields[$fieldName] = $fieldType;
        }
        
        // Siempre agregar activo y eliminado al final
        if (!isset($this->fields['activo'])) {
            $this->fields['activo'] = 'boolean:default:true';
        }
    }

    /**
     * Parsear relaciones del input
     */
    protected function parseRelations(): void
    {
        $relationsOption = $this->option('relations');
        
        if (!$relationsOption) {
            // Relación por defecto con Empresa
            $this->relations = [
                'Empresa' => 'belongsTo',
            ];
            return;
        }
        
        // Siempre incluir Empresa
        $this->relations['Empresa'] = 'belongsTo';
        
        $pairs = explode(',', $relationsOption);
        foreach ($pairs as $pair) {
            $parts = explode(':', $pair, 2);
            $model = trim($parts[0]);
            $type = trim($parts[1] ?? 'belongsTo');
            $this->relations[$model] = $type;
        }
    }

    /**
     * Mostrar resumen antes de generar
     */
    protected function showSummary(): void
    {
        $this->info('📋 Resumen del módulo a generar:');
        $this->table(
            ['Componente', 'Archivo'],
            [
                ['Modelo', "app/Models/{$this->moduleName}.php"],
                ['Controller', "app/Http/Controllers/API/{$this->moduleName}Controller.php"],
                ['Policy', "app/Policies/{$this->moduleName}Policy.php"],
                ['StoreRequest', "app/Http/Requests/{$this->moduleName}/Store{$this->moduleName}Request.php"],
                ['UpdateRequest', "app/Http/Requests/{$this->moduleName}/Update{$this->moduleName}Request.php"],
                ['Resource', "app/Http/Resources/{$this->moduleName}Resource.php"],
                ['Migration', "database/migrations/xxxx_create_{$this->tableName}_table.php"],
                ['Factory', "database/factories/{$this->moduleName}Factory.php"],
                ['Test', "tests/Feature/{$this->moduleName}Test.php"],
            ]
        );
        
        $this->newLine();
        $this->info('📦 Campos:');
        foreach ($this->fields as $field => $type) {
            $this->line("   • {$field}: {$type}");
        }
        
        $this->newLine();
        $this->info('🔗 Relaciones:');
        foreach ($this->relations as $model => $type) {
            $this->line("   • {$model}: {$type}");
        }
    }

    /**
     * Generar el Modelo
     */
    protected function generateModel(): void
    {
        $this->info('📄 Generando Modelo...');
        
        $fillable = $this->generateFillable();
        $casts = $this->generateCasts();
        $relations = $this->generateModelRelations();
        
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo {$this->moduleName}
 * 
 * @package App\Models
 * @author Generado por MakeErpModule
 * @copyright 2025 Sistemas Ursol S.A.
 */
class {$this->moduleName} extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use HasCustomSoftDeletes;
    use HasAuditFields;
    use HasActiveScope;

    /**
     * Tabla asociada al modelo
     */
    protected \$table = '{$this->tableName}';

    /**
     * Nombres personalizados de las marcas de tiempo
     */
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    /**
     * Atributos asignables masivamente
     */
    protected \$fillable = [
{$fillable}
    ];

    /**
     * Conversiones de tipos
     */
    protected \$casts = [
{$casts}
    ];

    /* ===================== Relaciones ===================== */

{$relations}
}

PHP;

        $path = app_path("Models/{$this->moduleName}.php");
        $this->writeFile($path, $content);
    }

    /**
     * Generar el Controller
     */
    protected function generateController(): void
    {
        $this->info('🎮 Generando Controller...');
        
        $modelLower = Str::camel($this->moduleName);
        $permissionSlug = Str::kebab($this->modulePlural);
        
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\\{$this->moduleName};
use App\Http\Requests\\{$this->moduleName}\Store{$this->moduleName}Request;
use App\Http\Requests\\{$this->moduleName}\Update{$this->moduleName}Request;
use App\Http\Resources\\{$this->moduleName}Resource;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Controller para {$this->moduleName}
 * 
 * @package App\Http\Controllers\API
 * @author Generado por MakeErpModule
 * @copyright 2025 Sistemas Ursol S.A.
 */
#[OA\Tag(
    name: '{$this->modulePlural}',
    description: 'Gestión de {$this->modulePlural}'
)]
class {$this->moduleName}Controller extends Controller
{
    use HasCacheableQueries;

    /**
     * Tags de cache
     */
    protected array \$cacheTags = ['{$permissionSlug}'];

    /**
     * TTL del cache en segundos (1 hora)
     */
    protected int \$cacheTTL = 3600;

    /**
     * Listar todos los registros
     */
    #[OA\Get(
        path: '/api/{$permissionSlug}',
        operationId: 'get{$this->modulePlural}',
        summary: 'Listar {$this->modulePlural}',
        security: [['sanctum' => []]],
        tags: ['{$this->modulePlural}'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'activo', in: 'query', schema: new OA\Schema(type: 'boolean'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de {$this->modulePlural}'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function index(Request \$request): AnonymousResourceCollection
    {
        \$this->authorize('viewAny', {$this->moduleName}::class);

        return \$this->cacheQueryIfEnabled(\$request, function () use (\$request) {
            \$query = {$this->moduleName}::query()
                ->where('eliminado', false);

            // Filtro por empresa (multi-tenancy)
            if (\$empresaId = \$request->user()?->empresa_id) {
                \$query->where('empresa_id', \$empresaId);
            }

            // Búsqueda
            if (\$search = \$request->input('search')) {
                \$query->where('nombre', 'like', "%{\$search}%");
            }

            // Filtro por estado activo
            if (\$request->has('activo')) {
                \$query->where('activo', \$request->boolean('activo'));
            }

            \$perPage = \$request->input('per_page', 15);
            
            return {$this->moduleName}Resource::collection(
                \$query->orderBy('id', 'desc')->paginate(\$perPage)
            );
        });
    }

    /**
     * Crear nuevo registro
     */
    #[OA\Post(
        path: '/api/{$permissionSlug}',
        operationId: 'create{$this->moduleName}',
        summary: 'Crear {$this->moduleName}',
        security: [['sanctum' => []]],
        tags: ['{$this->modulePlural}'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/{$this->moduleName}Request')
        ),
        responses: [
            new OA\Response(response: 201, description: '{$this->moduleName} creado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function store(Store{$this->moduleName}Request \$request): JsonResponse
    {
        \$this->authorize('create', {$this->moduleName}::class);

        \$data = \$request->validated();
        
        // Asignar empresa del usuario si no se especifica
        if (!isset(\$data['empresa_id'])) {
            \$data['empresa_id'] = \$request->user()->empresa_id;
        }
        
        // Campos de auditoría
        \$data['creado_por'] = \$request->user()->id;

        \${$modelLower} = {$this->moduleName}::create(\$data);
        
        \$this->flushCache();

        return response()->json([
            'message' => '{$this->moduleName} creado exitosamente',
            'data' => new {$this->moduleName}Resource(\${$modelLower}),
        ], 201);
    }

    /**
     * Mostrar un registro específico
     */
    #[OA\Get(
        path: '/api/{$permissionSlug}/{id}',
        operationId: 'get{$this->moduleName}',
        summary: 'Obtener {$this->moduleName}',
        security: [['sanctum' => []]],
        tags: ['{$this->modulePlural}'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: '{$this->moduleName} encontrado'),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
    public function show(int \$id): JsonResponse
    {
        \${$modelLower} = {$this->moduleName}::where('eliminado', false)->findOrFail(\$id);
        
        \$this->authorize('view', \${$modelLower});

        return response()->json([
            'data' => new {$this->moduleName}Resource(\${$modelLower}),
        ]);
    }

    /**
     * Actualizar un registro
     */
    #[OA\Put(
        path: '/api/{$permissionSlug}/{id}',
        operationId: 'update{$this->moduleName}',
        summary: 'Actualizar {$this->moduleName}',
        security: [['sanctum' => []]],
        tags: ['{$this->modulePlural}'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: '{$this->moduleName} actualizado'),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
    public function update(Update{$this->moduleName}Request \$request, int \$id): JsonResponse
    {
        \${$modelLower} = {$this->moduleName}::where('eliminado', false)->findOrFail(\$id);
        
        \$this->authorize('update', \${$modelLower});

        \$data = \$request->validated();
        \$data['actualizado_por'] = \$request->user()->id;

        \${$modelLower}->update(\$data);
        
        \$this->flushCache();

        return response()->json([
            'message' => '{$this->moduleName} actualizado exitosamente',
            'data' => new {$this->moduleName}Resource(\${$modelLower}->fresh()),
        ]);
    }

    /**
     * Eliminar un registro (soft delete)
     */
    #[OA\Delete(
        path: '/api/{$permissionSlug}/{id}',
        operationId: 'delete{$this->moduleName}',
        summary: 'Eliminar {$this->moduleName}',
        security: [['sanctum' => []]],
        tags: ['{$this->modulePlural}'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: '{$this->moduleName} eliminado'),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
    public function destroy(int \$id): JsonResponse
    {
        \${$modelLower} = {$this->moduleName}::where('eliminado', false)->findOrFail(\$id);
        
        \$this->authorize('delete', \${$modelLower});

        \${$modelLower}->update([
            'eliminado' => true,
            'actualizado_por' => request()->user()->id,
        ]);
        
        \$this->flushCache();

        return response()->json([
            'message' => '{$this->moduleName} eliminado exitosamente',
        ]);
    }
}

PHP;

        $path = app_path("Http/Controllers/API/{$this->moduleName}Controller.php");
        $this->writeFile($path, $content);
    }

    /**
     * Generar Policy
     */
    protected function generatePolicy(): void
    {
        $this->info('🔐 Generando Policy...');
        
        $permissionSlug = Str::kebab($this->modulePlural);
        
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Usuario;
use App\Models\\{$this->moduleName};

/**
 * Policy para {$this->moduleName}
 * 
 * @package App\Policies
 * @author Generado por MakeErpModule
 * @copyright 2025 Sistemas Ursol S.A.
 */
class {$this->moduleName}Policy extends BasePolicy
{
    /**
     * Prefijo del permiso para este módulo
     */
    protected string \$permission = '{$permissionSlug}';
}

PHP;

        $path = app_path("Policies/{$this->moduleName}Policy.php");
        $this->writeFile($path, $content);
    }

    /**
     * Generar FormRequests
     */
    protected function generateFormRequests(): void
    {
        $this->info('📝 Generando FormRequests...');
        
        $rules = $this->generateValidationRules();
        $messages = $this->generateValidationMessages();
        
        // Crear directorio si no existe
        $dir = app_path("Http/Requests/{$this->moduleName}");
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        
        // Store Request
        $storeContent = <<<PHP
<?php

declare(strict_types=1);

namespace App\Http\Requests\\{$this->moduleName};

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para crear {$this->moduleName}
 * 
 * @package App\Http\Requests\\{$this->moduleName}
 * @author Generado por MakeErpModule
 * @copyright 2025 Sistemas Ursol S.A.
 */
class Store{$this->moduleName}Request extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación
     * 
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
{$rules}
        ];
    }

    /**
     * Mensajes de error personalizados
     * 
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
{$messages}
        ];
    }
}

PHP;

        $storePath = app_path("Http/Requests/{$this->moduleName}/Store{$this->moduleName}Request.php");
        $this->writeFile($storePath, $storeContent);
        
        // Update Request (similar pero campos opcionales)
        $updateRules = $this->generateValidationRules(true);
        
        $updateContent = <<<PHP
<?php

declare(strict_types=1);

namespace App\Http\Requests\\{$this->moduleName};

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para actualizar {$this->moduleName}
 * 
 * @package App\Http\Requests\\{$this->moduleName}
 * @author Generado por MakeErpModule
 * @copyright 2025 Sistemas Ursol S.A.
 */
class Update{$this->moduleName}Request extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación
     * 
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
{$updateRules}
        ];
    }

    /**
     * Mensajes de error personalizados
     * 
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
{$messages}
        ];
    }
}

PHP;

        $updatePath = app_path("Http/Requests/{$this->moduleName}/Update{$this->moduleName}Request.php");
        $this->writeFile($updatePath, $updateContent);
    }

    /**
     * Generar Resource
     */
    protected function generateResource(): void
    {
        $this->info('📦 Generando Resource...');
        
        $resourceFields = $this->generateResourceFields();
        
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para {$this->moduleName}
 * 
 * @package App\Http\Resources
 * @author Generado por MakeErpModule
 * @copyright 2025 Sistemas Ursol S.A.
 */
class {$this->moduleName}Resource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request \$request): array
    {
        return [
            'id' => \$this->id,
{$resourceFields}
            
            // Timestamps
            'creado_en' => \$this->creado_en?->toISOString(),
            'actualizado_en' => \$this->actualizado_en?->toISOString(),
            'creado_por' => \$this->creado_por,
            'actualizado_por' => \$this->actualizado_por,
        ];
    }
}

PHP;

        $path = app_path("Http/Resources/{$this->moduleName}Resource.php");
        $this->writeFile($path, $content);
    }

    /**
     * Generar Migration
     */
    protected function generateMigration(): void
    {
        $this->info('🗄️ Generando Migration...');
        
        $migrationFields = $this->generateMigrationFields();
        $timestamp = date('Y_m_d_His');
        
        $content = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration para tabla {$this->tableName}
 * 
 * @author Generado por MakeErpModule
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{$this->tableName}', function (Blueprint \$table) {
            \$table->id();
            
{$migrationFields}
            
            // Campos de auditoría
            \$table->unsignedBigInteger('creado_por')->nullable();
            \$table->unsignedBigInteger('actualizado_por')->nullable();
            \$table->timestamp('creado_en')->nullable();
            \$table->timestamp('actualizado_en')->nullable();
            \$table->boolean('eliminado')->default(false);
            
            // Índices
            \$table->index('empresa_id');
            \$table->index('activo');
            \$table->index('eliminado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$this->tableName}');
    }
};

PHP;

        $path = database_path("migrations/{$timestamp}_create_{$this->tableName}_table.php");
        $this->writeFile($path, $content);
    }

    /**
     * Generar Factory
     */
    protected function generateFactory(): void
    {
        $this->info('🏭 Generando Factory...');
        
        $factoryFields = $this->generateFactoryFields();
        
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\\{$this->moduleName};
use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para {$this->moduleName}
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\\{$this->moduleName}>
 * @author Generado por MakeErpModule
 */
class {$this->moduleName}Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected \$model = {$this->moduleName}::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
{$factoryFields}
            'activo' => true,
            'eliminado' => false,
            'creado_por' => Usuario::factory(),
        ];
    }

    /**
     * Estado: inactivo
     */
    public function inactivo(): static
    {
        return \$this->state(fn (array \$attributes) => [
            'activo' => false,
        ]);
    }

    /**
     * Estado: eliminado (soft delete)
     */
    public function eliminado(): static
    {
        return \$this->state(fn (array \$attributes) => [
            'eliminado' => true,
        ]);
    }
}

PHP;

        $path = database_path("factories/{$this->moduleName}Factory.php");
        $this->writeFile($path, $content);
    }

    /**
     * Generar Test
     */
    protected function generateTest(): void
    {
        $this->info('🧪 Generando Test...');
        
        $permissionSlug = Str::kebab($this->modulePlural);
        $modelLower = Str::camel($this->moduleName);
        
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\\{$this->moduleName};
use App\Models\Usuario;
use App\Models\Empresa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests para {$this->moduleName}Controller
 * 
 * @author Generado por MakeErpModule
 */
class {$this->moduleName}Test extends TestCase
{
    use RefreshDatabase;

    protected Usuario \$user;
    protected Empresa \$empresa;

    protected function setUp(): void
    {
        parent::setUp();
        
        \$this->seed(\Database\Seeders\PermisosSeeder::class);
        \$this->seed(\Database\Seeders\RolesSeeder::class);
        
        \$this->empresa = Empresa::factory()->create();
        \$this->user = Usuario::factory()->create([
            'empresa_id' => \$this->empresa->id,
        ]);
        
        // Asignar permisos
        \$this->assignPermissions(\$this->user, [
            'ver-{$permissionSlug}',
            'crear-{$permissionSlug}',
            'editar-{$permissionSlug}',
            'eliminar-{$permissionSlug}',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function puede_listar_{$permissionSlug}(): void
    {
        {$this->moduleName}::factory()->count(3)->create([
            'empresa_id' => \$this->empresa->id,
        ]);

        \$response = \$this->actingAs(\$this->user)
            ->getJson('/api/{$permissionSlug}');

        \$response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'nombre', 'activo', 'creado_en'],
                ],
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function puede_crear_{$modelLower}(): void
    {
        \$data = [
            'empresa_id' => \$this->empresa->id,
            'nombre' => 'Test {$this->moduleName}',
            'activo' => true,
        ];

        \$response = \$this->actingAs(\$this->user)
            ->postJson('/api/{$permissionSlug}', \$data);

        \$response->assertCreated()
            ->assertJsonPath('data.nombre', 'Test {$this->moduleName}');

        \$this->assertDatabaseHas('{$this->tableName}', [
            'nombre' => 'Test {$this->moduleName}',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function puede_ver_{$modelLower}(): void
    {
        \${$modelLower} = {$this->moduleName}::factory()->create([
            'empresa_id' => \$this->empresa->id,
        ]);

        \$response = \$this->actingAs(\$this->user)
            ->getJson("/api/{$permissionSlug}/{\${$modelLower}->id}");

        \$response->assertOk()
            ->assertJsonPath('data.id', \${$modelLower}->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function puede_actualizar_{$modelLower}(): void
    {
        \${$modelLower} = {$this->moduleName}::factory()->create([
            'empresa_id' => \$this->empresa->id,
        ]);

        \$response = \$this->actingAs(\$this->user)
            ->putJson("/api/{$permissionSlug}/{\${$modelLower}->id}", [
                'nombre' => 'Nombre Actualizado',
            ]);

        \$response->assertOk()
            ->assertJsonPath('data.nombre', 'Nombre Actualizado');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function puede_eliminar_{$modelLower}(): void
    {
        \${$modelLower} = {$this->moduleName}::factory()->create([
            'empresa_id' => \$this->empresa->id,
        ]);

        \$response = \$this->actingAs(\$this->user)
            ->deleteJson("/api/{$permissionSlug}/{\${$modelLower}->id}");

        \$response->assertOk();
        
        \$this->assertDatabaseHas('{$this->tableName}', [
            'id' => \${$modelLower}->id,
            'eliminado' => true,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function requiere_autenticacion(): void
    {
        \$response = \$this->getJson('/api/{$permissionSlug}');

        \$response->assertUnauthorized();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function no_puede_ver_{$modelLower}_de_otra_empresa(): void
    {
        \$otraEmpresa = Empresa::factory()->create();
        \${$modelLower} = {$this->moduleName}::factory()->create([
            'empresa_id' => \$otraEmpresa->id,
        ]);

        \$response = \$this->actingAs(\$this->user)
            ->getJson("/api/{$permissionSlug}/{\${$modelLower}->id}");

        \$response->assertForbidden();
    }
}

PHP;

        $path = base_path("tests/Feature/{$this->moduleName}Test.php");
        $this->writeFile($path, $content);
    }

    /**
     * Agregar rutas a api.php
     */
    protected function addRoutes(): void
    {
        $this->info('🛣️ Agregando rutas...');
        
        $permissionSlug = Str::kebab($this->modulePlural);
        
        $routeCode = <<<PHP

    // {$this->moduleName} - Generado por MakeErpModule
    Route::apiResource('{$permissionSlug}', \App\Http\Controllers\API\\{$this->moduleName}Controller::class);
PHP;

        $apiPath = base_path('routes/api.php');
        $content = File::get($apiPath);
        
        // Buscar el final del grupo de rutas autenticadas
        if (str_contains($content, "// === FIN RUTAS GENERADAS ===")) {
            $content = str_replace(
                "// === FIN RUTAS GENERADAS ===",
                $routeCode . "\n\n    // === FIN RUTAS GENERADAS ===",
                $content
            );
        } else {
            // Si no existe el marcador, agregar antes del último });
            $content = preg_replace(
                '/(\}\)->middleware\(\[\'auth:sanctum\'\]\);)\s*$/',
                $routeCode . "\n$1",
                $content
            );
            
            // Si eso tampoco funciona, agregar al final
            if (!str_contains($content, $routeCode)) {
                $content .= "\n" . $routeCode;
            }
        }
        
        File::put($apiPath, $content);
        $this->generatedFiles[] = $apiPath . ' (modificado)';
    }

    /**
     * Registrar Policy en AuthServiceProvider
     */
    protected function registerPolicy(): void
    {
        $this->info('📋 Nota: Registrar policy en AuthServiceProvider...');
        
        $this->warn("   Agregar manualmente a app/Providers/AuthServiceProvider.php:");
        $this->line("   {$this->moduleName}::class => {$this->moduleName}Policy::class,");
    }

    /**
     * Generar fillable para el modelo
     */
    protected function generateFillable(): string
    {
        $lines = [];
        foreach (array_keys($this->fields) as $field) {
            $lines[] = "        '{$field}',";
        }
        $lines[] = "        'eliminado',";
        return implode("\n", $lines);
    }

    /**
     * Generar casts para el modelo
     */
    protected function generateCasts(): string
    {
        $casts = [];
        $addedFields = [];
        
        foreach ($this->fields as $field => $type) {
            $baseType = explode(':', $type)[0];
            
            $castType = match ($baseType) {
                'boolean' => 'boolean',
                'integer', 'bigInteger', 'unsignedBigInteger' => 'integer',
                'decimal', 'float', 'double' => 'decimal:2',
                'date' => 'date',
                'datetime', 'timestamp' => 'datetime',
                'json', 'array' => 'array',
                default => null,
            };
            
            if ($castType) {
                $casts[] = "        '{$field}' => '{$castType}',";
                $addedFields[] = $field;
            }
        }
        
        // Solo agregar si no se agregaron antes
        if (!in_array('activo', $addedFields)) {
            $casts[] = "        'activo' => 'boolean',";
        }
        if (!in_array('eliminado', $addedFields)) {
            $casts[] = "        'eliminado' => 'boolean',";
        }
        $casts[] = "        'creado_en' => 'datetime',";
        $casts[] = "        'actualizado_en' => 'datetime',";
        
        return implode("\n", $casts);
    }

    /**
     * Generar relaciones para el modelo
     */
    protected function generateModelRelations(): string
    {
        $relations = [];
        
        foreach ($this->relations as $model => $type) {
            $methodName = Str::camel($model);
            $returnType = match ($type) {
                'belongsTo' => 'BelongsTo',
                'hasMany' => 'HasMany',
                'hasOne' => 'HasOne',
                'belongsToMany' => 'BelongsToMany',
                default => 'BelongsTo',
            };
            
            $foreignKey = $type === 'belongsTo' ? Str::snake($model) . '_id' : null;
            $foreignKeyParam = $foreignKey ? ", '{$foreignKey}'" : '';
            
            $relations[] = <<<PHP
    public function {$methodName}(): \Illuminate\Database\Eloquent\Relations\\{$returnType}
    {
        return \$this->{$type}({$model}::class{$foreignKeyParam});
    }
PHP;
        }
        
        return implode("\n\n", $relations);
    }

    /**
     * Generar reglas de validación
     */
    protected function generateValidationRules(bool $forUpdate = false): string
    {
        $rules = [];
        
        foreach ($this->fields as $field => $type) {
            if ($field === 'empresa_id') {
                $required = $forUpdate ? 'nullable' : 'required';
                $rules[] = "            'empresa_id' => ['{$required}', 'exists:empresas,id'],";
                continue;
            }
            
            if ($field === 'activo' || $field === 'eliminado') {
                $rules[] = "            '{$field}' => ['boolean'],";
                continue;
            }
            
            $parts = explode(':', $type);
            $baseType = $parts[0];
            $isNullable = in_array('nullable', $parts);
            
            $required = $forUpdate ? 'sometimes' : ($isNullable ? 'nullable' : 'required');
            
            $typeRule = match ($baseType) {
                'string' => 'string', 'max:255',
                'text' => 'string',
                'integer', 'bigInteger', 'unsignedBigInteger' => 'integer',
                'decimal', 'float', 'double' => 'numeric',
                'boolean' => 'boolean',
                'date' => 'date',
                'datetime', 'timestamp' => 'date',
                'email' => 'email',
                'foreignId' => 'integer',
                default => 'string',
            };
            
            $rules[] = "            '{$field}' => ['{$required}', '{$typeRule}'],";
        }
        
        return implode("\n", $rules);
    }

    /**
     * Generar mensajes de validación
     */
    protected function generateValidationMessages(): string
    {
        $messages = [];
        
        foreach ($this->fields as $field => $type) {
            if (in_array($field, ['empresa_id', 'activo', 'eliminado'])) {
                continue;
            }
            
            $fieldLabel = Str::title(str_replace('_', ' ', $field));
            $messages[] = "            '{$field}.required' => 'El campo {$fieldLabel} es obligatorio',";
        }
        
        return implode("\n", $messages);
    }

    /**
     * Generar campos para Resource
     */
    protected function generateResourceFields(): string
    {
        $fields = [];
        
        foreach ($this->fields as $field => $type) {
            $baseType = explode(':', $type)[0];
            
            $cast = match ($baseType) {
                'boolean' => '(bool) ',
                'integer', 'bigInteger', 'unsignedBigInteger' => '(int) ',
                'decimal', 'float', 'double' => '(float) ',
                default => '',
            };
            
            $fields[] = "            '{$field}' => {$cast}\$this->{$field},";
        }
        
        // Agregar relaciones
        foreach ($this->relations as $model => $type) {
            if ($model === 'Empresa') {
                continue; // Empresa ya está en empresa_id
            }
            
            $methodName = Str::camel($model);
            $fields[] = "            '{$methodName}' => \$this->whenLoaded('{$methodName}'),";
        }
        
        return implode("\n", $fields);
    }

    /**
     * Generar campos para Migration
     */
    protected function generateMigrationFields(): string
    {
        $fields = [];
        
        foreach ($this->fields as $field => $type) {
            $parts = explode(':', $type);
            $baseType = $parts[0];
            $modifiers = array_slice($parts, 1);
            
            $line = match ($baseType) {
                'foreignId' => "\$table->foreignId('{$field}')->constrained()->onDelete('cascade')",
                'string' => "\$table->string('{$field}')",
                'text' => "\$table->text('{$field}')",
                'integer' => "\$table->integer('{$field}')",
                'bigInteger' => "\$table->bigInteger('{$field}')",
                'unsignedBigInteger' => "\$table->unsignedBigInteger('{$field}')",
                'decimal' => "\$table->decimal('{$field}', 15, 2)",
                'float' => "\$table->float('{$field}')",
                'double' => "\$table->double('{$field}')",
                'boolean' => "\$table->boolean('{$field}')",
                'date' => "\$table->date('{$field}')",
                'datetime' => "\$table->dateTime('{$field}')",
                'timestamp' => "\$table->timestamp('{$field}')",
                'json' => "\$table->json('{$field}')",
                'email' => "\$table->string('{$field}')",
                default => "\$table->string('{$field}')",
            };
            
            // Aplicar modificadores
            if (in_array('nullable', $modifiers)) {
                $line .= '->nullable()';
            }
            
            foreach ($modifiers as $mod) {
                if (str_starts_with($mod, 'default')) {
                    $defaultParts = explode(':', $mod);
                    $defaultValue = $defaultParts[1] ?? 'null';
                    if ($defaultValue === 'true') {
                        $line .= '->default(true)';
                    } elseif ($defaultValue === 'false') {
                        $line .= '->default(false)';
                    } elseif (is_numeric($defaultValue)) {
                        $line .= "->default({$defaultValue})";
                    } else {
                        $line .= "->default('{$defaultValue}')";
                    }
                }
            }
            
            $fields[] = "            {$line};";
        }
        
        return implode("\n", $fields);
    }

    /**
     * Generar campos para Factory
     */
    protected function generateFactoryFields(): string
    {
        $fields = [];
        
        foreach ($this->fields as $field => $type) {
            if (in_array($field, ['empresa_id', 'activo', 'eliminado'])) {
                continue;
            }
            
            $parts = explode(':', $type);
            $baseType = $parts[0];
            
            $fakerCall = match ($baseType) {
                'string' => $field === 'nombre' ? "fake()->words(3, true)" : "fake()->sentence()",
                'text' => "fake()->paragraph()",
                'integer', 'bigInteger', 'unsignedBigInteger' => "fake()->numberBetween(1, 100)",
                'decimal', 'float', 'double' => "fake()->randomFloat(2, 0, 10000)",
                'boolean' => "fake()->boolean()",
                'date' => "fake()->date()",
                'datetime', 'timestamp' => "fake()->dateTime()",
                'email' => "fake()->unique()->safeEmail()",
                'foreignId' => "null", // Se debe especificar relación
                default => "fake()->word()",
            };
            
            $fields[] = "            '{$field}' => {$fakerCall},";
        }
        
        return implode("\n", $fields);
    }

    /**
     * Escribir archivo verificando si existe
     */
    protected function writeFile(string $path, string $content): void
    {
        if (File::exists($path) && !$this->option('force')) {
            $this->warn("   ⚠️ Archivo ya existe: {$path}");
            
            if (!$this->confirm("¿Sobrescribir {$path}?", false)) {
                return;
            }
        }
        
        // Asegurar que el directorio existe
        $dir = dirname($path);
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        
        File::put($path, $content);
        $this->generatedFiles[] = $path;
        $this->line("   ✅ Creado: {$path}");
    }

    /**
     * Mostrar resultados finales
     */
    protected function showResults(): void
    {
        $this->info('🎉 ¡Módulo generado exitosamente!');
        $this->newLine();
        
        $this->info('📝 Archivos generados:');
        foreach ($this->generatedFiles as $file) {
            $this->line("   • {$file}");
        }
        
        $this->newLine();
        $this->info('📋 Pasos siguientes:');
        $this->line('   1. Ejecutar: php artisan migrate');
        $this->line("   2. Registrar policy en AuthServiceProvider:");
        $this->line("      {$this->moduleName}::class => {$this->moduleName}Policy::class,");
        $this->line("   3. Agregar permisos al seeder (PermisosSeeder):");
        $permissionSlug = Str::kebab($this->modulePlural);
        $this->line("      'ver-{$permissionSlug}', 'crear-{$permissionSlug}', 'editar-{$permissionSlug}', 'eliminar-{$permissionSlug}'");
        $this->line('   4. Ejecutar: php artisan test');
        $this->line('   5. Revisar y ajustar validaciones en FormRequests');
        $this->newLine();
        
        $this->info("🚀 Endpoints disponibles:");
        $this->line("   GET    /api/{$permissionSlug}");
        $this->line("   POST   /api/{$permissionSlug}");
        $this->line("   GET    /api/{$permissionSlug}/{id}");
        $this->line("   PUT    /api/{$permissionSlug}/{id}");
        $this->line("   DELETE /api/{$permissionSlug}/{id}");
    }
}

