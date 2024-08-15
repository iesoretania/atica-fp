<?php

use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Symfony\Symfony62\Rector\Class_\SecurityAttributeToIsGrantedAttributeRector;

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
        /*SymfonySetList::SYMFONY_60,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION*/
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES
    ])
    ->withRules([
        //ReturnTypeFromStrictNativeCallRector::class,
        //AddVoidReturnTypeWhereNoReturnRector::class,
        //AddReturnTypeDeclarationRector::class,
        SecurityAttributeToIsGrantedAttributeRector::class
    ])
    ->withPreparedSets(
        /*deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        doctrineCodeQuality: true,
        symfonyCodeQuality: true*/
    );
