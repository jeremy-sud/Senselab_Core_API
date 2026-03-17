<?php

namespace Tests\Unit\Traits;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para el trait ApiResponse — FASE 15
 *
 * Verifica el envelope unificado para todas las respuestas API.
 */
class ApiResponseTest extends TestCase
{
    use ApiResponse;

    // ─── successResponse ─────────────────────────────────────────

    #[Test]
    public function success_response_retorna_json_con_envelope(): void
    {
        $response = $this->successResponse(['id' => 1], 'OK');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $body = $response->getData(true);
        $this->assertTrue($body['success']);
        $this->assertSame('OK', $body['message']);
        $this->assertSame(['id' => 1], $body['data']);
    }

    #[Test]
    public function success_response_omite_message_si_vacio(): void
    {
        $body = $this->successResponse(['x' => 1])->getData(true);

        $this->assertTrue($body['success']);
        $this->assertArrayNotHasKey('message', $body);
    }

    #[Test]
    public function success_response_omite_data_si_null(): void
    {
        $body = $this->successResponse(message: 'Hecho')->getData(true);

        $this->assertTrue($body['success']);
        $this->assertArrayNotHasKey('data', $body);
    }

    #[Test]
    public function success_response_acepta_status_code_personalizado(): void
    {
        $response = $this->successResponse(statusCode: 202);
        $this->assertSame(202, $response->getStatusCode());
    }

    // ─── createdResponse ─────────────────────────────────────────

    #[Test]
    public function created_response_retorna_201(): void
    {
        $response = $this->createdResponse(['id' => 5]);

        $this->assertSame(201, $response->getStatusCode());
        $body = $response->getData(true);
        $this->assertTrue($body['success']);
        $this->assertSame(['id' => 5], $body['data']);
        $this->assertSame('Recurso creado exitosamente', $body['message']);
    }

    #[Test]
    public function created_response_acepta_mensaje_personalizado(): void
    {
        $body = $this->createdResponse(null, 'Factura registrada')->getData(true);
        $this->assertSame('Factura registrada', $body['message']);
    }

    // ─── deletedResponse ─────────────────────────────────────────

    #[Test]
    public function deleted_response_retorna_200_con_mensaje(): void
    {
        $response = $this->deletedResponse();

        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getData(true);
        $this->assertTrue($body['success']);
        $this->assertSame('Recurso eliminado exitosamente', $body['message']);
    }

    // ─── paginatedResponse ───────────────────────────────────────

    #[Test]
    public function paginated_response_incluye_meta(): void
    {
        $items = [['id' => 1], ['id' => 2]];
        $paginator = new LengthAwarePaginator($items, 50, 15, 2);

        $response = $this->paginatedResponse($paginator);

        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getData(true);
        $this->assertTrue($body['success']);
        $this->assertCount(2, $body['data']);
        $this->assertSame(2, $body['meta']['current_page']);
        $this->assertSame(4, $body['meta']['last_page']);
        $this->assertSame(15, $body['meta']['per_page']);
        $this->assertSame(50, $body['meta']['total']);
    }

    // ─── errorResponse ───────────────────────────────────────────

    #[Test]
    public function error_response_retorna_json_con_success_false(): void
    {
        $response = $this->errorResponse('Algo salió mal', 422);

        $this->assertSame(422, $response->getStatusCode());
        $body = $response->getData(true);
        $this->assertFalse($body['success']);
        $this->assertSame('Algo salió mal', $body['message']);
        $this->assertArrayHasKey('trace_id', $body);
    }

    #[Test]
    public function error_response_incluye_errors_cuando_se_proporcionan(): void
    {
        $errors = ['campo' => ['El campo es requerido']];
        $body = $this->errorResponse('Validación fallida', 422, $errors)->getData(true);

        $this->assertSame($errors, $body['errors']);
    }

    #[Test]
    public function error_response_omite_errors_si_null(): void
    {
        $body = $this->errorResponse('Error', 500)->getData(true);
        $this->assertArrayNotHasKey('errors', $body);
    }

    #[Test]
    public function error_response_agrega_header_x_trace_id(): void
    {
        $response = $this->errorResponse('Error', 500);
        $this->assertNotEmpty($response->headers->get('X-Trace-ID'));
    }

    #[Test]
    public function error_response_default_es_500(): void
    {
        $response = $this->errorResponse();
        $this->assertSame(500, $response->getStatusCode());
    }
}
