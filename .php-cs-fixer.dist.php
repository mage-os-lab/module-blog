<?php
return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@PHP82Migration' => true,
        'declare_strict_types' => true,
        'native_function_invocation' => ['include' => ['@compiler_optimized']],
        'ordered_imports' => true,
        'no_unused_imports' => true,
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_order' => true,
        'return_type_declaration' => ['space_before' => 'none'],
    ])
    ->setFinder(
        (new PhpCsFixer\Finder())
            ->in(['Api', 'Controller', 'Model', 'Test', 'Ui'])
            ->notPath('#/registration\.php$#')
    );
