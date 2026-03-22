<?php

namespace Tests\Contract;

use PhpPact\Consumer\InteractionBuilder;
use PhpPact\Consumer\Matcher\Matcher;
use PhpPact\Consumer\Model\ConsumerRequest;
use PhpPact\Consumer\Model\ProviderResponse;
use PhpPact\Standalone\MockService\MockServerConfig;
use PHPUnit\Framework\TestCase;

/**
 * Base class for Pact consumer contract tests.
 *
 * Each consumer test defines expected interactions (request/response pairs)
 * against the Ursol CAST API provider. These generate Pact JSON contracts
 * in tests/Contract/pacts/ that can be verified against the real provider.
 */
abstract class PactTestCase extends TestCase
{
    protected InteractionBuilder $builder;
    protected MockServerConfig $config;
    protected Matcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new MockServerConfig();
        $this->config->setConsumer($this->getConsumerName());
        $this->config->setProvider('UrsolCastApi');
        $this->config->setPactDir(__DIR__ . '/pacts');
        $this->config->setPactSpecificationVersion('4.0.0');
        $this->config->setHost('127.0.0.1');
        $this->config->setPort(0); // Auto-assign port

        $this->builder = new InteractionBuilder($this->config);
        $this->matcher = new Matcher();
    }

    /**
     * Consumer name for the Pact contract.
     * Override in subclasses for different frontend consumers.
     */
    protected function getConsumerName(): string
    {
        return 'UrsolCastFrontend';
    }

    /**
     * Make an HTTP request to the Pact mock server simulating the consumer.
     * Must be called after willRespondWith() starts the mock server.
     *
     * @return array{status: int, body: mixed}
     */
    protected function callMockServer(string $method, string $path, ?array $jsonBody = null, bool $withAuth = true): array
    {
        $baseUri = (string) $this->config->getBaseUri();
        $url = $baseUri . $path;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $headers = ['Accept: application/json'];
        if ($withAuth) {
            $headers[] = 'Authorization: Bearer valid-test-token';
        }

        if ($jsonBody !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonBody, JSON_THROW_ON_ERROR));
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $body = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status' => $statusCode,
            'body' => $body ? json_decode($body, true) : null,
        ];
    }

    /**
     * Standard success envelope used by all API responses.
     */
    protected function successEnvelope(mixed $data): array
    {
        return [
            'success' => $this->matcher->boolean(true),
            'data' => $data,
        ];
    }

    /**
     * Paginated success envelope.
     */
    protected function paginatedEnvelope(mixed $itemShape): array
    {
        return [
            'success' => $this->matcher->boolean(true),
            'data' => $this->matcher->eachLike($itemShape),
            'meta' => [
                'current_page' => $this->matcher->integerV3(1),
                'last_page' => $this->matcher->integerV3(1),
                'per_page' => $this->matcher->integerV3(15),
                'total' => $this->matcher->integerV3(1),
            ],
        ];
    }

    /**
     * Standard error envelope.
     */
    protected function errorEnvelope(string $message = 'Error'): array
    {
        return [
            'success' => $this->matcher->boolean(false),
            'message' => $this->matcher->like($message),
        ];
    }

    /**
     * Create a consumer request with standard auth headers.
     */
    protected function authenticatedRequest(string $method, string $path): ConsumerRequest
    {
        $request = new ConsumerRequest();
        $request->setMethod($method);
        $request->setPath($path);
        $request->addHeader('Authorization', 'Bearer valid-test-token');
        $request->addHeader('Accept', 'application/json');

        return $request;
    }

    /**
     * Create a JSON provider response.
     */
    protected function jsonResponse(int $status, array $body): ProviderResponse
    {
        $response = new ProviderResponse();
        $response->setStatus($status);
        $response->addHeader('Content-Type', 'application/json');
        $response->setBody($body);

        return $response;
    }

    /**
     * ISO 8601 datetime matcher.
     */
    protected function iso8601(): \PhpPact\Consumer\Matcher\Matchers\Regex
    {
        return $this->matcher->dateTimeISO8601();
    }
}
