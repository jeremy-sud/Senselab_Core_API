<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('bootstrap/cache')
    ->exclude('storage')
    ->exclude('vendor')
    ->exclude('node_modules')
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@PHP82Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'not_operator_with_successor_space' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
        'phpdoc_scalar' => true,
        'unary_operator_spaces' => true,
        'binary_operator_spaces' => ['default' => 'single_space'],
        'blank_line_before_statement' => [
            'statements' => ['return', 'try', 'throw'],
        ],
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_var_without_name' => true,
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
                'property' => 'one',
            ],
        ],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => true,
        ],
        'single_trait_insert_per_statement' => true,
        'declare_strict_types' => false,
        'no_leading_namespace_whitespace' => true,
        'no_whitespace_in_blank_line' => true,
        'return_type_declaration' => ['space_before' => 'none'],
        'single_blank_line_before_namespace' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true);
