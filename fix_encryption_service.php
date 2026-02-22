<?php

$file = 'app/Services/EncryptionService.php';
$content = file_get_contents($file);

$content = str_replace('public function encryptFields(array $data, string $modelClass): array', "/**\n     * @param array<string, mixed> \$data\n     * @return array<string, mixed>\n     */\n    public function encryptFields(array \$data, string \$modelClass): array", $content);
$content = str_replace('public function decryptFields(array $data, string $modelClass): array', "/**\n     * @param array<string, mixed> \$data\n     * @return array<string, mixed>\n     */\n    public function decryptFields(array \$data, string \$modelClass): array", $content);
$content = str_replace('public function getEncryptedFields(string $modelClass): array', "/**\n     * @return array<int, string>\n     */\n    public function getEncryptedFields(string \$modelClass): array", $content);
$content = str_replace('public function getStatistics(): array', "/**\n     * @return array<string, mixed>\n     */\n    public function getStatistics(): array", $content);

file_put_contents($file, $content);
