<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Usuario;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\Permiso;
use App\Models\Sucursal;
use App\Models\FormaPago;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Sembrar datos esenciales para evitar errores de foreign key
        $this->seedEssentialData();
        
        // Limpiar cache de Redis antes de cada test para evitar conflictos
        // con datos de tests anteriores
        if (config('cache.default') === 'redis') {
            \Illuminate\Support\Facades\Cache::flush();
        }
    }

    /**
     * Sembrar datos esenciales para tests
     */
    protected function seedEssentialData(): void
    {
        // Crear regímenes tributarios si no existen
        if (\App\Models\RegimenTributario::count() === 0) {
            \App\Models\RegimenTributario::create([
                'codigo' => '01',
                'nombre' => 'Régimen General',
                'descripcion' => 'Régimen General de Tributación'
            ]);
            \App\Models\RegimenTributario::create([
                'codigo' => '02',
                'nombre' => 'Régimen Simplificado',
                'descripcion' => 'Régimen Simplificado de Tributación'
            ]);
        }
    }

    /**
     * Crea una empresa de prueba
     */
    protected function createEmpresa(array $attributes = []): Empresa
    {
        // Crear régimen tributario si no existe (buscar por código único)
        $regimen = \App\Models\RegimenTributario::firstOrCreate(
            ['codigo' => '01'],
            [
                'nombre' => 'Régimen General',
                'descripcion' => 'Régimen General de Tributación'
            ]
        );

        return Empresa::create(array_merge([
            'nombre' => 'Empresa Test',
            'nombre_comercial' => 'Test Corp',
            'razon_social' => 'Empresa Test S.A.',
            'num_identificacion_dgt' => '3-101-' . rand(100000, 999999),
            'tipo_identificacion' => '02',
            'direccion' => 'San José, Costa Rica',
            'provincia' => '1',
            'canton' => '01',
            'distrito' => '01',
            'telefono' => '+506 2222-3333',
            'email' => 'test@empresa.com',
            'moneda_defecto' => 'CRC',
            'regimen_tributario_id' => $regimen->id,
            'activo' => true,
            'eliminado' => false,
        ], $attributes));
    }

    /**
     * Crea una sucursal de prueba
     */
    protected function createSucursal(Empresa $empresa = null, array $attributes = []): Sucursal
    {
        if (!$empresa) {
            $empresa = Empresa::first() ?? $this->createEmpresa();
        }

        return Sucursal::create(array_merge([
            'empresa_id' => $empresa->id,
            'nombre' => 'Sucursal Principal',
            'direccion' => 'San José, Costa Rica',
            'telefono' => '+506 2222-3333',
            'email' => 'sucursal@empresa.com',
            'activo' => true,
            'eliminado' => false,
        ], $attributes));
    }

    /**
     * Crea o retorna una forma de pago de prueba
     */
    protected function getFormaPago(): FormaPago
    {
        return FormaPago::firstOrCreate(
            ['codigo_dgt' => '01'],
            [
                'nombre' => 'Efectivo',
                'descripcion' => 'Pago en efectivo',
                'activo' => true,
                'eliminado' => false,
            ]
        );
    }

    /**
     * Crea un usuario de prueba con roles y permisos opcionales
     */
    protected function createUsuario(array $attributes = [], array $roles = []): Usuario
    {
        $empresa = Empresa::first() ?? $this->createEmpresa();

        $usuario = Usuario::create(array_merge([
            'nombre' => 'Usuario',
            'apellidos' => 'Test',
            'email' => 'test' . rand(1000, 9999) . '@test.com',
            'password_hash' => bcrypt('password123'),
            'empresa_id' => $empresa->id,
            'activo' => true,
            'eliminado' => false,
        ], $attributes));

        // Asignar roles si se proporcionan
        if (!empty($roles)) {
            foreach ($roles as $roleName) {
                $rol = Rol::where('nombre', $roleName)->first();
                if ($rol) {
                    $usuario->roles()->attach($rol->id);
                }
            }
        }

        return $usuario;
    }

    /**
     * Crea un usuario administrador con todos los permisos
     */
    protected function createAdminUsuario(array $attributes = []): Usuario
    {
        $usuario = $this->createUsuario($attributes, ['Administrador']);
        return $usuario;
    }

    /**
     * Autentica un usuario y retorna el token
     */
    protected function actingAsUsuario(Usuario $usuario = null): string
    {
        if (!$usuario) {
            $usuario = $this->createAdminUsuario();
        }

        $token = $usuario->createToken('test-token')->plainTextToken;
        
        return $token;
    }

    /**
     * Helper para hacer peticiones autenticadas
     */
    protected function authenticatedJson(string $method, string $uri, array $data = [], Usuario $usuario = null)
    {
        $token = $this->actingAsUsuario($usuario);
        
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->json($method, $uri, $data);
    }

    /**
     * Crea roles básicos para testing
     */
    protected function seedRoles(): void
    {
        $roles = [
            ['nombre' => 'Administrador', 'descripcion' => 'Acceso total al sistema'],
            ['nombre' => 'Gerente', 'descripcion' => 'Acceso a gestión y reportes'],
            ['nombre' => 'Vendedor', 'descripcion' => 'Acceso a ventas y clientes'],
            ['nombre' => 'Bodeguero', 'descripcion' => 'Acceso a inventario'],
        ];

        foreach ($roles as $rol) {
            Rol::create($rol);
        }
    }

    /**
     * Crea permisos básicos para testing
     */
    protected function seedPermisos(): void
    {
        $permisos = [
            // Empresas
            ['nombre' => 'Ver Empresas', 'slug' => 'ver-empresas', 'modulo' => 'Empresas'],
            ['nombre' => 'Crear Empresas', 'slug' => 'crear-empresas', 'modulo' => 'Empresas'],
            ['nombre' => 'Editar Empresas', 'slug' => 'editar-empresas', 'modulo' => 'Empresas'],
            ['nombre' => 'Eliminar Empresas', 'slug' => 'eliminar-empresas', 'modulo' => 'Empresas'],
            // Productos
            ['nombre' => 'Ver Productos', 'slug' => 'ver-productos', 'modulo' => 'Productos'],
            ['nombre' => 'Crear Productos', 'slug' => 'crear-productos', 'modulo' => 'Productos'],
            ['nombre' => 'Editar Productos', 'slug' => 'editar-productos', 'modulo' => 'Productos'],
            ['nombre' => 'Eliminar Productos', 'slug' => 'eliminar-productos', 'modulo' => 'Productos'],
            // Clientes
            ['nombre' => 'Ver Clientes', 'slug' => 'ver-clientes', 'modulo' => 'Clientes'],
            ['nombre' => 'Crear Clientes', 'slug' => 'crear-clientes', 'modulo' => 'Clientes'],
            ['nombre' => 'Editar Clientes', 'slug' => 'editar-clientes', 'modulo' => 'Clientes'],
            ['nombre' => 'Eliminar Clientes', 'slug' => 'eliminar-clientes', 'modulo' => 'Clientes'],
            // Ventas
            ['nombre' => 'Ver Ventas', 'slug' => 'ver-ventas', 'modulo' => 'Ventas'],
            ['nombre' => 'Crear Ventas', 'slug' => 'crear-ventas', 'modulo' => 'Ventas'],
            ['nombre' => 'Editar Ventas', 'slug' => 'editar-ventas', 'modulo' => 'Ventas'],
            ['nombre' => 'Eliminar Ventas', 'slug' => 'eliminar-ventas', 'modulo' => 'Ventas'],
            // Roles y Permisos
            ['nombre' => 'Ver Permisos', 'slug' => 'ver-permisos', 'modulo' => 'Roles y Permisos'],
            ['nombre' => 'Crear Permisos', 'slug' => 'crear-permisos', 'modulo' => 'Roles y Permisos'],
            ['nombre' => 'Editar Permisos', 'slug' => 'editar-permisos', 'modulo' => 'Roles y Permisos'],
            ['nombre' => 'Eliminar Permisos', 'slug' => 'eliminar-permisos', 'modulo' => 'Roles y Permisos'],
            ['nombre' => 'Ver Roles', 'slug' => 'ver-roles', 'modulo' => 'Roles y Permisos'],
            ['nombre' => 'Crear Roles', 'slug' => 'crear-roles', 'modulo' => 'Roles y Permisos'],
            ['nombre' => 'Editar Roles', 'slug' => 'editar-roles', 'modulo' => 'Roles y Permisos'],
            ['nombre' => 'Eliminar Roles', 'slug' => 'eliminar-roles', 'modulo' => 'Roles y Permisos'],
            // Declaraciones Tributarias (con guiones bajos)
            ['nombre' => 'Ver Declaraciones Tributarias', 'slug' => 'ver-declaraciones_tributarias', 'modulo' => 'Tributación'],
            ['nombre' => 'Crear Declaraciones Tributarias', 'slug' => 'crear-declaraciones_tributarias', 'modulo' => 'Tributación'],
            ['nombre' => 'Editar Declaraciones Tributarias', 'slug' => 'editar-declaraciones_tributarias', 'modulo' => 'Tributación'],
            ['nombre' => 'Eliminar Declaraciones Tributarias', 'slug' => 'eliminar-declaraciones_tributarias', 'modulo' => 'Tributación'],
            // Cuentas Bancarias (con guiones bajos)
            ['nombre' => 'Ver Cuentas Bancarias', 'slug' => 'ver-cuentas_bancarias', 'modulo' => 'Banca'],
            ['nombre' => 'Crear Cuentas Bancarias', 'slug' => 'crear-cuentas_bancarias', 'modulo' => 'Banca'],
            ['nombre' => 'Editar Cuentas Bancarias', 'slug' => 'editar-cuentas_bancarias', 'modulo' => 'Banca'],
            ['nombre' => 'Eliminar Cuentas Bancarias', 'slug' => 'eliminar-cuentas_bancarias', 'modulo' => 'Banca'],
            // Movimientos Bancarios (con guiones bajos)
            ['nombre' => 'Ver Movimientos Bancarios', 'slug' => 'ver-movimientos_bancarios', 'modulo' => 'Banca'],
            ['nombre' => 'Crear Movimientos Bancarios', 'slug' => 'crear-movimientos_bancarios', 'modulo' => 'Banca'],
            ['nombre' => 'Editar Movimientos Bancarios', 'slug' => 'editar-movimientos_bancarios', 'modulo' => 'Banca'],
            ['nombre' => 'Eliminar Movimientos Bancarios', 'slug' => 'eliminar-movimientos_bancarios', 'modulo' => 'Banca'],
            // Retenciones de Impuesto (con guiones bajos)
            ['nombre' => 'Ver Retenciones Impuesto', 'slug' => 'ver-retenciones_impuestos', 'modulo' => 'Tributación'],
            ['nombre' => 'Crear Retenciones Impuesto', 'slug' => 'crear-retenciones_impuestos', 'modulo' => 'Tributación'],
            ['nombre' => 'Editar Retenciones Impuesto', 'slug' => 'editar-retenciones_impuestos', 'modulo' => 'Tributación'],
            ['nombre' => 'Eliminar Retenciones Impuesto', 'slug' => 'eliminar-retenciones_impuestos', 'modulo' => 'Tributación'],
            // Tipos de Clientes
            ['nombre' => 'Ver Tipos Clientes', 'slug' => 'ver-tipos-clientes', 'modulo' => 'Catálogos'],
            ['nombre' => 'Crear Tipos Clientes', 'slug' => 'crear-tipos-clientes', 'modulo' => 'Catálogos'],
            ['nombre' => 'Editar Tipos Clientes', 'slug' => 'editar-tipos-clientes', 'modulo' => 'Catálogos'],
            ['nombre' => 'Eliminar Tipos Clientes', 'slug' => 'eliminar-tipos-clientes', 'modulo' => 'Catálogos'],
            // Tipos de Comprobantes FE (con guion bajo)
            ['nombre' => 'Ver Tipos Comprobantes FE', 'slug' => 'ver-tipo_comprobante_fe', 'modulo' => 'Catálogos'],
            ['nombre' => 'Crear Tipos Comprobantes FE', 'slug' => 'crear-tipo_comprobante_fe', 'modulo' => 'Catálogos'],
            ['nombre' => 'Editar Tipos Comprobantes FE', 'slug' => 'editar-tipo_comprobante_fe', 'modulo' => 'Catálogos'],
            ['nombre' => 'Eliminar Tipos Comprobantes FE', 'slug' => 'eliminar-tipo_comprobante_fe', 'modulo' => 'Catálogos'],
            // Proveedores
            ['nombre' => 'Ver Proveedores', 'slug' => 'ver-proveedores', 'modulo' => 'Proveedores'],
            ['nombre' => 'Crear Proveedores', 'slug' => 'crear-proveedores', 'modulo' => 'Proveedores'],
            ['nombre' => 'Editar Proveedores', 'slug' => 'editar-proveedores', 'modulo' => 'Proveedores'],
            ['nombre' => 'Eliminar Proveedores', 'slug' => 'eliminar-proveedores', 'modulo' => 'Proveedores'],
            // Facturación Electrónica
            ['nombre' => 'Ver Facturación Electrónica', 'slug' => 'ver-facturacion_electronica', 'modulo' => 'Facturación Electrónica'],
            ['nombre' => 'Crear Facturación Electrónica', 'slug' => 'crear-facturacion_electronica', 'modulo' => 'Facturación Electrónica'],
            ['nombre' => 'Editar Facturación Electrónica', 'slug' => 'editar-facturacion_electronica', 'modulo' => 'Facturación Electrónica'],
            ['nombre' => 'Eliminar Facturación Electrónica', 'slug' => 'eliminar-facturacion_electronica', 'modulo' => 'Facturación Electrónica'],
        ];

        foreach ($permisos as $permiso) {
            Permiso::firstOrCreate(
                ['slug' => $permiso['slug']], 
                $permiso
            );
        }
        
        // Asignar todos los permisos al rol Administrador si existe
        $adminRol = Rol::where('nombre', 'Administrador')->first();
        if ($adminRol) {
            $this->assignAllPermissionsToRole($adminRol);
        }
    }

    /**
     * Asigna todos los permisos a un rol
     */
    protected function assignAllPermissionsToRole(Rol $rol): void
    {
        $permisos = Permiso::all()->pluck('id')->toArray();
        $rol->permisos()->sync($permisos); // Usa sync en lugar de attach para evitar duplicados
    }

    /**
     * Crea o retorna una categoría de producto
     */
    protected function getCategoriaProducto(Empresa $empresa = null): \App\Models\CategoriaProducto
    {
        return \App\Models\CategoriaProducto::firstOrCreate(
            ['nombre' => 'Categoría Test'],
            ['activo' => true, 'eliminado' => false]
        );
    }

    /**
     * Crea o retorna una unidad de medida
     */
    protected function getUnidadMedida(): \App\Models\UnidadMedida
    {
        return \App\Models\UnidadMedida::firstOrCreate(
            ['codigo_dgt' => 'Unn'],
            ['nombre' => 'Unidad', 'activo' => true, 'eliminado' => false]
        );
    }

    /**
     * Crea un producto de prueba con todos los campos requeridos
     */
    protected function createProducto(array $attributes = [], Empresa $empresa = null): \App\Models\Producto
    {
        if (!$empresa) {
            $empresa = Empresa::first() ?? $this->createEmpresa();
        }

        $categoria = $this->getCategoriaProducto($empresa);
        $unidad = $this->getUnidadMedida();

        return \App\Models\Producto::create(array_merge([
            'empresa_id' => $empresa->id,
            'categoria_id' => $categoria->id,
            'unidad_medida_id' => $unidad->id,
            'nombre' => 'Producto Test ' . rand(1000, 9999),
            'codigo' => 'PROD' . rand(1000, 9999),
            'tipo' => 'producto',
            'precio_venta' => 1000.00,
            'activo' => true,
            'eliminado' => false,
        ], $attributes));
    }
}
