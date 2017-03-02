<?php
namespace Neos\Flow\Tests\Unit\ObjectManagement;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use org\bovigo\vfs\vfsStream;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\ObjectManagement\CompileTimeObjectManager;
use Neos\Flow\Package\Package;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Tests\UnitTestCase;

class CompileTimeObjectManagerTest extends UnitTestCase
{
    /**
     * @var PackageManager
     */
    protected $mockPackageManager;

    /**
     * @var CompileTimeObjectManager
     */
    protected $compileTimeObjectManager;

    public function setUp()
    {
        vfsStream::setup('Packages');
        $this->mockPackageManager = $this->getMockBuilder(PackageManager::class)->disableOriginalConstructor()->getMock();
        $this->compileTimeObjectManager = $this->getAccessibleMock(CompileTimeObjectManager::class, array('dummy'), array(), '', false);
        $this->compileTimeObjectManager->_set('systemLogger', $this->createMock(SystemLoggerInterface::class));
        $configurations = array(
            'Neos' => array(
                'Flow' => array(
                    'object' => array(
                        'includeClasses' => array(
                            'NonFlow.IncludeAllClasses' => array('.*'),
                            'NonFlow.IncludeAndExclude' => array('.*'),
                            'Vendor.AnotherPackage' => array('SomeNonExistingClass')
                        ),
                        'excludeClasses' => array(
                            'NonFlow.IncludeAndExclude' => array('.*')
                        )
                    )
                )
            )
        );
        $this->compileTimeObjectManager->injectAllSettings($configurations);
    }

    /**
     * @test
     */
    public function flowPackageClassesAreNotFilteredFromObjectManagementByDefault()
    {
        $packagePath = 'vfs://Packages/Vendor.TestPackage/';
        mkdir($packagePath . 'Classes/', 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "vendor/testpackage", "type": "neos-package"}');
        file_put_contents($packagePath . 'Classes/Test.php', '<?php ?>');

        $testPackage = new Package('Vendor.TestPackage', 'vendor/testpackage', $packagePath, ['psr-4' => ['Vendor\\TestPackage' => 'Classes/']]);

        $objectManagementEnabledClasses = $this->compileTimeObjectManager->_call('registerClassFiles', ['Vendor.TestPackage' => $testPackage]);
        // Count is at least 1 as '' => 'DateTime' is hardcoded
        $this->assertCount(2, $objectManagementEnabledClasses);
        $this->assertArrayHasKey('Vendor.TestPackage', $objectManagementEnabledClasses);
    }

    /**
     * @test
     */
    public function nonFlowPackageClassesAreFilteredFromObjectManagementByDefault()
    {
        $packagePath = 'vfs://Packages/NonFlow.TestPackage/';
        mkdir($packagePath . 'Classes/', 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "vendor/testpackage", "type": "some-non-flow-package-type"}');
        file_put_contents($packagePath . 'Classes/Test.php', '<?php ?>');

        $testPackage = new Package('NonFlow.TestPackage', 'vendor/testpackage', $packagePath, ['psr-0' => ['NonFlow\\TestPackage' => 'Classes/']]);

        $objectManagementEnabledClasses = $this->compileTimeObjectManager->_call('registerClassFiles', ['NonFlow.TestPackage' => $testPackage]);
        // Count is at least 1 as '' => 'DateTime' is hardcoded
        $this->assertCount(1, $objectManagementEnabledClasses);
    }

    /**
     * @test
     */
    public function nonFlowPackageClassesCanBeIncludedInObjectManagementByConfiguration()
    {
        $packagePath = 'vfs://Packages/NonFlow.IncludeAllClasses/';
        mkdir($packagePath . 'Classes/', 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "nonflow/includeallclasses", "type": "some-non-flow-package-type"}');
        file_put_contents($packagePath . 'Classes/Test.php', '<?php ?>');

        $testPackage = new Package('NonFlow.IncludeAllClasses', 'nonflow/includeallclasses', $packagePath, ['psr-4' => ['NonFlow\\IncludeAllClasses' => 'Classes/']]);

        $objectManagementEnabledClasses = $this->compileTimeObjectManager->_call('registerClassFiles', ['NonFlow.IncludeAllClasses' => $testPackage]);
        // Count is at least 1 as '' => 'DateTime' is hardcoded
        $this->assertCount(2, $objectManagementEnabledClasses);
        $this->assertArrayHasKey('NonFlow.IncludeAllClasses', $objectManagementEnabledClasses);
    }

    /**
     * @test
     */
    public function nonFlowPackageClassesExcludedAndIncludedWillNotBeIncluded()
    {
        $packagePath = 'vfs://Packages/NonFlow.IncludeAndExclude/';
        mkdir($packagePath . 'Classes/', 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "nonflow/includeandexclude", "type": "some-non-flow-package-type"}');
        file_put_contents($packagePath . 'Classes/Test.php', '<?php ?>');

        $testPackage = new Package('NonFlow.IncludeAndExclude', 'nonflow/includeandexclude', $packagePath, ['psr-0' => ['NonFlow\\IncludeAndExclude' => 'Classes/']]);

        $objectManagementEnabledClasses = $this->compileTimeObjectManager->_call('registerClassFiles', ['NonFlow.IncludeAndExclude' => $testPackage]);
        // Count is at least 1 as '' => 'DateTime' is hardcoded
        $this->assertCount(1, $objectManagementEnabledClasses);
    }

    /**
     * @test
     */
    public function flowPackageClassesForNonMatchingIncludesAreRemoved()
    {
        $packagePath = 'vfs://Packages/Vendor.AnotherPackage/';
        mkdir($packagePath . 'Classes/', 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "vendor/anotherpackage", "type": "typo3-flow"}');
        file_put_contents($packagePath . 'Classes/Test.php', '<?php ?>');

        $testPackage = new Package('Vendor.AnotherPackage', 'vendor/anotherpackage', $packagePath, ['psr-0' => ['Vendor\\AnotherPackage' => 'Classes/']]);

        $objectManagementEnabledClasses = $this->compileTimeObjectManager->_call('registerClassFiles', ['Vendor.AnotherPackage' => $testPackage]);
        // Count is at least 1 as '' => 'DateTime' is hardcoded
        $this->assertCount(1, $objectManagementEnabledClasses);
    }
}
