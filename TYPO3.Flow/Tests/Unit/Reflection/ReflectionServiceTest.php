<?php
namespace TYPO3\FLOW3\Tests\Unit\Reflection;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once('Fixture/DummyInterface1.php');
require_once('Fixture/DummyInterface2.php');
require_once('Fixture/DummyInterface3.php');
require_once('Fixture/ImplementationOfDummyInterface1.php');
require_once('Fixture/Implementation1OfDummyInterface3.php');
require_once('Fixture/Implementation2OfDummyInterface3.php');
require_once('Fixture/ParentClass1.php');
require_once('Fixture/SubClassOfParentClass1.php');
require_once('Fixture/SubClassOfSubClassOfParentClass1.php');
require_once('Fixture/ProxyOfImplementationOfDummyInterface1.php');
require_once('Fixture/TaggedClass1.php');
require_once('Fixture/TaggedClass2.php');
require_once('Fixture/TaggedClass3.php');
require_once('Fixture/DummyClass.php');
require_once('Fixture/DummyAbstractClass.php');
require_once('Fixture/DummyFinalClass.php');
require_once('Fixture/DummyClassWithMethods.php');
require_once('Fixture/DummyClassWithProperties.php');
require_once('Fixture/Model/Entity.php');
require_once('Fixture/Model/ValueObject.php');
require_once('Fixture/Repository/NonstandardEntityRepository.php');

/**
 * testcase for the Reflection Service
 *
 */
class ReflectionServiceTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function isClassReflectedTellsIfTheReflectionServiceKnowsTheSpecifiedClass() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$reflectedClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClass' => time(),
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('dummy'));
		$reflectionService->_set('reflectedClassNames', $reflectedClassNames);

		$this->assertTrue($reflectionService->isClassReflected('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClass'));
		$this->assertFalse($reflectionService->isClassReflected('TYPO3\Virtual\UnknownClass'));
	}

	/**
	 * @test
	 */
	public function getAllClassNamesReturnsNamesOfAllReflectedClasses() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$reflectedClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClass' => time(),
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('dummy'));
		$reflectionService->_set('reflectedClassNames', $reflectedClassNames);

		$this->assertSame(array_keys($reflectedClassNames), $reflectionService->getAllClassNames());
	}

	/**
	 * @test
	 */
	public function getDefaultImplementationClassNameForInterfaceReturnsClassNameOfOnlyClassImplementingTheInterface() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$classNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyInterface1',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyInterface2',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1'
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($classNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();

		$className = $reflectionService->getDefaultImplementationClassNameForInterface('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyInterface1');

		$this->assertEquals('TYPO3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1', $className);
	}

	/**
	 * @test
	 */
	public function getDefaultImplementationClassNameForInterfaceReturnsFalseIfNoClassImplementsTheInterface() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$classNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyInterface1',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyInterface2',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1'
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($classNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();
		$className = $reflectionService->getDefaultImplementationClassNameForInterface('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyInterface2');

		$this->assertFalse($className);
	}

	/**
	 * If two classes implement an interface, the Reflection Service checks if one of them is
	 * a proxy (implements the Proxy marker interface). If that is the case, it's sure that the
	 * other class is the original (target) class. In case these conditions are met, the name
	 * of the proxy class is returned.
	 *
	 * @test
	 * @see http://typo3.org/go/issue/3027
	 */
	public function getDefaultImplementationClassNameForInterfaceReturnsClassNameOfTheProxyIfTwoClassesWereFound() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$classNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyInterface1',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\ProxyOfImplementationOfDummyInterface1',
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($classNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($classNames);
		$className = $reflectionService->getDefaultImplementationClassNameForInterface('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyInterface1');
		$this->assertEquals('TYPO3\FLOW3\Tests\Reflection\Fixture\ProxyOfImplementationOfDummyInterface1', $className, 'Proxy registered second.');

		$classNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyInterface1',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\ProxyOfImplementationOfDummyInterface1',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1',
		);

		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($classNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();
		$className = $reflectionService->getDefaultImplementationClassNameForInterface('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyInterface1');

		$this->assertEquals('TYPO3\FLOW3\Tests\Reflection\Fixture\ProxyOfImplementationOfDummyInterface1', $className, 'Proxy registered first.');
	}

	/**
	 * @test
	 */
	public function getAllImplementationClassNamesForInterfaceReturnsAllNamesOfClassesImplementingTheInterface() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$availableClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyInterface3',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\Implementation1OfDummyInterface3',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\Implementation2OfDummyInterface3'
		);

		$expectedClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\Implementation1OfDummyInterface3',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\Implementation2OfDummyInterface3'
		);

		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($availableClassNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();
		$detectedClassNames = $reflectionService->getAllImplementationClassNamesForInterface('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyInterface3');

		$this->assertEquals($expectedClassNames, $detectedClassNames);
	}

	/**
	 * @test
	 */
	public function getAllImplementationClassNamesForInterfaceReturnsEmptyArrayIfNoClassImplementsTheInterface() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$availableClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyInterface3',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyInterface2',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\Implementation1OfDummyInterface3',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\Implementation2OfDummyInterface3'
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($availableClassNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();

		$detectedClassNames = $reflectionService->getAllImplementationClassNamesForInterface('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyInterface2');
		$this->assertEquals(array(), $detectedClassNames);
	}

	/**
	 * @test
	 */
	public function getAllSubClassNamesForClassReturnsEmptyArrayIfNoClassInheritsTheClass() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$availableClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClass',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\ParentClass1'
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($availableClassNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();

		$detectedClassNames = $reflectionService->getAllSubClassNamesForClass('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClass');
		$this->assertEquals(array(), $detectedClassNames);
	}

	/**
	 * @test
	 */
	public function getAllSubClassNamesForClassReturnsArrayOfSubClasses() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$availableClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClass',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\ParentClass1',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\SubClassOfParentClass1',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\SubClassOfSubClassOfParentClass1',
		);

		$expectedClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\SubClassOfParentClass1',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\SubClassOfSubClassOfParentClass1',
		);

		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($availableClassNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();

		$detectedClassNames = $reflectionService->getAllSubClassNamesForClass('TYPO3\FLOW3\Tests\Reflection\Fixture\ParentClass1');
		$this->assertEquals($expectedClassNames, $detectedClassNames);
	}

	/**
	 * @test
	 */
	public function getClassNamesByTagReturnsArrayOfClassesTaggedBySpecifiedTag() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$availableClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\TaggedClass1',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\TaggedClass2',
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($availableClassNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();

		$detectedClassNames = $reflectionService->getClassNamesByTag('sometag1');
		$this->assertEquals(array('TYPO3\FLOW3\Tests\Reflection\Fixture\TaggedClass1'), $detectedClassNames);

		$detectedClassNames = $reflectionService->getClassNamesByTag('sometag2');
		$this->assertEquals(array('TYPO3\FLOW3\Tests\Reflection\Fixture\TaggedClass2'), $detectedClassNames);
	}

	/**
	 * @test
	 */
	public function getClassTagsValuesReturnsArrayOfTagsAndValuesOfAClass() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$availableClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\TaggedClass3',
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($availableClassNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();

		$expectedTags = array('firsttag' => array(), 'secondtag' => array('1', '2'), 'thirdtag' => array('one, two', 'three, four'));
		$detectedTags = $reflectionService->getClassTagsValues('TYPO3\FLOW3\Tests\Reflection\Fixture\TaggedClass3');
		ksort($detectedTags);
		$this->assertEquals($expectedTags, $detectedTags);
	}

	/**
	 * @test
	 */
	public function getClassTagValuesReturnsArrayOfValuesOfASpecificClassTag() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$availableClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\TaggedClass3',
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($availableClassNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();

		$expectedValues = array('one, two', 'three, four');
		$detectedValues = $reflectionService->getClassTagValues('TYPO3\FLOW3\Tests\Reflection\Fixture\TaggedClass3', 'thirdtag');
		ksort($detectedValues);
		$this->assertEquals($expectedValues, $detectedValues);
	}

	/**
	 * @test
	 */
	public function isClassTaggedWithReturnsTrueIfClassIsTaggedWithSpecifiedTag() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$availableClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\TaggedClass1',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\TaggedClass2',
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($availableClassNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();

		$this->assertTrue($reflectionService->isClassTaggedWith('TYPO3\FLOW3\Tests\Reflection\Fixture\TaggedClass1', 'sometag1'));
		$this->assertFalse($reflectionService->isClassTaggedWith('TYPO3\FLOW3\Tests\Reflection\Fixture\TaggedClass1', 'sometag2'));
		$this->assertTrue($reflectionService->isClassTaggedWith('TYPO3\FLOW3\Tests\Reflection\Fixture\TaggedClass2', 'sometag2'));
	}

	/**
	 * @test
	 */
	public function isClassAbstractTellsIfAClassIsAbstract() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$availableClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClass',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyAbstractClass',
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($availableClassNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();

		$this->assertTrue($reflectionService->isClassAbstract('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyAbstractClass'));
		$this->assertFalse($reflectionService->isClassAbstract('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClass'));
	}

	/**
	 * @test
	 */
	public function isClassFinalTellsIfAClassIsFinal() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$availableClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClass',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyFinalClass',
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($availableClassNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();

		$this->assertTrue($reflectionService->isClassFinal('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyFinalClass'));
		$this->assertFalse($reflectionService->isClassFinal('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClass'));
	}

	/**
	 * @test
	 */
	public function getClassMethodNamesReturnsNamesOfAllMethodsOfAClass() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$availableClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClass',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClassWithMethods',
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($availableClassNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();

		$expectedMethodNames = array('firstMethod', 'secondMethod');
		$detectedMethodNames = $reflectionService->getClassMethodNames('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClassWithMethods');
		$this->assertEquals($expectedMethodNames, $detectedMethodNames);
	}

	/**
	 * @test
	 */
	public function getClassPropertyNamesReturnsNamesOfAllPropertiesOfAClass() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$availableClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClass',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties',
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($availableClassNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();

		$expectedPropertyNames = array('firstProperty', 'secondProperty');
		$detectedPropertyNames = $reflectionService->getClassPropertyNames('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties');
		$this->assertEquals($expectedPropertyNames, $detectedPropertyNames);
	}

	/**
	 * @test
	 */
	public function getMethodTagsValuesReturnsArrayOfTagsAndValuesOfAMethod() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$availableClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClassWithMethods',
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($availableClassNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();

		$expectedTags = array('firsttag' => array(), 'return' => array('void'), 'secondtag' => array('a', 'b'), 'param' => array('string $arg1 Argument 1 documentation'));
		$detectedTags = $reflectionService->getMethodTagsValues('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClassWithMethods', 'firstMethod');
		ksort($detectedTags);
		$this->assertEquals($expectedTags, $detectedTags);
	}

	/**
	 * @test
	 */
	public function getMethodParametersReturnsAnArrayOfParameterNamesAndAdditionalInformation() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$availableClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClassWithMethods',
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($availableClassNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();

		$expectedParameters = array(
			'arg1' => array('position' => 0, 'byReference' => FALSE, 'array' => FALSE, 'optional' => FALSE, 'class' => NULL, 'allowsNull' => TRUE, 'type' => 'string'),
			'arg2' => array('position' => 1, 'byReference' => TRUE, 'array' => FALSE, 'optional' => FALSE, 'class' => NULL, 'allowsNull' => TRUE),
			'arg3' => array('position' => 2, 'byReference' => FALSE, 'array' => FALSE, 'optional' => FALSE, 'class' => 'stdClass', 'allowsNull' => FALSE, 'type' => 'stdClass'),
			'arg4' => array('position' => 3, 'byReference' => FALSE, 'array' => FALSE, 'optional' => TRUE, 'class' => NULL, 'allowsNull' => TRUE, 'defaultValue' => 'default')
		);

		$actualParameters = $reflectionService->getMethodParameters('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClassWithMethods', 'firstMethod');
		$this->assertEquals($expectedParameters, $actualParameters);
	}

	/**
	 * @test
	 */
	public function getPropertyNamesByTagReturnsArrayOfPropertiesTaggedBySpecifiedTag() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$availableClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClass',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties',
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($availableClassNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();

		$expectedPropertyNames = array('firstProperty');
		$detectedPropertyNames = $reflectionService->getPropertyNamesByTag('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties', 'firsttag');
		$this->assertEquals($expectedPropertyNames, $detectedPropertyNames);
	}

	/**
	 * @test
	 */
	public function getPropertyNamesByTagReturnsEmptyArrayIfNoPropertiesTaggedBySpecifiedTagWhereFound() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$availableClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClass',
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties',
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($availableClassNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();

		$expectedPropertyNames = array();
		$detectedPropertyNames = $reflectionService->getPropertyNamesByTag('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClass', 'firsttag');
		$this->assertEquals($expectedPropertyNames, $detectedPropertyNames);

		$detectedPropertyNames = $reflectionService->getPropertyNamesByTag('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties', 'tagnothere');
		$this->assertEquals($expectedPropertyNames, $detectedPropertyNames);
	}

	/**
	 * @test
	 */
	public function getPropertyTagsValuesReturnsArrayOfTagsAndValuesOfAProperty() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$availableClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties',
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($availableClassNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();

		$expectedTags = array('firsttag' => array(), 'secondtag' => array('x', 'y'), 'var' => array('mixed'));
		$detectedTags = $reflectionService->getPropertyTagsValues('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties', 'firstProperty');
		ksort($detectedTags);
		$this->assertEquals($expectedTags, $detectedTags);
	}

	/**
	 * @test
	 */
	public function getPropertyTagValuesReturnsArrayOfValuesOfAPropertysTag() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$availableClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties',
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($availableClassNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();

		$expectedValues = array('x', 'y');
		$detectedValues = $reflectionService->getPropertyTagValues('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties', 'firstProperty', 'secondtag');
		ksort($detectedValues);
		$this->assertEquals($expectedValues, $detectedValues);
	}

	/**
	 * @test
	 */
	public function isPropertyTaggedWithReturnsTrueIfTheSpecifiedClassPropertyIsTaggedWithTheGivenTag() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$availableClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties',
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($availableClassNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();

		$this->assertTrue($reflectionService->isPropertyTaggedWith('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties', 'firstProperty', 'firsttag'));
		$this->assertFalse($reflectionService->isPropertyTaggedWith('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties', 'firstProperty', 'nothing'));
		$this->assertFalse($reflectionService->isPropertyTaggedWith('TYPO3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties', 'noProperty', 'firsttag'));
	}

	/**
	 * @test
	 */
	public function isClassImplementationOfReturnsTrueIfClassImplementsSpecifiedInterface() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$availableClassNames = array(
			'TYPO3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1'
		);
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'detectAvailableClassNames'));
		$reflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue($availableClassNames));

		$reflectionService->setStatusCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize();

		$this->assertTrue($reflectionService->isClassImplementationOf('TYPO3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1', 'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyInterface1'));
		$this->assertFalse($reflectionService->isClassImplementationOf('TYPO3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1', 'TYPO3\FLOW3\Tests\Reflection\Fixture\DummyInterface2'));
	}

	/**
	 * @test
	 */
	public function classSchemaOnlyContainsNonTransientProperties() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('isClassReflected', 'isClassTaggedWith'));
		$reflectionService->_call('buildClassSchemata', array('TYPO3\FLOW3\Tests\Reflection\Fixture\Model\Entity'));

		$expectedProperties = array('someString', 'someInteger', 'someFloat', 'someDate', 'someBoolean', 'someIdentifier', 'someSplObjectStorage');

		$builtClassSchemata = $reflectionService->getClassSchemata();
		$builtClassSchema = array_pop($builtClassSchemata);
		$actualProperties = array_keys($builtClassSchema->getProperties());
		sort($expectedProperties);
		sort($actualProperties);
		$this->assertEquals($expectedProperties, $actualProperties, 'not same');
	}

	/**
	 * @test
	 */
	public function propertyDataIsDetectedFromVarAnnotations() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('isClassReflected', 'isClassTaggedWith'));
		$reflectionService->_call('buildClassSchemata', array('TYPO3\FLOW3\Tests\Reflection\Fixture\Model\Entity'));

		$expectedProperties = array(
			'someBoolean' => array('type' => 'boolean', 'elementType' => NULL, 'lazy' => FALSE),
			'someString' => array('type' => 'string', 'elementType' => NULL, 'lazy' => FALSE),
			'someInteger' => array('type' => 'integer', 'elementType' => NULL, 'lazy' => FALSE),
			'someFloat' => array('type' => 'float', 'elementType' => NULL, 'lazy' => FALSE),
			'someDate' => array('type' => 'DateTime', 'elementType' => NULL, 'lazy' => FALSE),
			'someSplObjectStorage' => array('type' => 'SplObjectStorage', 'elementType' => NULL, 'lazy' => TRUE),
			'someIdentifier' => array('type' => 'string', 'elementType' => NULL, 'lazy' => FALSE)
		);

		$builtClassSchemata = $reflectionService->getClassSchemata();
		$builtClassSchema = array_pop($builtClassSchemata);
		$actualProperties = $builtClassSchema->getProperties();
		asort($expectedProperties);
		asort($actualProperties);
		$this->assertEquals($expectedProperties, $actualProperties);
	}

	/**
	 * @test
	 */
	public function modelTypeEntityIsRecognizedByEntityAnnotation() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('isClassReflected', 'isClassTaggedWith'));
		$reflectionService->expects($this->at(0))->method('isClassTaggedWith')->with('TYPO3\FLOW3\Tests\Reflection\Fixture\Model\Entity', 'entity')->will($this->returnValue(TRUE));
		$reflectionService->_call('buildClassSchemata', array('TYPO3\FLOW3\Tests\Reflection\Fixture\Model\Entity'));

		$builtClassSchemata = $reflectionService->getClassSchemata();
		$builtClassSchema = array_pop($builtClassSchemata);
		$this->assertEquals($builtClassSchema->getModelType(), \TYPO3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY);
	}

	/**
	 * @test
	 */
	public function modelTypeValueObjectIsRecognizedByValueObjectAnnotation() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('isClassReflected', 'isClassTaggedWith', 'checkValueObjectRequirements'));
		$reflectionService->expects($this->at(0))->method('isClassTaggedWith')->with('TYPO3\FLOW3\Tests\Reflection\Fixture\Model\ValueObject', 'entity')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->at(1))->method('isClassTaggedWith')->with('TYPO3\FLOW3\Tests\Reflection\Fixture\Model\ValueObject', 'valueobject')->will($this->returnValue(TRUE));
		$reflectionService->_call('buildClassSchemata', array('TYPO3\FLOW3\Tests\Reflection\Fixture\Model\ValueObject'));

		$builtClassSchemata = $reflectionService->getClassSchemata();
		$builtClassSchema = array_pop($builtClassSchemata);
		$this->assertEquals($builtClassSchema->getModelType(), \TYPO3\FLOW3\Reflection\ClassSchema::MODELTYPE_VALUEOBJECT);
	}

	/**
	 * @test
	 */
	public function modelTypeValueObjectTriggersCheckValueObjectRequirementsCall() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('isClassReflected', 'isClassTaggedWith', 'checkValueObjectRequirements'));
		$reflectionService->expects($this->any())->method('isClassTaggedWith')->will($this->onConsecutiveCalls(FALSE, TRUE));
		$reflectionService->expects($this->once())->method('checkValueObjectRequirements')->with('TYPO3\FLOW3\Tests\Reflection\Fixture\Model\ValueObject');
		$reflectionService->_call('buildClassSchemata', array('TYPO3\FLOW3\Tests\Reflection\Fixture\Model\ValueObject'));

		$builtClassSchemata = $reflectionService->getClassSchemata();
		$builtClassSchema = array_pop($builtClassSchemata);
		$this->assertEquals($builtClassSchema->getModelType(), \TYPO3\FLOW3\Reflection\ClassSchema::MODELTYPE_VALUEOBJECT);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Reflection\Exception\InvalidValueObjectException
	 */
	public function checkValueObjectRequirementsThrowsExceptionIfConstructorIsMissing() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('getClassMethodNames'));
		$reflectionService->expects($this->once())->method('getClassMethodNames')->with('TYPO3\FLOW3\Tests\Reflection\Fixture\Model\ValueObject')->will($this->returnValue(array('getFoo')));
		$reflectionService->_call('checkValueObjectRequirements', 'TYPO3\FLOW3\Tests\Reflection\Fixture\Model\ValueObject');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Reflection\Exception\InvalidValueObjectException
	 */
	public function checkValueObjectRequirementsThrowsExceptionIfSetterExists() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('getClassMethodNames'));
		$reflectionService->expects($this->once())->method('getClassMethodNames')->with('TYPO3\FLOW3\Tests\Reflection\Fixture\Model\ValueObject')->will($this->returnValue(array('__construct', 'getFoo', 'setBar')));
		$reflectionService->_call('checkValueObjectRequirements', 'TYPO3\FLOW3\Tests\Reflection\Fixture\Model\ValueObject');
	}

	/**
	 * @test
	 */
	public function checkValueObjectRequirementsRequiresConstructorAndNoSetters() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('getClassMethodNames'));
		$reflectionService->expects($this->once())->method('getClassMethodNames')->with('TYPO3\FLOW3\Tests\Reflection\Fixture\Model\ValueObject')->will($this->returnValue(array('__construct', 'getFoo')));
		$reflectionService->_call('checkValueObjectRequirements', 'TYPO3\FLOW3\Tests\Reflection\Fixture\Model\ValueObject');
	}

	/**
	 * @test
	 */
	public function classSchemaContainsNameOfItsRelatedClass() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('dummy'));
		$reflectionService->_call('buildClassSchemata', array('TYPO3\FLOW3\Tests\Reflection\Fixture\Model\Entity'));

		$builtClassSchemata = $reflectionService->getClassSchemata();
		$builtClassSchema = array_pop($builtClassSchemata);
		$this->assertEquals($builtClassSchema->getClassName(), 'TYPO3\FLOW3\Tests\Reflection\Fixture\Model\Entity');
	}

	/**
	 * @test
	 */
	public function identityPropertiesAreDetectedFromAnnotation() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('dummy'));
		$reflectionService->_call('buildClassSchemata', array('TYPO3\FLOW3\Tests\Reflection\Fixture\Model\Entity'));

		$builtClassSchemata = $reflectionService->getClassSchemata();
		$builtClassSchema = array_pop($builtClassSchemata);

		$expectedIdentityProperties = array(
			'someString' => 'string',
			'someDate' => 'DateTime'
		);

		$this->assertSame($builtClassSchema->getIdentityProperties(), $expectedIdentityProperties);
	}

	/**
	 * @test
	 */
	public function aggregateRootIsTrueWhenRepositoryClassNameIsNotNull() {
		$classSchema = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ClassSchema', array('dummy'), array('FooBar'));
		$classSchema->_set('repositoryClassName', 'FooBarRepository');

		$this->assertTrue($classSchema->isAggregateRoot());
	}

	/**
	 * @test
	 */
	public function repositoryClassNameIsDetectedForEntities() {
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('isClassReflected', 'loadFromCache'));
		$reflectionService->injectClassLoader(new \TYPO3\FLOW3\Core\ClassLoader());
		$reflectionService->expects($this->atLeastOnce())->method('isClassReflected')->with('TYPO3\FLOW3\Tests\Reflection\Fixture\Repository\EntityRepository')->will($this->returnValue(TRUE));
		$reflectionService->initializeObject();
		$reflectionService->_call('buildClassSchemata', array('TYPO3\FLOW3\Tests\Reflection\Fixture\Model\Entity'));

		$builtClassSchemata = $reflectionService->getClassSchemata();
		$builtClassSchema = array_pop($builtClassSchemata);

		$this->assertEquals('TYPO3\FLOW3\Tests\Reflection\Fixture\Repository\EntityRepository', $builtClassSchema->getRepositoryClassName());
	}

	/**
	 * Does detection work for models where a repository declares itself responsible?
	 * @test
	 */
	public function aggregateRootIsDetectedForEntitiesWithNonStandardRepository() {
		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('isClassReflected', 'getAllImplementationClassNamesForInterface', 'loadFromCache'));
		$reflectionService->injectClassLoader(new \TYPO3\FLOW3\Core\ClassLoader());
		$reflectionService->expects($this->atLeastOnce())->method('isClassReflected')->with('TYPO3\FLOW3\Tests\Reflection\Fixture\Repository\EntityRepository')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->once())->method('getAllImplementationClassNamesForInterface')->with('TYPO3\FLOW3\Persistence\RepositoryInterface')->will($this->returnValue(array('TYPO3\FLOW3\Tests\Reflection\Fixture\Repository\NonstandardEntityRepository')));
		$reflectionService->initializeObject();
		$reflectionService->_call('buildClassSchemata', array('TYPO3\FLOW3\Tests\Reflection\Fixture\Model\Entity'));

		$builtClassSchemata = $reflectionService->getClassSchemata();
		$builtClassSchema = array_pop($builtClassSchemata);

		$this->assertEquals('TYPO3\FLOW3\Tests\Reflection\Fixture\Repository\NonstandardEntityRepository', $builtClassSchema->getRepositoryClassName());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Reflection\Exception
	 */
	public function entitiesMustBePrototype() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('detectAvailableClassNames', 'reflectClass', 'isClassTaggedWith', 'getClassTagValues', 'buildClassSchemata'));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue(array('Quux')));
		$reflectionService->expects($this->any())->method('isClassTaggedWith')->will($this->returnValue(TRUE));
		$reflectionService->expects($this->any())->method('getClassTagValues')->will($this->returnValue(array()));

		$reflectionService->_call('reflectEmergedClasses');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Reflection\Exception
	 */
	public function valueObjectsMustBePrototype() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('detectAvailableClassNames', 'reflectClass', 'isClassTaggedWith', 'getClassTagValues', 'buildClassSchemata'));
		$reflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue(array('Quux')));
		$reflectionService->expects($this->any())->method('isClassTaggedWith')->will($this->onConsecutiveCalls(FALSE, TRUE));
		$reflectionService->expects($this->any())->method('getClassTagValues')->will($this->returnValue(array()));

		$reflectionService->_call('reflectEmergedClasses',array('Quux'));
	}

	/**
	 * @test
	 */
	public function detectAvailableClassNamesCollectsClassNamesFromActivePackages() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$packages = array(
			 'Foo' => $this->getMock('TYPO3\FLOW3\Package\Package', array(), array(), '', FALSE),
			 'Bar' => $this->getMock('TYPO3\FLOW3\Package\Package', array(), array(), '', FALSE)
		);

		$fooClassFiles = array(
			 'FooClass' => '/tmp/foo/FooClass',
			 'FooException' => '/tmp/foo/FooException'
		);

		$barClassFiles = array(
			 'Bar1Class' => '/tmp/bar/Bar1Class',
			 'Bar2Class' => '/tmp/bar/Bar1Class'
		);

		$packages['Foo']->expects($this->once())->method('getClassFiles')->will($this->returnValue($fooClassFiles));
		$packages['Bar']->expects($this->once())->method('getClassFiles')->will($this->returnValue($barClassFiles));

		$mockPackageManager = $this->getMock('TYPO3\FLOW3\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->once())->method('getActivePackages')->will($this->returnValue($packages));

		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('dummy'));
		$reflectionService->injectSettings(array('object' => array('registerFunctionalTestClasses' => FALSE)));
		$reflectionService->injectPackageManager($mockPackageManager);

		$expectedClassNames = array('FooClass', 'Bar1Class', 'Bar2Class');
		$this->assertEquals($expectedClassNames, $reflectionService->_call('detectAvailableClassNames'));
	}

	/**
	 * @test
	 */
	public function detectAvailableClassNamesAlsoRegistersFunctionalTestClassesIfObjectManagerIsConfiguredToDoSo() {
		$this->markTestSkipped('Refactor unit tests for Reflection Service!');

		$packages = array(
			 'Foo' => $this->getMock('TYPO3\FLOW3\Package\Package', array(), array(), '', FALSE),
		);

		$fooClassFiles = array(
			 'FooClass' => '/tmp/foo/FooClass',
			 'FooException' => '/tmp/foo/FooException'
		);

		$fooTestsClassFiles = array(
			 'FooTestClass' => '/tmp/foo/tests/FooTestClass',
			 'FooTestException' => '/tmp/foo/tests/FooTestException',
		);

		$packages['Foo']->expects($this->once())->method('getClassFiles')->will($this->returnValue($fooClassFiles));
		$packages['Foo']->expects($this->once())->method('getFunctionalTestsClassFiles')->will($this->returnValue($fooTestsClassFiles));

		$mockPackageManager = $this->getMock('TYPO3\FLOW3\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->once())->method('getActivePackages')->will($this->returnValue($packages));

		$reflectionService = $this->getAccessibleMock('TYPO3\FLOW3\Reflection\ReflectionService', array('dummy'));
		$reflectionService->injectSettings(array('object' => array('registerFunctionalTestClasses' => TRUE)));
		$reflectionService->injectPackageManager($mockPackageManager);

		$expectedClassNames = array('FooClass', 'FooTestClass');
		$this->assertEquals($expectedClassNames, $reflectionService->_call('detectAvailableClassNames'));
	}

}

?>