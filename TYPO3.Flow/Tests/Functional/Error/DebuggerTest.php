<?php
namespace TYPO3\Flow\Tests\Functional\Error;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Core\ApplicationContext;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Flow\Error\Debugger;
use TYPO3\Flow\Utility\Arrays;

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
        $configurationOverwrite['Settings']['TYPO3']['Flow']['error']['debugger']['ignoredClasses']['TYPO3\\\\Flow\\\\Core\\\\.*'] = false;
        $newConfiguration = Arrays::arrayMergeRecursiveOverrule($currentConfiguration, $configurationOverwrite);
        ObjectAccess::setProperty($this->configurationManager, 'configurations', $newConfiguration, true);

        $this->assertContains('rootContextString', Debugger::renderDump($object, 10, true));
    }
}
