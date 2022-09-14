<?php

$finder = PhpCsFixer\Finder::create()
                           ->in(array(
                               __DIR__ . '/../../classes',
                               __DIR__ . '/../../src',
                               __DIR__ . '/../../sql'
                           ));

$config = new PhpCsFixer\Config();
$config->setUsingCache(false);
return $config->setRules([
    '@PSR12' => true,
    'strict_param' => false,
    'concat_space' => ['spacing' => 'one'],
    'function_typehint_space' => true,
    'array_syntax' => ['syntax' => 'short']
])->setFinder($finder);
