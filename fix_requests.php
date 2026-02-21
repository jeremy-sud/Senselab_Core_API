<?php

$dir = __DIR__ . '/app/Http/Requests';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $original = $content;

        // Fix rules()
        $content = preg_replace(
            '/public function rules\(\): array\s*\{/',
            "/**\n     * Get the validation rules that apply to the request.\n     *\n     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>\n     */\n    public function rules(): array\n    {",
            $content
        );

        // Fix messages()
        $content = preg_replace(
            '/public function messages\(\): array\s*\{/',
            "/**\n     * Get the error messages for the defined validation rules.\n     *\n     * @return array<string, string>\n     */\n    public function messages(): array\n    {",
            $content
        );

        // Fix attributes()
        $content = preg_replace(
            '/public function attributes\(\): array\s*\{/',
            "/**\n     * Get custom attributes for validator errors.\n     *\n     * @return array<string, string>\n     */\n    public function attributes(): array\n    {",
            $content
        );

        // Fix withValidator()
        $content = preg_replace(
            '/public function withValidator\(\$validator\)(?:\s*:\s*void)?\s*\{/',
            "/**\n     * Configure the validator instance.\n     *\n     * @param \Illuminate\Validation\Validator \$validator\n     * @return void\n     */\n    public function withValidator(\Illuminate\Validation\Validator \$validator): void\n    {",
            $content
        );

        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            echo "Fixed: " . $file->getFilename() . "\n";
        }
    }
}
