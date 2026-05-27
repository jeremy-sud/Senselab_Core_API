<?php

namespace App\DTOs\AI\Cabys;

use Illuminate\Http\Request;

/**
 * DTO para clasificación simple de producto en CABYS
 */
final class ClassifyProductDTO
{
    public function __construct(
        public readonly string $description,
        public readonly ?string $category_hint = null,
        public readonly ?int $max_suggestions = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $data = method_exists($request, 'validated') ? $request->validated() : [];
        
        return new self(
            description: $data['description'] ?? $request->input('description') ?? '',
            category_hint: $data['category_hint'] ?? $request->input('category_hint'),
            max_suggestions: $data['max_suggestions'] ?? $request->input('max_suggestions'),
        );
    }

    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'category_hint' => $this->category_hint,
            'max_suggestions' => $this->max_suggestions,
        ];
    }
}
