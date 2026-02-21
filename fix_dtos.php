<?php

$dir = __DIR__ . '/app/DTOs';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $original = $content;

        // Fix toArray()
        $content = preg_replace(
            '/public function toArray\(\): array\s*\{/',
            "/**\n     * Convert the DTO to an array.\n     *\n     * @return array<string, mixed>\n     */\n    public function toArray(): array\n    {",
            $content
        );

        // Fix toModelData()
        $content = preg_replace(
            '/public function toModelData\(\): array\s*\{/',
            "/**\n     * Convert the DTO to model data array.\n     *\n     * @return array<string, mixed>\n     */\n    public function toModelData(): array\n    {",
            $content
        );

        // Fix rules()
        $content = preg_replace(
            '/public static function rules\(\): array\s*\{/',
            "/**\n     * Get the validation rules.\n     *\n     * @return array<string, mixed>\n     */\n    public static function rules(): array\n    {",
            $content
        );

        // Fix messages()
        $content = preg_replace(
            '/public static function messages\(\): array\s*\{/',
            "/**\n     * Get the validation messages.\n     *\n     * @return array<string, string>\n     */\n    public static function messages(): array\n    {",
            $content
        );

        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            echo "Fixed: " . $file->getFilename() . "\n";
        }
    }
}
