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
        return new self(
            description: $request->validated('description'),
            category_hint: $request->validated('category_hint'),
            max_suggestions: $request->validated('max_suggestions'),
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
