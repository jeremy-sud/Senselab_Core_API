<?php

namespace App\DTOs\AI\Content;

use Illuminate\Http\Request;

/**
 * DTO para generar recordatorio de pago mediante IA
 */
final class GeneratePaymentReminderDTO
{
    /**
     * @param int[] $factura_ids
     */
    public function __construct(
        public readonly int $cliente_id,
        public readonly array $factura_ids = [],
        public readonly string $tone = 'friendly',
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            cliente_id: $request->validated('cliente_id'),
            factura_ids: $request->validated('factura_ids', []),
            tone: $request->validated('tone', 'friendly'),
        );
    }

    public function toArray(): array
    {
        return [
            'cliente_id' => $this->cliente_id,
            'factura_ids' => $this->factura_ids,
            'tone' => $this->tone,
        ];
    }
}
