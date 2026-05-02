<?php

namespace Tests\Unit\DTOs\AI;

use App\DTOs\AI\Cabys\ClassifyProductDTO;
use App\DTOs\AI\Cabys\BatchClassifyDTO;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CabysClassificationDTOTest extends TestCase
{
    use RefreshDatabase;

    public function test_classify_product_dto_from_request(): void
    {
        $request = Request::create('/api/v1/ai/cabys/classify', 'POST', [
            'description' => 'Arroz blanco grano largo',
            'category_hint' => 'Alimentos',
            'max_suggestions' => 3,
        ]);

        $dto = ClassifyProductDTO::fromRequest($request);

        $this->assertEquals('Arroz blanco grano largo', $dto->description);
        $this->assertEquals('Alimentos', $dto->category_hint);
        $this->assertEquals(3, $dto->max_suggestions);
    }

    public function test_classify_product_dto_with_minimal_data(): void
    {
        $request = Request::create('/api/v1/ai/cabys/classify', 'POST', [
            'description' => 'Test producto',
        ]);

        $dto = ClassifyProductDTO::fromRequest($request);

        $this->assertEquals('Test producto', $dto->description);
        $this->assertNull($dto->category_hint);
        $this->assertNull($dto->max_suggestions);
    }

    public function test_classify_product_dto_to_array(): void
    {
        $dto = new ClassifyProductDTO(
            description: 'Arroz',
            category_hint: 'Alimentos',
            max_suggestions: 5,
        );

        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('Arroz', $array['description']);
        $this->assertEquals('Alimentos', $array['category_hint']);
        $this->assertEquals(5, $array['max_suggestions']);
    }

    public function test_batch_classify_dto_from_request(): void
    {
        $products = [
            ['description' => 'Producto 1', 'id' => '123'],
            ['description' => 'Producto 2', 'id' => '456'],
        ];

        $request = Request::create('/api/v1/ai/cabys/batch', 'POST', [
            'products' => $products,
        ]);

        $dto = BatchClassifyDTO::fromRequest($request);

        $this->assertCount(2, $dto->products);
        $this->assertEquals('Producto 1', $dto->products[0]['description']);
        $this->assertEquals('123', $dto->products[0]['id']);
    }

    public function test_batch_classify_dto_to_array(): void
    {
        $products = [
            ['description' => 'Producto 1'],
            ['description' => 'Producto 2'],
        ];

        $dto = new BatchClassifyDTO(products: $products);

        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertCount(2, $array['products']);
    }
}
