<?php

namespace App\Http\Resources\Api\V1\Hacienda;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * API Resource Collection para HaciendaComprobante
 *
 * Formatea la respuesta JSON para listados de comprobantes Hacienda
 */
class HaciendaComprobanteCollection extends ResourceCollection
{
    public $collects = HaciendaComprobanteResource::class;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'pagination' => [
                'total' => $this->total(),
                'count' => $this->count(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
                'path' => $this->path(),
            ],
        ];
    }
}
