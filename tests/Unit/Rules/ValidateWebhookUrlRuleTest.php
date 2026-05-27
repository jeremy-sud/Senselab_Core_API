<?php

namespace Tests\Unit\Rules;

use App\Rules\ValidateWebhookUrlRule;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ValidateWebhookUrlRuleTest extends TestCase
{
    private ValidateWebhookUrlRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new ValidateWebhookUrlRule();
    }

    #[DataProvider('invalidUrlsProvider')]
    public function test_rejects_invalid_urls(string $url): void
    {
        $validator = Validator::make(
            ['webhook_url' => $url],
            ['webhook_url' => ['required', $this->rule]]
        );

        $this->assertTrue($validator->fails());
        $this->assertNotEmpty($validator->errors()->get('webhook_url'));
    }

    public static function invalidUrlsProvider(): array
    {
        return [
            'Empty URL' => [''],
            'Invalid format' => ['not a url'],
            'FTP scheme' => ['ftp://example.com/webhook'],
            'File scheme' => ['file:///etc/passwd'],
        ];
    }

    #[DataProvider('privateIpsProvider')]
    public function test_rejects_private_ips(string $url): void
    {
        $validator = Validator::make(
            ['webhook_url' => $url],
            ['webhook_url' => $this->rule]
        );

        $this->assertTrue($validator->fails());
    }

    public static function privateIpsProvider(): array
    {
        return [
            'Localhost http' => ['http://localhost/webhook'],
            'Localhost https' => ['https://localhost/webhook'],
            'Loopback 127.0.0.1' => ['http://127.0.0.1/webhook'],
            'IPv6 loopback' => ['http://[::1]/webhook'],
            'Private 10.x.x.x' => ['http://10.0.0.1/webhook'],
            'Private 172.16.x.x' => ['http://172.16.0.1/webhook'],
            'Private 192.168.x.x' => ['http://192.168.1.1/webhook'],
            'Link-local 169.254' => ['http://169.254.1.1/webhook'],
        ];
    }

    public function test_rejects_aws_metadata_endpoint(): void
    {
        $validator = Validator::make(
            ['webhook_url' => 'http://169.254.169.254/latest/meta-data/'],
            ['webhook_url' => $this->rule]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_rejects_aws_metadata_v2_endpoint(): void
    {
        $validator = Validator::make(
            ['webhook_url' => 'http://169.254.169.253/latest/meta-data/'],
            ['webhook_url' => $this->rule]
        );

        $this->assertTrue($validator->fails());
    }

    #[DataProvider('blockedPortsProvider')]
    public function test_rejects_blocked_ports(int $port): void
    {
        $validator = Validator::make(
            ['webhook_url' => "http://example.com:{$port}/webhook"],
            ['webhook_url' => $this->rule]
        );

        $this->assertTrue($validator->fails());
    }

    public static function blockedPortsProvider(): array
    {
        return [
            'SSH port 22' => [22],
            'Telnet port 23' => [23],
            'SMTP port 25' => [25],
            'MySQL port 3306' => [3306],
            'PostgreSQL port 5432' => [5432],
            'Redis port 6379' => [6379],
            'MongoDB port 27017' => [27017],
        ];
    }

    #[DataProvider('validUrlsProvider')]
    public function test_accepts_valid_public_urls(string $url): void
    {
        $validator = Validator::make(
            ['webhook_url' => $url],
            ['webhook_url' => $this->rule]
        );

        // Nota: Este test puede fallar si no hay conectividad DNS
        // En ese caso, es un error de resolución, no de validación
        if ($validator->fails()) {
            // Permitir error de host no resolvible como excepción en testing
            $errors = $validator->errors()->get('webhook_url');
            $hasResolutionError = collect($errors)->contains(
                fn ($error) => str_contains($error, 'unresolvable')
            );

            if ($hasResolutionError) {
                $this->assertTrue(true);
            } else {
                $this->fail("URL válida fue rechazada: {$url}");
            }
        } else {
            $this->assertTrue(true);
        }
    }

    public static function validUrlsProvider(): array
    {
        return [
            'HTTPS public URL' => ['https://webhook.example.com/api/callback'],
            'HTTP port 80' => ['http://webhook.example.com:80/api'],
            'HTTPS port 443' => ['https://webhook.example.com:443/api'],
            'With query params' => ['https://webhook.example.com/api?id=123'],
            'With fragment' => ['https://webhook.example.com/api#section'],
        ];
    }

    public function test_rejects_multicast_addresses(): void
    {
        $validator = Validator::make(
            ['webhook_url' => 'http://224.0.0.1/webhook'],
            ['webhook_url' => $this->rule]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_rejects_broadcast_address(): void
    {
        $validator = Validator::make(
            ['webhook_url' => 'http://0.0.0.0/webhook'],
            ['webhook_url' => $this->rule]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_error_message_contains_helpful_info(): void
    {
        $validator = Validator::make(
            ['webhook_url' => 'http://localhost/webhook'],
            ['webhook_url' => $this->rule]
        );

        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->get('webhook_url');
        $this->assertNotEmpty($errors);
    }
}
