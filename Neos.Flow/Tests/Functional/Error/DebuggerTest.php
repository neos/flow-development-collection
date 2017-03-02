<?php
namespace Neos\Flow\Tests\Functional\Error;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\ApplicationContext;
use Neos\Utility\ObjectAccess;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Flow\Error\Debugger;
use Neos\Utility\Arrays;

/**
 * Functional tests for the Debugger
 */
class DebuggerTest extends FunctionalTestCase
{
    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;


    public function setUp()
    {
        parent::setUp();
        $this->configurationManager = $this->objectManager->get(ConfigurationManager::class);
        Debugger::clearState();
    }


    /**
     * @test
     */
    public function ignoredClassesCanBeOverwrittenBySettings()
    {
        $object = new ApplicationContext('Development');
        $this->assertEquals(sprintf('%s prototype object', ApplicationContext::class), Debugger::renderDump($object, 10, true));
        Debugger::clearState();

        $currentConfiguration = ObjectAccess::getProperty($this->configurationManager, 'configurations', true);
        $configurationOverwrite['Settings']['Neos']['Flow']['error']['debugger']['ignoredClasses']['Neos\\\\Flow\\\\Core\\\\.*'] = false;
        $newConfiguration = Arrays::arrayMergeRecursiveOverrule($currentConfiguration, $configurationOverwrite);
        ObjectAccess::setProperty($this->configurationManager, 'configurations', $newConfiguration, true);

        $this->assertContains('rootContextString', Debugger::renderDump($object, 10, true));
    }
}
