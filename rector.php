<?php
declare (strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths(
        [
            __DIR__ . '/Neos.Flow/Classes',
            __DIR__ . '/Neos.Flow.Log/Classes',
            __DIR__ . '/Neos.FluidAdaptor/Classes',
            __DIR__ . '/Neos.Http.Factories/Classes',
            __DIR__ . '/Neos.Kickstarter/Classes',
            __DIR__ . '/Neos.Utility.Arrays/Classes',
            __DIR__ . '/Neos.Utility.Files/Classes',
            __DIR__ . '/Neos.Utility.MediaTypes/Classes',
            __DIR__ . '/Neos.Utility.ObjectHandling/Classes',
            __DIR__ . '/Neos.Utility.OpcodeCache/Classes',
            __DIR__ . '/Neos.Utility.Pdo/Classes',
            __DIR__ . '/Neos.Utility.Schema/Classes',
            __DIR__ . '/Neos.Utility.Unicode/Classes',
        ]
    )
    ->withPhpSets(
        php56: true
    );
