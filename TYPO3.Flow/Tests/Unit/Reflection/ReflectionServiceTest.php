<?php
namespace TYPO3\Flow\Tests\Unit\Reflection;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\Common\Annotations\Reader;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the ReflectionService
 *
 */
class ReflectionServiceTest extends UnitTestCase {

	/**
	 * @var ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var Reader|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockAnnotationReader;

	public function setUp() {
		$this->reflectionService = $this->getAccessibleMock('TYPO3\Flow\Reflection\ReflectionService', NULL);

		$this->mockAnnotationReader = $this->getMockBuilder('Doctrine\Common\Annotations\Reader')->disableOriginalConstructor()->getMock();
		$this->mockAnnotationReader->expects($this->any())->method('getClassAnnotations')->will($this->returnValue(array()));
		$this->inject($this->reflectionService, 'annotationReader', $this->mockAnnotationReader);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Reflection\Exception\ClassLoadingForReflectionFailedException
	 */
	public function reflectClassThrowsExceptionForNonExistingClasses() {
		$this->reflectionService->_call('reflectClass', 'Non\Existing\Class');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Reflection\Exception\ClassLoadingForReflectionFailedException
	 */
	public function reflectClassThrowsExceptionForFilesWithNoClass() {
		$this->reflectionService->_call('reflectClass', 'TYPO3\Flow\Tests\Unit\Reflection\Fixture\FileWithNoClass');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Reflection\Exception\ClassLoadingForReflectionFailedException
	 */
	public function reflectClassThrowsExceptionForClassesWithNoMatchingFilename() {
		$this->reflectionService->_call('reflectClass', 'TYPO3\Flow\Tests\Unit\Reflection\Fixture\ClassWithDifferentNameDifferent');
	}

	/**
	 * @test
	 */
	public function isTagIgnoredReturnsTrueForIgnoredTags() {
		$settings = array('reflection' => array('ignoredTags' => array('ignored' => TRUE)));
		$this->reflectionService->injectSettings($settings);

		$this->assertTrue($this->reflectionService->_call('isTagIgnored', 'ignored'));
	}

	/**
	 * @test
	 */
	public function isTagIgnoredReturnsFalseForTagsThatAreNotIgnored() {
		$settings = array('reflection' => array('ignoredTags' => array('notignored' => FALSE)));
		$this->reflectionService->injectSettings($settings);

		$this->assertFalse($this->reflectionService->_call('isTagIgnored', 'notignored'));
	}

	/**
	 * @test
	 */
	public function isTagIgnoredReturnsFalseForTagsThatAreNotConfigured() {
		$settings = array('reflection' => array('ignoredTags' => array('ignored' => TRUE, 'notignored' => FALSE)));
		$this->reflectionService->injectSettings($settings);

		$this->assertFalse($this->reflectionService->_call('isTagIgnored', 'notconfigured'));
	}

	/**
	 * @test
	 */
	public function isTagIgnoredWorksWithOldConfiguration() {
		$settings = array('reflection' => array('ignoredTags' => array('ignored')));
		$this->reflectionService->injectSettings($settings);

		$this->assertTrue($this->reflectionService->_call('isTagIgnored', 'ignored'));
		$this->assertFalse($this->reflectionService->_call('isTagIgnored', 'notignored'));
	}
}
