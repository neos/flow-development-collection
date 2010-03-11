<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Reflection;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
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

/**
 * Testcase for the Reflection Service
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ReflectionServiceTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theInitializedFlagIsSetToTrueAfterCallingInitialize() {
		$reflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'forgetChangedClasses', 'reflectEmergedClasses'), array(), '', FALSE);
		$this->assertFalse($reflectionService->isInitialized());
		$reflectionService->initialize(array(__CLASS__));
		$this->assertTrue($reflectionService->isInitialized());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeLoadsDataFromTheCacheAndReflectsNewClasses() {
		$reflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'forgetChangedClasses', 'reflectEmergedClasses'), array(), '', FALSE);
		$reflectionService->expects($this->once())->method('loadFromCache');
		$reflectionService->expects($this->never())->method('forgetChangedClasses');
		$reflectionService->expects($this->once())->method('reflectEmergedClasses')->with(array(__CLASS__));
		$reflectionService->initialize(array(__CLASS__));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeForgetsChangedClassesIfSettingsSaySo() {
		$reflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'forgetChangedClasses', 'reflectEmergedClasses'), array(), '', FALSE);
		$reflectionService->injectSettings(array('monitor' => array('detectClassChanges' => TRUE)));
		$reflectionService->expects($this->once())->method('forgetChangedClasses');
		$reflectionService->initialize(array(__CLASS__));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function shutdownObjectSavesDataToCacheIfTheReflectedClassesAreNotEqualToTheCachedOnes() {
		$reflectionService = $this->getAccessibleMock('F3\FLOW3\Reflection\ReflectionService', array('saveToCache'), array(), '', FALSE);
		$reflectionService->_set('reflectedClassNames', array('Foo' => 12345, 'Bar' => 12345));
		$reflectionService->_set('cachedClassNames', array('Foo' => 12345, 'Bar' => 23456));
		$reflectionService->expects($this->once())->method('saveToCache');
		$reflectionService->shutdownObject();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isClassReflectedTellsIfTheReflectionServiceKnowsTheSpecfiedClass() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyClass',
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($availableClassNames);

		$this->assertTrue($reflectionService->isClassReflected('F3\FLOW3\Tests\Reflection\Fixture\DummyClass'));
		$this->assertFalse($reflectionService->isClassReflected('F3\Virtual\UnknownClass'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDefaultImplementationClassNameForInterfaceReturnsClassNameOfOnlyClassImplementingTheInterface() {
		$classNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyInterface1',
			'F3\FLOW3\Tests\Reflection\Fixture\DummyInterface2',
			'F3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1'
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($classNames);
		$className = $reflectionService->getDefaultImplementationClassNameForInterface('F3\FLOW3\Tests\Reflection\Fixture\DummyInterface1');

		$this->assertEquals('F3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1', $className);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDefaultImplementationClassNameForInterfaceReturnsFalseIfNoClassImplementsTheInterface() {
		$classNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyInterface1',
			'F3\FLOW3\Tests\Reflection\Fixture\DummyInterface2',
			'F3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1'
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($classNames);
		$className = $reflectionService->getDefaultImplementationClassNameForInterface('F3\FLOW3\Tests\Reflection\Fixture\DummyInterface2');

		$this->assertFalse($className);
	}

	/**
	 * If two classes implement an interface, the Reflection Service checks if one of them is
	 * a proxy (implements the Proxy marker interface). If that is the case, it's sure that the
	 * other class is the original (target) class. In case these conditions are met, the name
	 * of the proxy class is returned.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @see http://typo3.org/go/issue/3027
	 */
	public function getDefaultImplementationClassNameForInterfaceReturnsClassNameOfTheProxyIfTwoClassesWereFound() {
		$classNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyInterface1',
			'F3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1',
			'F3\FLOW3\Tests\Reflection\Fixture\ProxyOfImplementationOfDummyInterface1',
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($classNames);
		$className = $reflectionService->getDefaultImplementationClassNameForInterface('F3\FLOW3\Tests\Reflection\Fixture\DummyInterface1');
		$this->assertEquals('F3\FLOW3\Tests\Reflection\Fixture\ProxyOfImplementationOfDummyInterface1', $className, 'Proxy registered second.');

		$classNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyInterface1',
			'F3\FLOW3\Tests\Reflection\Fixture\ProxyOfImplementationOfDummyInterface1',
			'F3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1',
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($classNames);
		$className = $reflectionService->getDefaultImplementationClassNameForInterface('F3\FLOW3\Tests\Reflection\Fixture\DummyInterface1');

		$this->assertEquals('F3\FLOW3\Tests\Reflection\Fixture\ProxyOfImplementationOfDummyInterface1', $className, 'Proxy registered first.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAllImplementationClassNamesForInterfaceReturnsAllNamesOfClassesImplementingTheInterface() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyInterface3',
			'F3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1',
			'F3\FLOW3\Tests\Reflection\Fixture\Implementation1OfDummyInterface3',
			'F3\FLOW3\Tests\Reflection\Fixture\Implementation2OfDummyInterface3'
		);

		$expectedClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\Implementation1OfDummyInterface3',
			'F3\FLOW3\Tests\Reflection\Fixture\Implementation2OfDummyInterface3'
		);

		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($availableClassNames);
		$detectedClassNames = $reflectionService->getAllImplementationClassNamesForInterface('F3\FLOW3\Tests\Reflection\Fixture\DummyInterface3');

		$this->assertEquals($expectedClassNames, $detectedClassNames);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAllImplementationClassNamesForInterfaceReturnsEmptyArrayIfNoClassImplementsTheInterface() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyInterface3',
			'F3\FLOW3\Tests\Reflection\Fixture\DummyInterface2',
			'F3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1',
			'F3\FLOW3\Tests\Reflection\Fixture\Implementation1OfDummyInterface3',
			'F3\FLOW3\Tests\Reflection\Fixture\Implementation2OfDummyInterface3'
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($availableClassNames);

		$detectedClassNames = $reflectionService->getAllImplementationClassNamesForInterface('F3\FLOW3\Tests\Reflection\Fixture\DummyInterface2');
		$this->assertEquals(array(), $detectedClassNames);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAllSubClassNamesForClassReturnsEmptyArrayIfNoClassInheritsTheClass() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyClass',
			'F3\FLOW3\Tests\Reflection\Fixture\ParentClass1'
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($availableClassNames);

		$detectedClassNames = $reflectionService->getAllSubClassNamesForClass('F3\FLOW3\Tests\Reflection\Fixture\DummyClass');
		$this->assertEquals(array(), $detectedClassNames);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAllSubClassNamesForClassReturnsArrayOfSubClasses() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyClass',
			'F3\FLOW3\Tests\Reflection\Fixture\ParentClass1',
			'F3\FLOW3\Tests\Reflection\Fixture\SubClassOfParentClass1',
			'F3\FLOW3\Tests\Reflection\Fixture\SubClassOfSubClassOfParentClass1',
		);

		$expectedClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\SubClassOfParentClass1',
			'F3\FLOW3\Tests\Reflection\Fixture\SubClassOfSubClassOfParentClass1',
		);

		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($availableClassNames);

		$detectedClassNames = $reflectionService->getAllSubClassNamesForClass('F3\FLOW3\Tests\Reflection\Fixture\ParentClass1');
		$this->assertEquals($expectedClassNames, $detectedClassNames);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassNamesByTagReturnsArrayOfClassesTaggedBySpecifiedTag() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\TaggedClass1',
			'F3\FLOW3\Tests\Reflection\Fixture\TaggedClass2',
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($availableClassNames);

		$detectedClassNames = $reflectionService->getClassNamesByTag('sometag1');
		$this->assertEquals(array('F3\FLOW3\Tests\Reflection\Fixture\TaggedClass1'), $detectedClassNames);

		$detectedClassNames = $reflectionService->getClassNamesByTag('sometag2');
		$this->assertEquals(array('F3\FLOW3\Tests\Reflection\Fixture\TaggedClass2'), $detectedClassNames);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassTagsValuesReturnsArrayOfTagsAndValuesOfAClass() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\TaggedClass3',
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($availableClassNames);

		$expectedTags = array('firsttag' => array(), 'secondtag' => array('1', '2'), 'thirdtag' => array('one, two', 'three, four'));
		$detectedTags = $reflectionService->getClassTagsValues('F3\FLOW3\Tests\Reflection\Fixture\TaggedClass3');
		ksort($detectedTags);
		$this->assertEquals($expectedTags, $detectedTags);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassTagValuesReturnsArrayOfValuesOfASpecificClassTag() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\TaggedClass3',
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($availableClassNames);

		$expectedValues = array('one, two', 'three, four');
		$detectedValues = $reflectionService->getClassTagValues('F3\FLOW3\Tests\Reflection\Fixture\TaggedClass3', 'thirdtag');
		ksort($detectedValues);
		$this->assertEquals($expectedValues, $detectedValues);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isClassTaggedWithReturnsTrueIfClassIsTaggedWithSpecifiedTag() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\TaggedClass1',
			'F3\FLOW3\Tests\Reflection\Fixture\TaggedClass2',
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($availableClassNames);

		$this->assertTrue($reflectionService->isClassTaggedWith('F3\FLOW3\Tests\Reflection\Fixture\TaggedClass1', 'sometag1'));
		$this->assertFalse($reflectionService->isClassTaggedWith('F3\FLOW3\Tests\Reflection\Fixture\TaggedClass1', 'sometag2'));
		$this->assertTrue($reflectionService->isClassTaggedWith('F3\FLOW3\Tests\Reflection\Fixture\TaggedClass2', 'sometag2'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isClassAbstractTellsIfAClassIsAbstract() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyClass',
			'F3\FLOW3\Tests\Reflection\Fixture\DummyAbstractClass',
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($availableClassNames);

		$this->assertTrue($reflectionService->isClassAbstract('F3\FLOW3\Tests\Reflection\Fixture\DummyAbstractClass'));
		$this->assertFalse($reflectionService->isClassAbstract('F3\FLOW3\Tests\Reflection\Fixture\DummyClass'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isClassFinalTellsIfAClassIsFinal() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyClass',
			'F3\FLOW3\Tests\Reflection\Fixture\DummyFinalClass',
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($availableClassNames);

		$this->assertTrue($reflectionService->isClassFinal('F3\FLOW3\Tests\Reflection\Fixture\DummyFinalClass'));
		$this->assertFalse($reflectionService->isClassFinal('F3\FLOW3\Tests\Reflection\Fixture\DummyClass'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassMethodNamesReturnsNamesOfAllMethodsOfAClass() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyClass',
			'F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithMethods',
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($availableClassNames);

		$expectedMethodNames = array('firstMethod', 'secondMethod');
		$detectedMethodNames = $reflectionService->getClassMethodNames('F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithMethods');
		$this->assertEquals($expectedMethodNames, $detectedMethodNames);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassPropertyNamesReturnsNamesOfAllPropertiesOfAClass() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyClass',
			'F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties',
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($availableClassNames);

		$expectedPropertyNames = array('firstProperty', 'secondProperty');
		$detectedPropertyNames = $reflectionService->getClassPropertyNames('F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties');
		$this->assertEquals($expectedPropertyNames, $detectedPropertyNames);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethodTagsValuesReturnsArrayOfTagsAndValuesOfAMethod() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithMethods',
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($availableClassNames);

		$expectedTags = array('firsttag' => array(), 'return' => array('void'), 'secondtag' => array('a', 'b'), 'param' => array('string $arg1 Argument 1 documentation'));
		$detectedTags = $reflectionService->getMethodTagsValues('F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithMethods', 'firstMethod');
		ksort($detectedTags);
		$this->assertEquals($expectedTags, $detectedTags);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethodParametersReturnsAnArrayOfParameterNamesAndAdditionalInformation() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithMethods',
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($availableClassNames);

		$expectedParameters = array(
			'arg1' => array('position' => 0, 'byReference' => FALSE, 'array' => FALSE, 'optional' => FALSE, 'class' => NULL, 'allowsNull' => TRUE, 'type' => 'string'),
			'arg2' => array('position' => 1, 'byReference' => TRUE, 'array' => FALSE, 'optional' => FALSE, 'class' => NULL, 'allowsNull' => TRUE),
			'arg3' => array('position' => 2, 'byReference' => FALSE, 'array' => FALSE, 'optional' => FALSE, 'class' => 'stdClass', 'allowsNull' => FALSE, 'type' => 'stdClass'),
			'arg4' => array('position' => 3, 'byReference' => FALSE, 'array' => FALSE, 'optional' => TRUE, 'class' => NULL, 'allowsNull' => TRUE, 'defaultValue' => 'default')
		);

		$actualParameters = $reflectionService->getMethodParameters('F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithMethods', 'firstMethod');
		$this->assertEquals($expectedParameters, $actualParameters);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPropertyNamesByTagReturnsArrayOfPropertiesTaggedBySpecifiedTag() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyClass',
			'F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties',
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($availableClassNames);

		$expectedPropertyNames = array('firstProperty');
		$detectedPropertyNames = $reflectionService->getPropertyNamesByTag('F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties', 'firsttag');
		$this->assertEquals($expectedPropertyNames, $detectedPropertyNames);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getPropertyNamesByTagReturnsEmptyArrayIfNoPropertiesTaggedBySpecifiedTagWhereFound() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyClass',
			'F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties',
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($availableClassNames);

		$expectedPropertyNames = array();
		$detectedPropertyNames = $reflectionService->getPropertyNamesByTag('F3\FLOW3\Tests\Reflection\Fixture\DummyClass', 'firsttag');
		$this->assertEquals($expectedPropertyNames, $detectedPropertyNames);

		$detectedPropertyNames = $reflectionService->getPropertyNamesByTag('F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties', 'tagnothere');
		$this->assertEquals($expectedPropertyNames, $detectedPropertyNames);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPropertyTagsValuesReturnsArrayOfTagsAndValuesOfAProperty() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties',
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($availableClassNames);

		$expectedTags = array('firsttag' => array(), 'secondtag' => array('x', 'y'), 'var' => array('mixed'));
		$detectedTags = $reflectionService->getPropertyTagsValues('F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties', 'firstProperty');
		ksort($detectedTags);
		$this->assertEquals($expectedTags, $detectedTags);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPropertyTagValuesReturnsArrayOfValuesOfAPropertysTag() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties',
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($availableClassNames);

		$expectedValues = array('x', 'y');
		$detectedValues = $reflectionService->getPropertyTagValues('F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties', 'firstProperty', 'secondtag');
		ksort($detectedValues);
		$this->assertEquals($expectedValues, $detectedValues);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isPropertyTaggedWithReturnsTrueIfTheSpecifiedClassPropertyIsTaggedWithTheGivenTag() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties',
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($availableClassNames);

		$this->assertTrue($reflectionService->isPropertyTaggedWith('F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties', 'firstProperty', 'firsttag'));
		$this->assertFalse($reflectionService->isPropertyTaggedWith('F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties', 'firstProperty', 'nothing'));
		$this->assertFalse($reflectionService->isPropertyTaggedWith('F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties', 'noProperty', 'firsttag'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isClassImplementationOfReturnsTrueIfClassImplementsSpecifiedInterface() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1'
		);
		$reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$reflectionService->setStatusCache($this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE));
		$reflectionService->setDataCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->initialize($availableClassNames);

		$this->assertTrue($reflectionService->isClassImplementationOf('F3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1', 'F3\FLOW3\Tests\Reflection\Fixture\DummyInterface1'));
		$this->assertFalse($reflectionService->isClassImplementationOf('F3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1', 'F3\FLOW3\Tests\Reflection\Fixture\DummyInterface2'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reflectClassStoresATimeStampWithEachReflectedClass() {
		$className = uniqid('TestClass');
		eval('class ' . $className . ' {}');

		$startTime = time();
		$reflectionService = $this->getAccessibleMock('F3\FLOW3\Reflection\ReflectionService', array('log', 'convertParameterReflectionToArray'), array(), '', FALSE);
		$reflectionService->_call('reflectClass', $className);
		$endTime = time();

		$reflectedClassNames = $reflectionService->_get('reflectedClassNames');
		$this->assertTrue($reflectedClassNames[$className] >= $startTime && $reflectedClassNames[$className] <= $endTime);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function classSchemaOnlyContainsNonTransientProperties() {
		$reflectionService = $this->getAccessibleMock('F3\FLOW3\Reflection\ReflectionService', array('isClassReflected', 'isClassTaggedWith'));
		$reflectionService->_call('buildClassSchemata', array('F3\FLOW3\Tests\Reflection\Fixture\Model\Entity'));

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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function propertyDataIsDetectedFromVarAnnotations() {
		$reflectionService = $this->getAccessibleMock('F3\FLOW3\Reflection\ReflectionService', array('isClassReflected', 'isClassTaggedWith'));
		$reflectionService->_call('buildClassSchemata', array('F3\FLOW3\Tests\Reflection\Fixture\Model\Entity'));

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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function modelTypeEntityIsRecognizedByEntityAnnotation() {
		$reflectionService = $this->getAccessibleMock('F3\FLOW3\Reflection\ReflectionService', array('isClassReflected', 'isClassTaggedWith'));
		$reflectionService->expects($this->at(0))->method('isClassTaggedWith')->with('F3\FLOW3\Tests\Reflection\Fixture\Model\Entity', 'entity')->will($this->returnValue(TRUE));
		$reflectionService->_call('buildClassSchemata', array('F3\FLOW3\Tests\Reflection\Fixture\Model\Entity'));

		$builtClassSchemata = $reflectionService->getClassSchemata();
		$builtClassSchema = array_pop($builtClassSchemata);
		$this->assertEquals($builtClassSchema->getModelType(), \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function modelTypeValueObjectIsRecognizedByValueObjectAnnotation() {
		$reflectionService = $this->getAccessibleMock('F3\FLOW3\Reflection\ReflectionService', array('isClassReflected', 'isClassTaggedWith'));
		$reflectionService->expects($this->at(0))->method('isClassTaggedWith')->with('F3\FLOW3\Tests\Reflection\Fixture\Model\ValueObject', 'entity')->will($this->returnValue(FALSE));
		$reflectionService->expects($this->at(1))->method('isClassTaggedWith')->with('F3\FLOW3\Tests\Reflection\Fixture\Model\ValueObject', 'valueobject')->will($this->returnValue(TRUE));
		$reflectionService->_call('buildClassSchemata', array('F3\FLOW3\Tests\Reflection\Fixture\Model\ValueObject'));

		$builtClassSchemata = $reflectionService->getClassSchemata();
		$builtClassSchema = array_pop($builtClassSchemata);
		$this->assertEquals($builtClassSchema->getModelType(), \F3\FLOW3\Reflection\ClassSchema::MODELTYPE_VALUEOBJECT);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function classSchemaContainsNameOfItsRelatedClass() {
		$reflectionService = $this->getAccessibleMock('F3\FLOW3\Reflection\ReflectionService', array('dummy'));
		$reflectionService->_call('buildClassSchemata', array('F3\FLOW3\Tests\Reflection\Fixture\Model\Entity'));

		$builtClassSchemata = $reflectionService->getClassSchemata();
		$builtClassSchema = array_pop($builtClassSchemata);
		$this->assertEquals($builtClassSchema->getClassName(), 'F3\FLOW3\Tests\Reflection\Fixture\Model\Entity');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function uuidPropertyNameIsDetectedFromAnnotation() {
		$reflectionService = $this->getAccessibleMock('F3\FLOW3\Reflection\ReflectionService', array('dummy'));
		$reflectionService->_call('buildClassSchemata', array('F3\FLOW3\Tests\Reflection\Fixture\Model\Entity'));

		$builtClassSchemata = $reflectionService->getClassSchemata();
		$builtClassSchema = array_pop($builtClassSchemata);
		$this->assertEquals($builtClassSchema->getUUIDPropertyName(), 'someIdentifier');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function uuidPropertyNameIsSetAsRegularPropertyAsWell() {
		$reflectionService = $this->getAccessibleMock('F3\FLOW3\Reflection\ReflectionService', array('dummy'));
		$reflectionService->_call('buildClassSchemata', array('F3\FLOW3\Tests\Reflection\Fixture\Model\Entity'));

		$builtClassSchemata = $reflectionService->getClassSchemata();
		$builtClassSchema = array_pop($builtClassSchemata);
		$this->assertTrue(array_key_exists('someIdentifier', $builtClassSchema->getProperties()));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function identityPropertiesAreDetectedFromAnnotation() {
		$reflectionService = $this->getAccessibleMock('F3\FLOW3\Reflection\ReflectionService', array('dummy'));
		$reflectionService->_call('buildClassSchemata', array('F3\FLOW3\Tests\Reflection\Fixture\Model\Entity'));

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
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function aggregateRootIsDetectedForEntities() {
		$reflectionService = $this->getAccessibleMock('F3\FLOW3\Reflection\ReflectionService', array('isClassReflected', 'isClassTaggedWith'));
		$reflectionService->expects($this->at(0))->method('isClassTaggedWith')->will($this->returnValue(TRUE));
		$reflectionService->expects($this->once())->method('isClassReflected')->with('F3\FLOW3\Tests\Reflection\Fixture\Repository\EntityRepository')->will($this->returnValue(TRUE));
		$reflectionService->_call('buildClassSchemata', array('F3\FLOW3\Tests\Reflection\Fixture\Model\Entity'));

		$builtClassSchemata = $reflectionService->getClassSchemata();
		$builtClassSchema = array_pop($builtClassSchemata);

		$this->assertTrue($builtClassSchema->isAggregateRoot());
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Reflection\Exception
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function entitiesMustBePrototype() {
		$reflectionService = $this->getAccessibleMock('F3\FLOW3\Reflection\ReflectionService', array('reflectClass', 'isClassTaggedWith', 'getClassTagValues', 'buildClassSchemata'));
		$reflectionService->expects($this->any())->method('isClassTaggedWith')->will($this->returnValue(TRUE));
		$reflectionService->expects($this->any())->method('getClassTagValues')->will($this->returnValue(array()));

		$reflectionService->_call('reflectEmergedClasses',array('Quux'));
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Reflection\Exception
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function valueObjectsMustBePrototype() {
		$reflectionService = $this->getAccessibleMock('F3\FLOW3\Reflection\ReflectionService', array('reflectClass', 'isClassTaggedWith', 'getClassTagValues', 'buildClassSchemata'));
		$reflectionService->expects($this->any())->method('isClassTaggedWith')->will($this->onConsecutiveCalls(FALSE, TRUE));
		$reflectionService->expects($this->any())->method('getClassTagValues')->will($this->returnValue(array()));

		$reflectionService->_call('reflectEmergedClasses',array('Quux'));
	}
}

?>