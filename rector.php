<?php

use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    // register single rule
    ->withPaths([
        __DIR__ . '/src',
    ])
    /*->withRules([
        TypedPropertyFromStrictConstructorRector::class,
    ])*/
    // here we can define, what prepared sets of rules will be applied
    ->withPhpSets(php82: true)
    ->withAttributesSets(symfony: true, doctrine: true)
    ->withSets([
        SymfonySetList::SYMFONY_54
    ])
    ->withPreparedSets(
        //deadCode: true,
        //codeQuality: true,
        doctrineCodeQuality: true,
        symfonyCodeQuality: true
    );
