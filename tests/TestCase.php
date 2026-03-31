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
            // Sucursales
            ['nombre' => 'Ver Sucursales', 'slug' => 'ver-sucursales', 'modulo' => 'Empresas'],
            ['nombre' => 'Crear Sucursales', 'slug' => 'crear-sucursales', 'modulo' => 'Empresas'],
            ['nombre' => 'Editar Sucursales', 'slug' => 'editar-sucursales', 'modulo' => 'Empresas'],
            ['nombre' => 'Eliminar Sucursales', 'slug' => 'eliminar-sucursales', 'modulo' => 'Empresas'],
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
            // Almacenes
            ['nombre' => 'Ver Almacenes', 'slug' => 'ver-almacenes', 'modulo' => 'Inventario'],
            ['nombre' => 'Crear Almacenes', 'slug' => 'crear-almacenes', 'modulo' => 'Inventario'],
            ['nombre' => 'Editar Almacenes', 'slug' => 'editar-almacenes', 'modulo' => 'Inventario'],
            ['nombre' => 'Eliminar Almacenes', 'slug' => 'eliminar-almacenes', 'modulo' => 'Inventario'],
            // Contabilidad
            ['nombre' => 'Ver Contabilidad', 'slug' => 'ver-contabilidad', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Crear Contabilidad', 'slug' => 'crear-contabilidad', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Editar Contabilidad', 'slug' => 'editar-contabilidad', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Eliminar Contabilidad', 'slug' => 'eliminar-contabilidad', 'modulo' => 'Contabilidad'],
            // Nómina
            ['nombre' => 'Ver Nómina', 'slug' => 'ver-nomina', 'modulo' => 'Nómina'],
            ['nombre' => 'Crear Nómina', 'slug' => 'crear-nomina', 'modulo' => 'Nómina'],
            ['nombre' => 'Editar Nómina', 'slug' => 'editar-nomina', 'modulo' => 'Nómina'],
            ['nombre' => 'Eliminar Nómina', 'slug' => 'eliminar-nomina', 'modulo' => 'Nómina'],
            // Catálogos generales
            ['nombre' => 'Ver Catálogos', 'slug' => 'ver-catalogos', 'modulo' => 'Catálogos'],
            ['nombre' => 'Crear Catálogos', 'slug' => 'crear-catalogos', 'modulo' => 'Catálogos'],
            ['nombre' => 'Editar Catálogos', 'slug' => 'editar-catalogos', 'modulo' => 'Catálogos'],
            ['nombre' => 'Eliminar Catálogos', 'slug' => 'eliminar-catalogos', 'modulo' => 'Catálogos'],
            // Categorías de Productos
            ['nombre' => 'Ver Categorías Producto', 'slug' => 'ver-categorias_producto', 'modulo' => 'Inventario'],
            ['nombre' => 'Crear Categorías Producto', 'slug' => 'crear-categorias_producto', 'modulo' => 'Inventario'],
            ['nombre' => 'Editar Categorías Producto', 'slug' => 'editar-categorias_producto', 'modulo' => 'Inventario'],
            ['nombre' => 'Eliminar Categorías Producto', 'slug' => 'eliminar-categorias_producto', 'modulo' => 'Inventario'],
            // Cuentas Contables
            ['nombre' => 'Ver Cuentas Contables', 'slug' => 'ver-cuentas_contables', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Crear Cuentas Contables', 'slug' => 'crear-cuentas_contables', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Editar Cuentas Contables', 'slug' => 'editar-cuentas_contables', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Eliminar Cuentas Contables', 'slug' => 'eliminar-cuentas_contables', 'modulo' => 'Contabilidad'],
            // Asientos Contables
            ['nombre' => 'Ver Asientos Contables', 'slug' => 'ver-asientos_contables', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Crear Asientos Contables', 'slug' => 'crear-asientos_contables', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Editar Asientos Contables', 'slug' => 'editar-asientos_contables', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Eliminar Asientos Contables', 'slug' => 'eliminar-asientos_contables', 'modulo' => 'Contabilidad'],
            // Tipos de Cambio
            ['nombre' => 'Ver Tipos de Cambio', 'slug' => 'ver-tipos_cambio', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Crear Tipos de Cambio', 'slug' => 'crear-tipos_cambio', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Editar Tipos de Cambio', 'slug' => 'editar-tipos_cambio', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Eliminar Tipos de Cambio', 'slug' => 'eliminar-tipos_cambio', 'modulo' => 'Contabilidad'],
            // Empleados
            ['nombre' => 'Ver Empleados', 'slug' => 'ver-empleados', 'modulo' => 'Nómina'],
            ['nombre' => 'Crear Empleados', 'slug' => 'crear-empleados', 'modulo' => 'Nómina'],
            ['nombre' => 'Editar Empleados', 'slug' => 'editar-empleados', 'modulo' => 'Nómina'],
            ['nombre' => 'Eliminar Empleados', 'slug' => 'eliminar-empleados', 'modulo' => 'Nómina'],
            // Órdenes de Compra
            ['nombre' => 'Ver Órdenes Compra', 'slug' => 'ver-ordenes_compra', 'modulo' => 'Compras'],
            ['nombre' => 'Crear Órdenes Compra', 'slug' => 'crear-ordenes_compra', 'modulo' => 'Compras'],
            ['nombre' => 'Editar Órdenes Compra', 'slug' => 'editar-ordenes_compra', 'modulo' => 'Compras'],
            ['nombre' => 'Eliminar Órdenes Compra', 'slug' => 'eliminar-ordenes_compra', 'modulo' => 'Compras'],
            // Entradas de Inventario
            ['nombre' => 'Ver Entradas Inventario', 'slug' => 'ver-entrada_inventario', 'modulo' => 'Inventario'],
            ['nombre' => 'Crear Entradas Inventario', 'slug' => 'crear-entrada_inventario', 'modulo' => 'Inventario'],
            ['nombre' => 'Editar Entradas Inventario', 'slug' => 'editar-entrada_inventario', 'modulo' => 'Inventario'],
            ['nombre' => 'Eliminar Entradas Inventario', 'slug' => 'eliminar-entrada_inventario', 'modulo' => 'Inventario'],
            // Salidas de Inventario
            ['nombre' => 'Ver Salidas Inventario', 'slug' => 'ver-salida_inventario', 'modulo' => 'Inventario'],
            ['nombre' => 'Crear Salidas Inventario', 'slug' => 'crear-salida_inventario', 'modulo' => 'Inventario'],
            ['nombre' => 'Editar Salidas Inventario', 'slug' => 'editar-salida_inventario', 'modulo' => 'Inventario'],
            ['nombre' => 'Eliminar Salidas Inventario', 'slug' => 'eliminar-salida_inventario', 'modulo' => 'Inventario'],
            // Cuentas Contables (slug corregido para coincidir con Policy)
            ['nombre' => 'Ver Cuenta Contable', 'slug' => 'ver-cuenta_contable', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Crear Cuenta Contable', 'slug' => 'crear-cuenta_contable', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Editar Cuenta Contable', 'slug' => 'editar-cuenta_contable', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Eliminar Cuenta Contable', 'slug' => 'eliminar-cuenta_contable', 'modulo' => 'Contabilidad'],
            // Cuentas por Cobrar
            ['nombre' => 'Ver Cuentas por Cobrar', 'slug' => 'ver-cuentas_por_cobrar', 'modulo' => 'Finanzas'],
            ['nombre' => 'Crear Cuentas por Cobrar', 'slug' => 'crear-cuentas_por_cobrar', 'modulo' => 'Finanzas'],
            ['nombre' => 'Editar Cuentas por Cobrar', 'slug' => 'editar-cuentas_por_cobrar', 'modulo' => 'Finanzas'],
            ['nombre' => 'Eliminar Cuentas por Cobrar', 'slug' => 'eliminar-cuentas_por_cobrar', 'modulo' => 'Finanzas'],
            // Cuentas por Pagar
            ['nombre' => 'Ver Cuentas por Pagar', 'slug' => 'ver-cuentas_por_pagar', 'modulo' => 'Finanzas'],
            ['nombre' => 'Crear Cuentas por Pagar', 'slug' => 'crear-cuentas_por_pagar', 'modulo' => 'Finanzas'],
            ['nombre' => 'Editar Cuentas por Pagar', 'slug' => 'editar-cuentas_por_pagar', 'modulo' => 'Finanzas'],
            ['nombre' => 'Eliminar Cuentas por Pagar', 'slug' => 'eliminar-cuentas_por_pagar', 'modulo' => 'Finanzas'],
            // Períodos de Nómina
            ['nombre' => 'Ver Período Nómina', 'slug' => 'ver-periodo_nomina', 'modulo' => 'Nómina'],
            ['nombre' => 'Crear Período Nómina', 'slug' => 'crear-periodo_nomina', 'modulo' => 'Nómina'],
            ['nombre' => 'Editar Período Nómina', 'slug' => 'editar-periodo_nomina', 'modulo' => 'Nómina'],
            ['nombre' => 'Eliminar Período Nómina', 'slug' => 'eliminar-periodo_nomina', 'modulo' => 'Nómina'],
            // Pagos de Nómina
            ['nombre' => 'Ver Pago Nómina', 'slug' => 'ver-pago_nomina', 'modulo' => 'Nómina'],
            ['nombre' => 'Crear Pago Nómina', 'slug' => 'crear-pago_nomina', 'modulo' => 'Nómina'],
            ['nombre' => 'Editar Pago Nómina', 'slug' => 'editar-pago_nomina', 'modulo' => 'Nómina'],
            ['nombre' => 'Eliminar Pago Nómina', 'slug' => 'eliminar-pago_nomina', 'modulo' => 'Nómina'],
            // Tipos de Cuentas
            ['nombre' => 'Ver Tipos Cuentas', 'slug' => 'ver-tipo_cuenta', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Crear Tipos Cuentas', 'slug' => 'crear-tipo_cuenta', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Editar Tipos Cuentas', 'slug' => 'editar-tipo_cuenta', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Eliminar Tipos Cuentas', 'slug' => 'eliminar-tipo_cuenta', 'modulo' => 'Contabilidad'],
            // Configuraciones
            ['nombre' => 'Ver Configuraciones', 'slug' => 'ver-configuraciones', 'modulo' => 'Sistema'],
            ['nombre' => 'Crear Configuraciones', 'slug' => 'crear-configuraciones', 'modulo' => 'Sistema'],
            ['nombre' => 'Editar Configuraciones', 'slug' => 'editar-configuraciones', 'modulo' => 'Sistema'],
            ['nombre' => 'Eliminar Configuraciones', 'slug' => 'eliminar-configuraciones', 'modulo' => 'Sistema'],
            ['nombre' => 'Ver Configuración', 'slug' => 'ver-configuracion', 'modulo' => 'Sistema'],
            ['nombre' => 'Crear Configuración', 'slug' => 'crear-configuracion', 'modulo' => 'Sistema'],
            ['nombre' => 'Editar Configuración', 'slug' => 'editar-configuracion', 'modulo' => 'Sistema'],
            ['nombre' => 'Eliminar Configuración', 'slug' => 'eliminar-configuracion', 'modulo' => 'Sistema'],
            // Caja Chica
            ['nombre' => 'Ver Caja Chica', 'slug' => 'ver-caja_chica', 'modulo' => 'Finanzas'],
            ['nombre' => 'Crear Caja Chica', 'slug' => 'crear-caja_chica', 'modulo' => 'Finanzas'],
            ['nombre' => 'Editar Caja Chica', 'slug' => 'editar-caja_chica', 'modulo' => 'Finanzas'],
            ['nombre' => 'Eliminar Caja Chica', 'slug' => 'eliminar-caja_chica', 'modulo' => 'Finanzas'],
            ['nombre' => 'Ver Cajas Chicas', 'slug' => 'ver-cajas_chicas', 'modulo' => 'Finanzas'],
            ['nombre' => 'Crear Cajas Chicas', 'slug' => 'crear-cajas_chicas', 'modulo' => 'Finanzas'],
            ['nombre' => 'Editar Cajas Chicas', 'slug' => 'editar-cajas_chicas', 'modulo' => 'Finanzas'],
            ['nombre' => 'Eliminar Cajas Chicas', 'slug' => 'eliminar-cajas_chicas', 'modulo' => 'Finanzas'],
            // Presupuestos
            ['nombre' => 'Ver Presupuestos', 'slug' => 'ver-presupuestos', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Crear Presupuestos', 'slug' => 'crear-presupuestos', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Editar Presupuestos', 'slug' => 'editar-presupuestos', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Eliminar Presupuestos', 'slug' => 'eliminar-presupuestos', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Ver Presupuesto', 'slug' => 'ver-presupuesto', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Crear Presupuesto', 'slug' => 'crear-presupuesto', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Editar Presupuesto', 'slug' => 'editar-presupuesto', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Eliminar Presupuesto', 'slug' => 'eliminar-presupuesto', 'modulo' => 'Contabilidad'],
            // Cajas (Registradoras)
            ['nombre' => 'Ver Cajas', 'slug' => 'ver-cajas', 'modulo' => 'Finanzas'],
            ['nombre' => 'Crear Cajas', 'slug' => 'crear-cajas', 'modulo' => 'Finanzas'],
            ['nombre' => 'Editar Cajas', 'slug' => 'editar-cajas', 'modulo' => 'Finanzas'],
            ['nombre' => 'Eliminar Cajas', 'slug' => 'eliminar-cajas', 'modulo' => 'Finanzas'],
            // Usuarios
            ['nombre' => 'Ver Usuarios', 'slug' => 'ver-usuarios', 'modulo' => 'Sistema'],
            ['nombre' => 'Crear Usuarios', 'slug' => 'crear-usuarios', 'modulo' => 'Sistema'],
            ['nombre' => 'Editar Usuarios', 'slug' => 'editar-usuarios', 'modulo' => 'Sistema'],
            ['nombre' => 'Eliminar Usuarios', 'slug' => 'eliminar-usuarios', 'modulo' => 'Sistema'],
            // Etiquetas
            ['nombre' => 'Ver Etiquetas', 'slug' => 'ver-etiquetas', 'modulo' => 'Sistema'],
            ['nombre' => 'Crear Etiquetas', 'slug' => 'crear-etiquetas', 'modulo' => 'Sistema'],
            ['nombre' => 'Editar Etiquetas', 'slug' => 'editar-etiquetas', 'modulo' => 'Sistema'],
            ['nombre' => 'Eliminar Etiquetas', 'slug' => 'eliminar-etiquetas', 'modulo' => 'Sistema'],
            // Marcas (Policy: BasePolicy $permission='marca')
            ['nombre' => 'Ver Marcas', 'slug' => 'ver-marca', 'modulo' => 'Inventario'],
            ['nombre' => 'Crear Marcas', 'slug' => 'crear-marca', 'modulo' => 'Inventario'],
            ['nombre' => 'Editar Marcas', 'slug' => 'editar-marca', 'modulo' => 'Inventario'],
            ['nombre' => 'Eliminar Marcas', 'slug' => 'eliminar-marca', 'modulo' => 'Inventario'],
            // Formas de Pago (Policy: BasePolicy $permission='forma_pago')
            ['nombre' => 'Ver Formas Pago', 'slug' => 'ver-forma_pago', 'modulo' => 'Ventas'],
            ['nombre' => 'Crear Formas Pago', 'slug' => 'crear-forma_pago', 'modulo' => 'Ventas'],
            ['nombre' => 'Editar Formas Pago', 'slug' => 'editar-forma_pago', 'modulo' => 'Ventas'],
            ['nombre' => 'Eliminar Formas Pago', 'slug' => 'eliminar-forma_pago', 'modulo' => 'Ventas'],
            // Tipos de Impuesto (Policy: BasePolicy $permission='tipo_impuesto')
            ['nombre' => 'Ver Tipos Impuesto', 'slug' => 'ver-tipo_impuesto', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Crear Tipos Impuesto', 'slug' => 'crear-tipo_impuesto', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Editar Tipos Impuesto', 'slug' => 'editar-tipo_impuesto', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Eliminar Tipos Impuesto', 'slug' => 'eliminar-tipo_impuesto', 'modulo' => 'Contabilidad'],
            // Detalle Asiento (Policy: BasePolicy $permission='detalle_asiento')
            ['nombre' => 'Ver Detalle Asiento', 'slug' => 'ver-detalle_asiento', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Crear Detalle Asiento', 'slug' => 'crear-detalle_asiento', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Editar Detalle Asiento', 'slug' => 'editar-detalle_asiento', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Eliminar Detalle Asiento', 'slug' => 'eliminar-detalle_asiento', 'modulo' => 'Contabilidad'],
            // Cajas (Policy: dot-notation cajas.X)
            ['nombre' => 'Cajas Index', 'slug' => 'cajas.index', 'modulo' => 'Configuracion'],
            ['nombre' => 'Cajas Show', 'slug' => 'cajas.show', 'modulo' => 'Configuracion'],
            ['nombre' => 'Cajas Store', 'slug' => 'cajas.store', 'modulo' => 'Configuracion'],
            ['nombre' => 'Cajas Update', 'slug' => 'cajas.update', 'modulo' => 'Configuracion'],
            ['nombre' => 'Cajas Destroy', 'slug' => 'cajas.destroy', 'modulo' => 'Configuracion'],
            // Nomina Empleados (Policy: dot-notation nomina_empleados.X)
            ['nombre' => 'Nomina Empleados Index', 'slug' => 'nomina_empleados.index', 'modulo' => 'Nomina'],
            ['nombre' => 'Nomina Empleados Show', 'slug' => 'nomina_empleados.show', 'modulo' => 'Nomina'],
            ['nombre' => 'Nomina Empleados Store', 'slug' => 'nomina_empleados.store', 'modulo' => 'Nomina'],
            ['nombre' => 'Nomina Empleados Update', 'slug' => 'nomina_empleados.update', 'modulo' => 'Nomina'],
            ['nombre' => 'Nomina Empleados Destroy', 'slug' => 'nomina_empleados.destroy', 'modulo' => 'Nomina'],
            // Pagos Cuentas Cobrar (Policy: dot-notation pagos_cuentas_cobrar.X)
            ['nombre' => 'Pagos Cuentas Cobrar Index', 'slug' => 'pagos_cuentas_cobrar.index', 'modulo' => 'Ventas'],
            ['nombre' => 'Pagos Cuentas Cobrar Show', 'slug' => 'pagos_cuentas_cobrar.show', 'modulo' => 'Ventas'],
            ['nombre' => 'Pagos Cuentas Cobrar Store', 'slug' => 'pagos_cuentas_cobrar.store', 'modulo' => 'Ventas'],
            ['nombre' => 'Pagos Cuentas Cobrar Update', 'slug' => 'pagos_cuentas_cobrar.update', 'modulo' => 'Ventas'],
            ['nombre' => 'Pagos Cuentas Cobrar Destroy', 'slug' => 'pagos_cuentas_cobrar.destroy', 'modulo' => 'Ventas'],
            // Pagos Cuentas Pagar (Policy: dot-notation pagos_cuentas_pagar.X)
            ['nombre' => 'Pagos Cuentas Pagar Index', 'slug' => 'pagos_cuentas_pagar.index', 'modulo' => 'Compras'],
            ['nombre' => 'Pagos Cuentas Pagar Show', 'slug' => 'pagos_cuentas_pagar.show', 'modulo' => 'Compras'],
            ['nombre' => 'Pagos Cuentas Pagar Store', 'slug' => 'pagos_cuentas_pagar.store', 'modulo' => 'Compras'],
            ['nombre' => 'Pagos Cuentas Pagar Update', 'slug' => 'pagos_cuentas_pagar.update', 'modulo' => 'Compras'],
            ['nombre' => 'Pagos Cuentas Pagar Destroy', 'slug' => 'pagos_cuentas_pagar.destroy', 'modulo' => 'Compras'],
            // Tasas de Impuesto (Policy: BasePolicy $permission='tasa_impuesto')
            ['nombre' => 'Ver Tasas Impuesto', 'slug' => 'ver-tasa_impuesto', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Crear Tasas Impuesto', 'slug' => 'crear-tasa_impuesto', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Editar Tasas Impuesto', 'slug' => 'editar-tasa_impuesto', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Eliminar Tasas Impuesto', 'slug' => 'eliminar-tasa_impuesto', 'modulo' => 'Contabilidad'],
            // Unidad Medida (Policy: BasePolicy $permission='unidad_medida')
            ['nombre' => 'Ver Unidad Medida', 'slug' => 'ver-unidad_medida', 'modulo' => 'Inventario'],
            ['nombre' => 'Crear Unidad Medida', 'slug' => 'crear-unidad_medida', 'modulo' => 'Inventario'],
            ['nombre' => 'Editar Unidad Medida', 'slug' => 'editar-unidad_medida', 'modulo' => 'Inventario'],
            ['nombre' => 'Eliminar Unidad Medida', 'slug' => 'eliminar-unidad_medida', 'modulo' => 'Inventario'],
            // Salida Inventario (Policy: BasePolicy $permission='salida_inventario')
            ['nombre' => 'Ver Salida Inventario', 'slug' => 'ver-salida_inventario', 'modulo' => 'Inventario'],
            ['nombre' => 'Crear Salida Inventario', 'slug' => 'crear-salida_inventario', 'modulo' => 'Inventario'],
            ['nombre' => 'Editar Salida Inventario', 'slug' => 'editar-salida_inventario', 'modulo' => 'Inventario'],
            ['nombre' => 'Eliminar Salida Inventario', 'slug' => 'eliminar-salida_inventario', 'modulo' => 'Inventario'],
            // Codigo Actividad Economica (Policy: BasePolicy $permission='codigo_actividad_economica')
            ['nombre' => 'Ver Codigo Actividad', 'slug' => 'ver-codigo_actividad_economica', 'modulo' => 'Catálogos'],
            ['nombre' => 'Crear Codigo Actividad', 'slug' => 'crear-codigo_actividad_economica', 'modulo' => 'Catálogos'],
            ['nombre' => 'Editar Codigo Actividad', 'slug' => 'editar-codigo_actividad_economica', 'modulo' => 'Catálogos'],
            ['nombre' => 'Eliminar Codigo Actividad', 'slug' => 'eliminar-codigo_actividad_economica', 'modulo' => 'Catálogos'],
            // Deduccion Legal (Policy: BasePolicy $permission='deduccion_legal')
            ['nombre' => 'Ver Deduccion Legal', 'slug' => 'ver-deduccion_legal', 'modulo' => 'Nómina'],
            ['nombre' => 'Crear Deduccion Legal', 'slug' => 'crear-deduccion_legal', 'modulo' => 'Nómina'],
            ['nombre' => 'Editar Deduccion Legal', 'slug' => 'editar-deduccion_legal', 'modulo' => 'Nómina'],
            ['nombre' => 'Eliminar Deduccion Legal', 'slug' => 'eliminar-deduccion_legal', 'modulo' => 'Nómina'],
            // Cargo (Policy: BasePolicy $permission='cargo')
            ['nombre' => 'Ver Cargo', 'slug' => 'ver-cargo', 'modulo' => 'Nómina'],
            ['nombre' => 'Crear Cargo', 'slug' => 'crear-cargo', 'modulo' => 'Nómina'],
            ['nombre' => 'Editar Cargo', 'slug' => 'editar-cargo', 'modulo' => 'Nómina'],
            ['nombre' => 'Eliminar Cargo', 'slug' => 'eliminar-cargo', 'modulo' => 'Nómina'],
            // Pago (Policy: BasePolicy $permission='pago')
            ['nombre' => 'Ver Pago', 'slug' => 'ver-pago', 'modulo' => 'Finanzas'],
            ['nombre' => 'Crear Pago', 'slug' => 'crear-pago', 'modulo' => 'Finanzas'],
            ['nombre' => 'Editar Pago', 'slug' => 'editar-pago', 'modulo' => 'Finanzas'],
            ['nombre' => 'Eliminar Pago', 'slug' => 'eliminar-pago', 'modulo' => 'Finanzas'],
            // DetallePresupuesto (Policy: BasePolicy $permission='detalle_presupuesto')
            ['nombre' => 'Ver Detalle Presupuesto', 'slug' => 'ver-detalle_presupuesto', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Crear Detalle Presupuesto', 'slug' => 'crear-detalle_presupuesto', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Editar Detalle Presupuesto', 'slug' => 'editar-detalle_presupuesto', 'modulo' => 'Contabilidad'],
            ['nombre' => 'Eliminar Detalle Presupuesto', 'slug' => 'eliminar-detalle_presupuesto', 'modulo' => 'Contabilidad'],
            // PlanillaCcss (Policy: BasePolicy $permission='planilla_ccss')
            ['nombre' => 'Ver Planilla CCSS', 'slug' => 'ver-planilla_ccss', 'modulo' => 'Nómina'],
            ['nombre' => 'Crear Planilla CCSS', 'slug' => 'crear-planilla_ccss', 'modulo' => 'Nómina'],
            ['nombre' => 'Editar Planilla CCSS', 'slug' => 'editar-planilla_ccss', 'modulo' => 'Nómina'],
            ['nombre' => 'Eliminar Planilla CCSS', 'slug' => 'eliminar-planilla_ccss', 'modulo' => 'Nómina'],
            // Ruta (middleware: ver-rutas, rutas.crear, etc. + Policy: BasePolicy $permission='ruta')
            ['nombre' => 'Ver Rutas', 'slug' => 'ver-rutas', 'modulo' => 'Transporte'],
            ['nombre' => 'Crear Rutas', 'slug' => 'rutas.crear', 'modulo' => 'Transporte'],
            ['nombre' => 'Editar Rutas', 'slug' => 'rutas.actualizar', 'modulo' => 'Transporte'],
            ['nombre' => 'Eliminar Rutas', 'slug' => 'rutas.eliminar', 'modulo' => 'Transporte'],
            ['nombre' => 'Ver Ruta', 'slug' => 'ver-ruta', 'modulo' => 'Transporte'],
            ['nombre' => 'Crear Ruta', 'slug' => 'crear-ruta', 'modulo' => 'Transporte'],
            ['nombre' => 'Editar Ruta', 'slug' => 'editar-ruta', 'modulo' => 'Transporte'],
            ['nombre' => 'Eliminar Ruta', 'slug' => 'eliminar-ruta', 'modulo' => 'Transporte'],
            // ModeloBus (Policy: BasePolicy $permission='modelo_bus')
            ['nombre' => 'Ver Modelo Bus', 'slug' => 'ver-modelo_bus', 'modulo' => 'Transporte'],
            ['nombre' => 'Crear Modelo Bus', 'slug' => 'crear-modelo_bus', 'modulo' => 'Transporte'],
            ['nombre' => 'Editar Modelo Bus', 'slug' => 'editar-modelo_bus', 'modulo' => 'Transporte'],
            ['nombre' => 'Eliminar Modelo Bus', 'slug' => 'eliminar-modelo_bus', 'modulo' => 'Transporte'],
            // Cabys (Policy: BasePolicy $permission='cabys')
            ['nombre' => 'Ver Cabys', 'slug' => 'ver-cabys', 'modulo' => 'Catálogos'],
            ['nombre' => 'Crear Cabys', 'slug' => 'crear-cabys', 'modulo' => 'Catálogos'],
            ['nombre' => 'Editar Cabys', 'slug' => 'editar-cabys', 'modulo' => 'Catálogos'],
            ['nombre' => 'Eliminar Cabys', 'slug' => 'eliminar-cabys', 'modulo' => 'Catálogos'],
            // DeduccionLegal (Policy: BasePolicy $permission='deduccion_legal')
            ['nombre' => 'Ver Deducción Legal', 'slug' => 'ver-deduccion_legal', 'modulo' => 'Nómina'],
            ['nombre' => 'Crear Deducción Legal', 'slug' => 'crear-deduccion_legal', 'modulo' => 'Nómina'],
            ['nombre' => 'Editar Deducción Legal', 'slug' => 'editar-deduccion_legal', 'modulo' => 'Nómina'],
            ['nombre' => 'Eliminar Deducción Legal', 'slug' => 'eliminar-deduccion_legal', 'modulo' => 'Nómina'],
            // TipoCliente (Policy: BasePolicy $permission='tipos-clientes')
            ['nombre' => 'Ver Tipos Clientes', 'slug' => 'ver-tipos-clientes', 'modulo' => 'Clientes'],
            ['nombre' => 'Crear Tipos Clientes', 'slug' => 'crear-tipos-clientes', 'modulo' => 'Clientes'],
            ['nombre' => 'Editar Tipos Clientes', 'slug' => 'editar-tipos-clientes', 'modulo' => 'Clientes'],
            ['nombre' => 'Eliminar Tipos Clientes', 'slug' => 'eliminar-tipos-clientes', 'modulo' => 'Clientes'],
            // Etiqueta (Policy: dot-notation pattern)
            ['nombre' => 'Etiquetas Index', 'slug' => 'etiquetas.index', 'modulo' => 'Sistema'],
            ['nombre' => 'Etiquetas Show', 'slug' => 'etiquetas.show', 'modulo' => 'Sistema'],
            ['nombre' => 'Etiquetas Store', 'slug' => 'etiquetas.store', 'modulo' => 'Sistema'],
            ['nombre' => 'Etiquetas Update', 'slug' => 'etiquetas.update', 'modulo' => 'Sistema'],
            ['nombre' => 'Etiquetas Destroy', 'slug' => 'etiquetas.destroy', 'modulo' => 'Sistema'],
            // Webhooks (Policy: BasePolicy $permission='webhooks')
            ['nombre' => 'Ver Webhooks', 'slug' => 'ver-webhooks', 'modulo' => 'Sistema'],
            ['nombre' => 'Crear Webhooks', 'slug' => 'crear-webhooks', 'modulo' => 'Sistema'],
            ['nombre' => 'Editar Webhooks', 'slug' => 'editar-webhooks', 'modulo' => 'Sistema'],
            ['nombre' => 'Eliminar Webhooks', 'slug' => 'eliminar-webhooks', 'modulo' => 'Sistema'],
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

        // Usar uniqid para evitar colisiones
        $uniqueId = uniqid('', true);
        $shortId = substr(md5($uniqueId), 0, 8);

        return \App\Models\Producto::create(array_merge([
            'empresa_id' => $empresa->id,
            'categoria_id' => $categoria->id,
            'unidad_medida_id' => $unidad->id,
            'nombre' => 'Producto Test ' . $shortId,
            'codigo' => 'PROD' . strtoupper($shortId),
            'tipo' => 'producto',
            'precio_venta' => 1000.00,
            'activo' => true,
            'eliminado' => false,
        ], $attributes));
    }
}
