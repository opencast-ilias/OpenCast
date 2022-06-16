<?php

$config = new PhpCsFixer\Config();
$config->setUsingCache(false);
return $config->setRules([
    '@PSR12' => true,
    'array_syntax' => ['syntax' => 'short']
]);
