<?php

/*
 * (c) Contributors of the Neos Project - www.neos.io
 * Please see the LICENSE file which was distributed with this source code.
 */

declare(strict_types=1);

namespace Neos\Flow\Tests\PhpBench\Package;

use Neos\BuildEssentials\PhpBench\FrameworkEnabledBenchmark;
use Neos\Flow\Package\PackageManager;

/**
 *
 */
class PackageManagerBench extends FrameworkEnabledBenchmark
{
    /**
     * @BeforeMethods("bootstrapWithTestRequestHandler")
     * @Revs(5)
     */
    public function benchGetPackageManager()
    {
        $this->flowBootstrap->getObjectManager()->get(PackageManager::class);
    }

    /**
     * @BeforeMethods("bootstrapWithTestRequestHandler")
     * @Revs(5)
     */
    public function benchIsPackageAvailable(): void
    {
        $packageManager = $this->flowBootstrap->getObjectManager()->get(PackageManager::class);
        $packageManager->isPackageAvailable('Neos.Flow');
    }

    /**
     * @BeforeMethods("bootstrapWithTestRequestHandler")
     * @Revs(5)
     */
    public function benchGetPackageKeyFromComposerName(): void
    {
        $packageManager = $this->flowBootstrap->getObjectManager()->get(PackageManager::class);
        $packageManager->getPackageKeyFromComposerName('neos/flow');
    }
}
