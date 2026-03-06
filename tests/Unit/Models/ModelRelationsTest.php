<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Caja;
use App\Models\Empleado;
use App\Models\OrdenCompra;
use App\Models\Presupuesto;
use App\Models\PagoNomina;
use App\Models\PeriodoNomina;
use App\Models\TipoCuenta;
use App\Models\Departamento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests unitarios para las relaciones de modelos añadidas/corregidas
 *
 * Verifica que las relaciones Eloquent definidas durante el refactoring
 * están correctamente tipadas y devuelven las instancias esperadas.
 *
 * Modelos cubiertos:
 * - Caja (usuario, movimientos)
 * - Empleado (usuario, departamento)
 * - OrdenCompra (detalles, pagos)
 * - Presupuesto (detalles)
 * - PagoNomina (metodoPago, formaPago)
 * - PeriodoNomina (pagos alias)
 * - TipoCuenta (cuentasContables)
 * - Departamento (empleados)
 *
 */
#[Group('models')]
#[Group('relations')]
class ModelRelationsTest extends TestCase
{
    use RefreshDatabase;

    // ── Caja ───────────────────────────────────────────────────

    #[Test]
    public function test_caja_tiene_relacion_usuario()
    {
        $caja = new Caja();
        $relation = $caja->usuario();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relation
        );
    }

    #[Test]
    public function test_caja_tiene_relacion_movimientos()
    {
        $caja = new Caja();
        $relation = $caja->movimientos();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $relation
        );
    }

    #[Test]
    public function test_caja_tiene_usuario_id_en_fillable()
    {
        $caja = new Caja();
        $this->assertContains('usuario_id', $caja->getFillable());
    }

    // ── Empleado ───────────────────────────────────────────────

    #[Test]
    public function test_empleado_tiene_relacion_usuario()
    {
        $empleado = new Empleado();
        $relation = $empleado->usuario();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relation
        );
    }

    #[Test]
    public function test_empleado_tiene_relacion_departamento()
    {
        $empleado = new Empleado();
        $relation = $empleado->departamento();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relation
        );
    }

    #[Test]
    public function test_empleado_tiene_fks_en_fillable()
    {
        $empleado = new Empleado();
        $fillable = $empleado->getFillable();

        $this->assertContains('usuario_id', $fillable);
        $this->assertContains('departamento_id', $fillable);
    }

    // ── OrdenCompra ────────────────────────────────────────────

    #[Test]
    public function test_orden_compra_detalles_retorna_has_many()
    {
        $orden = new OrdenCompra();
        $relation = $orden->detalles();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $relation
        );
    }

    #[Test]
    public function test_orden_compra_pagos_retorna_has_many()
    {
        $orden = new OrdenCompra();
        $relation = $orden->pagos();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $relation
        );
    }

    // ── Presupuesto ────────────────────────────────────────────

    #[Test]
    public function test_presupuesto_detalles_retorna_has_many()
    {
        $presupuesto = new Presupuesto();
        $relation = $presupuesto->detalles();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $relation
        );
    }

    // ── PagoNomina ─────────────────────────────────────────────

    #[Test]
    public function test_pago_nomina_tiene_metodo_pago()
    {
        $pago = new PagoNomina();
        $relation = $pago->metodoPago();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relation
        );
    }

    #[Test]
    public function test_pago_nomina_forma_pago_retorna_belongs_to()
    {
        $pago = new PagoNomina();
        $relation = $pago->formaPago();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relation
        );
    }

    // ── PeriodoNomina ──────────────────────────────────────────

    #[Test]
    public function test_periodo_nomina_pagos_alias()
    {
        $periodo = new PeriodoNomina();

        // pagos() debe ser un alias de pagosNomina()
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $periodo->pagos()
        );
    }

    // ── TipoCuenta ─────────────────────────────────────────────

    #[Test]
    public function test_tipo_cuenta_tiene_cuentas_contables()
    {
        $tipo = new TipoCuenta();
        $relation = $tipo->cuentasContables();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $relation
        );
    }

    // ── Departamento (nuevo modelo) ────────────────────────────

    #[Test]
    public function test_departamento_tiene_relacion_empleados()
    {
        $depto = new Departamento();
        $relation = $depto->empleados();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $relation
        );
    }

    #[Test]
    public function test_departamento_tiene_fillable_correcto()
    {
        $depto = new Departamento();
        $fillable = $depto->getFillable();

        $this->assertContains('nombre', $fillable);
        $this->assertContains('descripcion', $fillable);
        $this->assertContains('codigo', $fillable);
        $this->assertContains('activo', $fillable);
    }

    #[Test]
    public function test_departamento_scope_activos()
    {
        $builder = Departamento::activos();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Builder::class,
            $builder
        );
    }
}
