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

/**
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id$
 */

require_once('Fixture/DummyInterface1.php');
require_once('Fixture/DummyInterface2.php');
require_once('Fixture/DummyInterface3.php');
require_once('Fixture/ImplementationOfDummyInterface1.php');
require_once('Fixture/Implementation1OfDummyInterface3.php');
require_once('Fixture/Implementation2OfDummyInterface3.php');
require_once('Fixture/TaggedClass1.php');
require_once('Fixture/TaggedClass2.php');
require_once('Fixture/TaggedClass3.php');
require_once('Fixture/DummyClass.php');
require_once('Fixture/DummyAbstractClass.php');
require_once('Fixture/DummyFinalClass.php');
require_once('Fixture/DummyClassWithMethods.php');
require_once('Fixture/DummyClassWithProperties.php');

/**
 * Testcase for the Reflection Service
 *
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ServiceTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isClassReflectedTellsIfTheReflectionServiceKnowsTheSpecfiedClass() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyClass',
		);
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
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
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
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
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->initialize($classNames);
		$className = $reflectionService->getDefaultImplementationClassNameForInterface('F3\FLOW3\Tests\Reflection\Fixture\DummyInterface2');

		$this->assertFalse($className);
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

		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
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
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->initialize($availableClassNames);

		$detectedClassNames = $reflectionService->getAllImplementationClassNamesForInterface('F3\FLOW3\Tests\Reflection\Fixture\DummyInterface2');
		$this->assertEquals(array(), $detectedClassNames);
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
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
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
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
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
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
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
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
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
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
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
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
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
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
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
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
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
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->initialize($availableClassNames);

		$expectedTags = array('firsttag' => array(), 'return' => array('void'), 'secondtag' => array('a', 'b'));
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
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->initialize($availableClassNames);

		$expectedParameters = array(
			'arg1' => array('position' => 0, 'byReference' => FALSE, 'array' => FALSE, 'optional' => FALSE, 'class' => NULL, 'allowsNull' => TRUE),
			'arg2' => array('position' => 1, 'byReference' => TRUE, 'array' => FALSE, 'optional' => FALSE, 'class' => NULL, 'allowsNull' => TRUE),
			'arg3' => array('position' => 2, 'byReference' => FALSE, 'array' => FALSE, 'optional' => FALSE, 'class' => 'stdClass', 'allowsNull' => FALSE),
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
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
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
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->initialize($availableClassNames);

		$expectedPropertyNames = array();
		$detectedPropertyNames = $reflectionService->getPropertyNamesByTag('F3\FLOW3\Tests\Reflection\Fixture\DummyClass', 'firsttag');
		$this->assertEquals($expectedPropertyNames, $detectedPropertyNames);

		$detectedPropertyNames = $reflectionService->getPropertyNamesByTag('F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties', 'tagnothere');
		$this->assertEquals($expectedPropertyNames, $detectedPropertyNames);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getPropertyNamesByTagReturnsEmptyArrayIfGivenClassIsUnknown() {
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->initialize(array());

		$expectedPropertyNames = array();
		$detectedPropertyNames = $reflectionService->getPropertyNamesByTag('F3\FLOW3\Tests\Reflection\Fixture\ClassDoesNotExist', 'tagnothere');
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
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
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
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
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
	public function theInitializedFlagIsSetToTrueAfterCallingInitialize() {
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$this->assertFalse($reflectionService->isInitialized());
		$reflectionService->initialize(array(__CLASS__));
		$this->assertTrue($reflectionService->isInitialized());

		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$this->assertFalse($reflectionService->isInitialized());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isPropertyTaggedWithReturnsTrueIfTheSpecifiedClassPropertyIsTaggedWithTheGivenTag() {
		$availableClassNames = array(
			'F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithProperties',
		);
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
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
		$reflectionService = new \F3\FLOW3\Reflection\Service();
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->initialize($availableClassNames);

		$this->assertTrue($reflectionService->isClassImplementationOf('F3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1', 'F3\FLOW3\Tests\Reflection\Fixture\DummyInterface1'));
		$this->assertFalse($reflectionService->isClassImplementationOf('F3\FLOW3\Tests\Reflection\Fixture\ImplementationOfDummyInterface1', 'F3\FLOW3\Tests\Reflection\Fixture\DummyInterface2'));
	}

}

?>