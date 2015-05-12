<?php
namespace TYPO3\Flow\Tests\Unit\Object;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use org\bovigo\vfs\vfsStream;


class CompileTimeObjectManagerTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Package\PackageManager
	 */
	protected $mockPackageManager;

	/**
	 * @var \TYPO3\Flow\Object\CompileTimeObjectManager
	 */
	protected $compileTimeObjectManager;

	/**
	 */
	public function setUp() {
		vfsStream::setup('Packages');
		$this->mockPackageManager = $this->getMockBuilder('TYPO3\Flow\Package\PackageManager')->disableOriginalConstructor()->getMock();
		$this->compileTimeObjectManager = $this->getAccessibleMock('TYPO3\Flow\Object\CompileTimeObjectManager', array('dummy'), array(), '', FALSE);
		$this->compileTimeObjectManager->_set('systemLogger', $this->getMock('TYPO3\Flow\Log\SystemLoggerInterface'));
		$configurations = array(
			'TYPO3' => array(
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
	public function flowPackageClassesAreNotFilteredFromObjectManagementByDefault() {
		$packagePath = 'vfs://Packages/Vendor.TestPackage/';
		mkdir($packagePath . 'Classes/', 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "vendor/testpackage", "type": "typo3-flow"}');
		file_put_contents($packagePath . 'Classes/Test.php', '<?php ?>');

		$testPackage = new \TYPO3\Flow\Package\Package($this->mockPackageManager, 'Vendor.TestPackage', $packagePath, 'Classes');

		$objectManagementEnabledClasses = $this->compileTimeObjectManager->_call('registerClassFiles', array('Vendor.TestPackage' => $testPackage));
		// Count is at least 1 as '' => 'DateTime' is hardcoded
		$this->assertCount(2, $objectManagementEnabledClasses);
		$this->assertArrayHasKey('Vendor.TestPackage', $objectManagementEnabledClasses);
	}

	/**
	 * @test
	 */
	public function nonFlowPackageClassesAreFilteredFromObjectManagementByDefault() {
		$packagePath = 'vfs://Packages/Vendor.TestPackage/';
		mkdir($packagePath . 'Classes/', 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "vendor/testpackage", "type": "some-non-flow-package-type"}');
		file_put_contents($packagePath . 'Classes/Test.php', '<?php ?>');

		$testPackage = new \TYPO3\Flow\Package\Package($this->mockPackageManager, 'Vendor.TestPackage', $packagePath, 'Classes');

		$objectManagementEnabledClasses = $this->compileTimeObjectManager->_call('registerClassFiles', array('Vendor.TestPackage' => $testPackage));
		// Count is at least 1 as '' => 'DateTime' is hardcoded
		$this->assertCount(1, $objectManagementEnabledClasses);
	}

	/**
	 * @test
	 */
	public function nonFlowPackageClassesCanBeIncludedInObjectManagementByConfiguration() {
		$packagePath = 'vfs://Packages/NonFlow.IncludeAllClasses/';
		mkdir($packagePath . 'Classes/', 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "nonflow/includeallclasses", "type": "some-non-flow-package-type"}');
		file_put_contents($packagePath . 'Classes/Test.php', '<?php ?>');

		$testPackage = new \TYPO3\Flow\Package\Package($this->mockPackageManager, 'NonFlow.IncludeAllClasses', $packagePath, 'Classes');

		$objectManagementEnabledClasses = $this->compileTimeObjectManager->_call('registerClassFiles', array('NonFlow.IncludeAllClasses' => $testPackage));
		// Count is at least 1 as '' => 'DateTime' is hardcoded
		$this->assertCount(2, $objectManagementEnabledClasses);
		$this->assertArrayHasKey('NonFlow.IncludeAllClasses', $objectManagementEnabledClasses);
	}

	/**
	 * @test
	 */
	public function nonFlowPackageClassesExcludedAndIncludedWillNotBeIncluded() {
		$packagePath = 'vfs://Packages/NonFlow.IncludeAndExclude/';
		mkdir($packagePath . 'Classes/', 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "nonflow/includeandexclude", "type": "some-non-flow-package-type"}');
		file_put_contents($packagePath . 'Classes/Test.php', '<?php ?>');

		$testPackage = new \TYPO3\Flow\Package\Package($this->mockPackageManager, 'NonFlow.IncludeAndExclude', $packagePath, 'Classes');

		$objectManagementEnabledClasses = $this->compileTimeObjectManager->_call('registerClassFiles', array('NonFlow.IncludeAndExclude' => $testPackage));
		// Count is at least 1 as '' => 'DateTime' is hardcoded
		$this->assertCount(1, $objectManagementEnabledClasses);
	}

	/**
	 * @test
	 */
	public function flowPackageClassesForNonMatchingIncludesAreRemoved() {
		$packagePath = 'vfs://Packages/Vendor.AnotherPackage/';
		mkdir($packagePath . 'Classes/', 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "vendor/anotherpackage", "type": "typo3-flow"}');
		file_put_contents($packagePath . 'Classes/Test.php', '<?php ?>');

		$testPackage = new \TYPO3\Flow\Package\Package($this->mockPackageManager, 'Vendor.AnotherPackage', $packagePath, 'Classes');

		$objectManagementEnabledClasses = $this->compileTimeObjectManager->_call('registerClassFiles', array('Vendor.AnotherPackage' => $testPackage));
		// Count is at least 1 as '' => 'DateTime' is hardcoded
		$this->assertCount(1, $objectManagementEnabledClasses);
	}

}