<?php
namespace TYPO3\Flow\Tests\Unit\Object;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use org\bovigo\vfs\vfsStream;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Object\CompileTimeObjectManager;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\Package;

class CompileTimeObjectManagerTest extends UnitTestCase
{
    /**
     * @var Package\PackageManager
     */
    protected $mockPackageManager;

    /**
     * @var CompileTimeObjectManager
     */
    protected $compileTimeObjectManager;

    /**
     */
    public function setUp()
    {
        vfsStream::setup('Packages');
        $this->mockPackageManager = $this->getMockBuilder(Package\PackageManager::class)->disableOriginalConstructor()->getMock();
        $this->compileTimeObjectManager = $this->getAccessibleMock(CompileTimeObjectManager::class, ['dummy'], [], '', false);
        $this->compileTimeObjectManager->_set('systemLogger', $this->createMock(SystemLoggerInterface::class));
        $configurations = [
            'TYPO3' => [
                'Flow' => [
                    'object' => [
                        'includeClasses' => [
                            'NonFlow.IncludeAllClasses' => ['.*'],
                            'NonFlow.IncludeAndExclude' => ['.*'],
                            'Vendor.AnotherPackage' => ['SomeNonExistingClass']
                        ],
                        'excludeClasses' => [
                            'NonFlow.IncludeAndExclude' => ['.*']
                        ]
                    ]
                ]
            ]
        ];
        $this->compileTimeObjectManager->injectAllSettings($configurations);
    }

    /**
     * @test
     */
    public function flowPackageClassesAreNotFilteredFromObjectManagementByDefault()
    {
        $packagePath = 'vfs://Packages/Vendor.TestPackage/';
        mkdir($packagePath . 'Classes/', 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "vendor/testpackage", "type": "typo3-flow"}');
        file_put_contents($packagePath . 'Classes/Test.php', '<?php ?>');

        $testPackage = new Package\Package($this->mockPackageManager, 'Vendor.TestPackage', $packagePath, 'Classes');

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

        $testPackage = new Package\Package($this->mockPackageManager, 'NonFlow.TestPackage', $packagePath, 'Classes');

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

        $testPackage = new Package\Package($this->mockPackageManager, 'NonFlow.IncludeAllClasses', $packagePath, 'Classes');

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

        $testPackage = new Package\Package($this->mockPackageManager, 'NonFlow.IncludeAndExclude', $packagePath, 'Classes');

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

        $testPackage = new Package\Package($this->mockPackageManager, 'Vendor.AnotherPackage', $packagePath, 'Classes');

        $objectManagementEnabledClasses = $this->compileTimeObjectManager->_call('registerClassFiles', ['Vendor.AnotherPackage' => $testPackage]);
        // Count is at least 1 as '' => 'DateTime' is hardcoded
        $this->assertCount(1, $objectManagementEnabledClasses);
    }
}
