<?php

$file = 'app/Observers/AuditObserver.php';
$content = file_get_contents($file);

$content = str_replace('protected function createAuditLog(Model $model, string $action, array $oldValues = [], array $newValues = []): void', "/**\n     * @param array<string, mixed> \$oldValues\n     * @param array<string, mixed> \$newValues\n     */\n    protected function createAuditLog(Model \$model, string \$action, array \$oldValues = [], array \$newValues = []): void", $content);
$content = str_replace('protected function detectSensitiveFields(array $oldValues, array $newValues): array', "/**\n     * @param array<string, mixed> \$oldValues\n     * @param array<string, mixed> \$newValues\n     * @return array<int, string>\n     */\n    protected function detectSensitiveFields(array \$oldValues, array \$newValues): array", $content);
$content = str_replace('protected function maskSensitiveValues(array $values, array $sensitiveFields): array', "/**\n     * @param array<string, mixed> \$values\n     * @param array<int, string> \$sensitiveFields\n     * @return array<string, mixed>\n     */\n    protected function maskSensitiveValues(array \$values, array \$sensitiveFields): array", $content);

file_put_contents($file, $content);
