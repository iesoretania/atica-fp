<?php

use Rector\Config\RectorConfig;
use Rector\Doctrine\CodeQuality\Rector\Class_\AddReturnDocBlockToCollectionPropertyGetterByToManyAttributeRector;
use Rector\Symfony\Set\SymfonySetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNativeCallRector;

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
    ->withRules([
        ReturnTypeFromStrictNativeCallRector::class,
        AddVoidReturnTypeWhereNoReturnRector::class,
        AddReturnTypeDeclarationRector::class,
        AddReturnDocBlockToCollectionPropertyGetterByToManyAttributeRector::class,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        doctrineCodeQuality: true,
        symfonyCodeQuality: true
    );
