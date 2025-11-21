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
     * Crea una empresa de prueba
     */
    protected function createEmpresa(array $attributes = []): Empresa
    {
        // Crear régimen tributario si no existe
        $regimen = \App\Models\RegimenTributario::firstOrCreate(
            ['nombre' => 'Régimen General'],
            ['descripcion' => 'Régimen General de Tributación']
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
            ['nombre' => 'Ver Productos', 'slug' => 'ver-productos', 'modulo' => 'Productos'],
            ['nombre' => 'Crear Productos', 'slug' => 'crear-productos', 'modulo' => 'Productos'],
            ['nombre' => 'Editar Productos', 'slug' => 'editar-productos', 'modulo' => 'Productos'],
            ['nombre' => 'Eliminar Productos', 'slug' => 'eliminar-productos', 'modulo' => 'Productos'],
            ['nombre' => 'Ver Clientes', 'slug' => 'ver-clientes', 'modulo' => 'Clientes'],
            ['nombre' => 'Crear Clientes', 'slug' => 'crear-clientes', 'modulo' => 'Clientes'],
        ];

        foreach ($permisos as $permiso) {
            Permiso::create($permiso);
        }
    }

    /**
     * Asigna todos los permisos a un rol
     */
    protected function assignAllPermissionsToRole(Rol $rol): void
    {
        $permisos = Permiso::all();
        foreach ($permisos as $permiso) {
            $rol->permisos()->attach($permiso->id);
        }
    }
}
