<?php
namespace Neos\Flow\Tests\Unit\Core\Booting;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Core\Booting\Scripts;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\SignalSlot\Dispatcher;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the initialization scripts
 */
class ScriptsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function initializeConfigurationInjectsSettingsToPackageManager()
    {
        $mockSignalSlotDispatcher = $this->createMock(Dispatcher::class);
        $mockPackageManager = $this->createMock(PackageManager::class, ['injectSettings'], [], '', false, true);

        $bootstrap = new Bootstrap('Testing');
        $bootstrap->setEarlyInstance(Dispatcher::class, $mockSignalSlotDispatcher);
        $bootstrap->setEarlyInstance(PackageManager::class, $mockPackageManager);

        $mockPackageManager->expects(self::once())->method('injectSettings');

        Scripts::initializeConfiguration($bootstrap);
    }
}
