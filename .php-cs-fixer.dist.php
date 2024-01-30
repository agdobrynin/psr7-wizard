<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in(['src', 'tests'])
    ->exclude([])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PhpCsFixer' => true,
        'declare_strict_types' => true,
        'native_function_invocation' => [
            'include' => ['@all'],
            'scope' => 'all',
        ],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'yoda_style' => [
            'always_move_variable' => true,
        ],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
