<?php

namespace Database\Factories;

use App\Models\Producto;
use App\Models\Empresa;
use App\Models\Marca;
use App\Models\CategoriaProducto;
use App\Models\UnidadMedida;
use App\Models\Cabys;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'codigo_interno' => $this->faker->unique()->ean8(),
            'codigo_barra' => $this->faker->ean13(),
            'nombre' => $this->faker->words(3, true),
            'descripcion' => $this->faker->sentence(),
            'marca_id' => Marca::inRandomOrder()->first()?->id,
            'categoria_id' => CategoriaProducto::inRandomOrder()->first()?->id,
            'unidad_medida_id' => UnidadMedida::inRandomOrder()->first()?->id ?? 1,
            'cabys_id' => Cabys::inRandomOrder()->first()?->id,
            'precio_costo' => $this->faker->randomFloat(2, 100, 10000),
            'precio_venta' => $this->faker->randomFloat(2, 150, 15000),
            'margen_utilidad_porcentaje' => $this->faker->randomFloat(2, 10, 50),
            'precio_minimo_venta' => $this->faker->randomFloat(2, 120, 12000),
            'tipo_producto' => $this->faker->randomElement(['producto', 'servicio']),
            'controla_inventario' => $this->faker->boolean(80),
            'permite_venta' => true,
            'permite_compra' => true,
            'stock_minimo' => $this->faker->numberBetween(5, 20),
            'stock_maximo' => $this->faker->numberBetween(50, 200),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
