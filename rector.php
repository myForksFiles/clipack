<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\ValueObject\PhpVersion;
use RectorLaravel\Set\LaravelLevelSetList;
use RectorLaravel\Set\LaravelSetList;
use Rector\Php80\Rector\ClassMethod\AddParamBasedOnParentClassMethodRector;
use Rector\Transform\Rector\String_\StringToClassConstantRector;
use RectorLaravel\Rector\StaticCall\CarbonToDateFacadeRector;

return RectorConfig::configure()
    ->withPaths([
                    __DIR__ . '/src',
                ])
//    ->withPreparedSets(
//        deadCode: true,
//        codeQuality: true,
//        codingStyle: true,
//    )
    ->withSkip([
                   __DIR__ . '/src',
                   __DIR__ . '/tests',

                   // manual-review-only candidates, keep disabled unless applied in a dedicated batch:
                   // - scope/casts modernization in Eloquent models
                   // - broader signature/type changes in Livewire components
                   // - wide Laravel style sweeps touching auth / routing semantics

                       // Legacy/application-coupled files. They still reference App\\... classes
                    // and should be migrated in dedicated, reviewed batches.
                    __DIR__ . '/src/Commands/ClearCaches.php',
                    __DIR__ . '/src/Commands/CreateUser.php',
                    __DIR__ . '/src/Commands/DiskSpace.php',
                    __DIR__ . '/src/Commands/LogRotate.php',
                    __DIR__ . '/src/Commands/RunSecurityAuditCommand.php',
                    __DIR__ . '/src/Commands/SymfonyLocalPhpSecurityChecker.php',
                    __DIR__ . '/src/Commands/YoutubeFindChannelCommand.php',
                    __DIR__ . '/src/Services/YoutubeChannelResolverService.php',

                    // Project-specific hard skips:
                    // - StringToClassConstantRector can break view('auth.login') into view(Login::class)
                    // - AddParamBasedOnParentClassMethodRector can break framework/component render signatures
                    // - CarbonToDateFacadeRector can change semantics in package code outside a full Laravel app
                    StringToClassConstantRector::class,
                    AddParamBasedOnParentClassMethodRector::class,
                    CarbonToDateFacadeRector::class,

               ])
    ->withPhpVersion(PhpVersion::PHP_83)
    ->withSets([
                   LevelSetList::UP_TO_PHP_83,

                   // bezpieczne reguły "do wersji Laravel", nie tylko jednego upgrade step
                   LaravelLevelSetList::UP_TO_LARAVEL_120,

                   // opcjonalne jakościowe reguły Laravel
                   LaravelSetList::LARAVEL_CODE_QUALITY,
                   LaravelSetList::LARAVEL_COLLECTION,
                   LaravelSetList::LARAVEL_TYPE_DECLARATIONS,
               ])
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withTypeCoverageLevel(1)
    ->withDeadCodeLevel(1)
    ->withCodeQualityLevel(1);
