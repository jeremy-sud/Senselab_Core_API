<?php

$file = 'app/Services/Hacienda/HaciendaApiClient.php';
$content = file_get_contents($file);

$content = str_replace('protected array $config;', "/** @var array<string, mixed> */\n    protected array \$config;", $content);
$content = str_replace('public function enviarComprobante(array $emisor, array $receptor, string $xmlFirmado): array', "/**\n     * @param array<string, mixed> \$emisor\n     * @param array<string, mixed> \$receptor\n     * @return array<string, mixed>\n     */\n    public function enviarComprobante(array \$emisor, array \$receptor, string \$xmlFirmado): array", $content);
$content = str_replace('public function consultarEstado(string $clave): array', "/**\n     * @return array<string, mixed>\n     */\n    public function consultarEstado(string \$clave): array", $content);
$content = str_replace('public function listarComprobantes(array $filtros = []): array', "/**\n     * @param array<string, mixed> \$filtros\n     * @return array<string, mixed>\n     */\n    public function listarComprobantes(array \$filtros = []): array", $content);
$content = str_replace('public function obtenerComprobante(string $clave): array', "/**\n     * @return array<string, mixed>\n     */\n    public function obtenerComprobante(string \$clave): array", $content);
$content = str_replace('public function get(string $endpoint, array $query = []): array', "/**\n     * @param array<string, mixed> \$query\n     * @return array<string, mixed>\n     */\n    public function get(string \$endpoint, array \$query = []): array", $content);
$content = str_replace('public function post(string $endpoint, array $data = []): array', "/**\n     * @param array<string, mixed> \$data\n     * @return array<string, mixed>\n     */\n    public function post(string \$endpoint, array \$data = []): array", $content);
$content = str_replace('protected function request(string $method, string $endpoint, array $options = []): array', "/**\n     * @param array<string, mixed> \$options\n     * @return array<string, mixed>\n     */\n    protected function request(string \$method, string \$endpoint, array \$options = []): array", $content);
$content = str_replace('protected function extractHeaders(\Illuminate\Http\Client\Response $response): array', "/**\n     * @return array<string, mixed>\n     */\n    protected function extractHeaders(\Illuminate\Http\Client\Response \$response): array", $content);
$content = str_replace('protected function logRequest(string $method, string $url, array $options): void', "/**\n     * @param array<string, mixed> \$options\n     */\n    protected function logRequest(string \$method, string \$url, array \$options): void", $content);
$content = str_replace('protected function logResponse(\Illuminate\Http\Client\Response $response, array $data): void', "/**\n     * @param array<string, mixed> \$data\n     */\n    protected function logResponse(\Illuminate\Http\Client\Response \$response, array \$data): void", $content);

file_put_contents($file, $content);
