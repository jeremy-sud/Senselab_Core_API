<?php

$file = 'app/Services/Hacienda/Xml/FirmaDigitalService.php';
$content = file_get_contents($file);

$content = str_replace('protected ?array $certData = null;', "/** @var array<string, mixed>|null */\n    protected ?array \$certData = null;", $content);
$content = str_replace('protected function extraerCertificado(string $p12Content, string $pin): array', "/**\n     * @return array<string, mixed>\n     */\n    protected function extraerCertificado(string \$p12Content, string \$pin): array", $content);
$content = str_replace('public function getInformacionCertificado(): array', "/**\n     * @return array<string, mixed>\n     */\n    public function getInformacionCertificado(): array", $content);

file_put_contents($file, $content);
