<?php

namespace App\DTOs\AI\Cabys;

use Illuminate\Http\Request;

/**
 * DTO para clasificación por lotes de productos en CABYS
 */
final class BatchClassifyDTO
{
    /**
     * @param array<int, array{description: string, id?: string}> $products
     */
    public function __construct(
        public readonly array $products,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $data = method_exists($request, 'validated') ? $request->validated() : [];
        
        return new self(
            products: $data['products'] ?? $request->input('products') ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'products' => $this->products,
        ];
    }
}
