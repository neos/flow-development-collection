<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id$
 */

require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_DummyInterface1.php');
require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_DummyInterface2.php');
require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_DummyInterface3.php');
require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_ImplementationOfDummyInterface1.php');
require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_Implementation1OfDummyInterface3.php');
require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_Implementation2OfDummyInterface3.php');
require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_TaggedClass1.php');
require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_TaggedClass2.php');
require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_TaggedClass3.php');
require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_DummyClass.php');
require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_DummyAbstractClass.php');
require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_DummyFinalClass.php');
require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_DummyClassWithMethods.php');
require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_DummyClassWithProperties.php');

/**
 * Testcase for the Reflection Service
 *
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Reflection_ServiceTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDefaultImplementationClassNameForInterfaceReturnsClassNameOfOnlyClassImplementingTheInterface() {
		$classNames = array(
			'F3_FLOW3_Tests_Reflection_Fixture_DummyInterface1',
			'F3_FLOW3_Tests_Reflection_Fixture_DummyInterface2',
			'F3_FLOW3_Tests_Reflection_Fixture_ImplementationOfDummyInterface1'
		);
		$reflectionService = new F3_FLOW3_Reflection_Service();
		$reflectionService->initialize($classNames);
		$className = $reflectionService->getDefaultImplementationClassNameForInterface('F3_FLOW3_Tests_Reflection_Fixture_DummyInterface1');

		$this->assertEquals('F3_FLOW3_Tests_Reflection_Fixture_ImplementationOfDummyInterface1', $className);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDefaultImplementationClassNameForInterfaceReturnsFalseIfNoClassImplementsTheInterface() {
		$classNames = array(
			'F3_FLOW3_Tests_Reflection_Fixture_DummyInterface1',
			'F3_FLOW3_Tests_Reflection_Fixture_DummyInterface2',
			'F3_FLOW3_Tests_Reflection_Fixture_ImplementationOfDummyInterface1'
		);
		$reflectionService = new F3_FLOW3_Reflection_Service();
		$reflectionService->initialize($classNames);
		$className = $reflectionService->getDefaultImplementationClassNameForInterface('F3_FLOW3_Tests_Reflection_Fixture_DummyInterface2');

		$this->assertFalse($className);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAllImplementationClassNamesForInterfaceReturnsAllNamesOfClassesImplementingTheInterface() {
		$availableClassNames = array(
			'F3_FLOW3_Tests_Reflection_Fixture_DummyInterface3',
			'F3_FLOW3_Tests_Reflection_Fixture_ImplementationOfDummyInterface1',
			'F3_FLOW3_Tests_Reflection_Fixture_Implementation1OfDummyInterface3',
			'F3_FLOW3_Tests_Reflection_Fixture_Implementation2OfDummyInterface3'
		);

		$expectedClassNames = array(
			'F3_FLOW3_Tests_Reflection_Fixture_Implementation1OfDummyInterface3',
			'F3_FLOW3_Tests_Reflection_Fixture_Implementation2OfDummyInterface3'
		);

		$reflectionService = new F3_FLOW3_Reflection_Service();
		$reflectionService->initialize($availableClassNames);
		$detectedClassNames = $reflectionService->getAllImplementationClassNamesForInterface('F3_FLOW3_Tests_Reflection_Fixture_DummyInterface3');

		$this->assertEquals($expectedClassNames, $detectedClassNames);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAllImplementationClassNamesForInterfaceReturnsEmptyArrayIfNoClassImplementsTheInterface() {
		$availableClassNames = array(
			'F3_FLOW3_Tests_Reflection_Fixture_DummyInterface3',
			'F3_FLOW3_Tests_Reflection_Fixture_DummyInterface2',
			'F3_FLOW3_Tests_Reflection_Fixture_ImplementationOfDummyInterface1',
			'F3_FLOW3_Tests_Reflection_Fixture_Implementation1OfDummyInterface3',
			'F3_FLOW3_Tests_Reflection_Fixture_Implementation2OfDummyInterface3'
		);
		$reflectionService = new F3_FLOW3_Reflection_Service();
		$reflectionService->initialize($availableClassNames);

		$detectedClassNames = $reflectionService->getAllImplementationClassNamesForInterface('F3_FLOW3_Tests_Reflection_Fixture_DummyInterface2');
		$this->assertEquals(array(), $detectedClassNames);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassNamesByTagReturnsArrayOfClassesTaggedBySpecifiedTag() {
		$availableClassNames = array(
			'F3_FLOW3_Tests_Reflection_Fixture_TaggedClass1',
			'F3_FLOW3_Tests_Reflection_Fixture_TaggedClass2',
		);
		$reflectionService = new F3_FLOW3_Reflection_Service();
		$reflectionService->initialize($availableClassNames);

		$detectedClassNames = $reflectionService->getClassNamesByTag('sometag1');
		$this->assertEquals(array('F3_FLOW3_Tests_Reflection_Fixture_TaggedClass1'), $detectedClassNames);

		$detectedClassNames = $reflectionService->getClassNamesByTag('sometag2');
		$this->assertEquals(array('F3_FLOW3_Tests_Reflection_Fixture_TaggedClass2'), $detectedClassNames);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassTagsValuesReturnsArrayOfTagsAndValuesOfAClass() {
		$availableClassNames = array(
			'F3_FLOW3_Tests_Reflection_Fixture_TaggedClass3',
		);
		$reflectionService = new F3_FLOW3_Reflection_Service();
		$reflectionService->initialize($availableClassNames);

		$expectedTags = array('firsttag' => array(), 'secondtag' => array('1', '2'), 'thirdtag' => array('one, two', 'three, four'));
		$detectedTags = $reflectionService->getClassTagsValues('F3_FLOW3_Tests_Reflection_Fixture_TaggedClass3');
		ksort($detectedTags);
		$this->assertEquals($expectedTags, $detectedTags);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassTagValuesReturnsArrayOfValuesOfASpecificClassTag() {
		$availableClassNames = array(
			'F3_FLOW3_Tests_Reflection_Fixture_TaggedClass3',
		);
		$reflectionService = new F3_FLOW3_Reflection_Service();
		$reflectionService->initialize($availableClassNames);

		$expectedValues = array('one, two', 'three, four');
		$detectedValues = $reflectionService->getClassTagValues('F3_FLOW3_Tests_Reflection_Fixture_TaggedClass3', 'thirdtag');
		ksort($detectedValues);
		$this->assertEquals($expectedValues, $detectedValues);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isClassTaggedWithReturnsTrueIfClassIsTaggedWithSpecifiedTag() {
		$availableClassNames = array(
			'F3_FLOW3_Tests_Reflection_Fixture_TaggedClass1',
			'F3_FLOW3_Tests_Reflection_Fixture_TaggedClass2',
		);
		$reflectionService = new F3_FLOW3_Reflection_Service();
		$reflectionService->initialize($availableClassNames);

		$this->assertTrue($reflectionService->isClassTaggedWith('F3_FLOW3_Tests_Reflection_Fixture_TaggedClass1', 'sometag1'));
		$this->assertFalse($reflectionService->isClassTaggedWith('F3_FLOW3_Tests_Reflection_Fixture_TaggedClass1', 'sometag2'));
		$this->assertTrue($reflectionService->isClassTaggedWith('F3_FLOW3_Tests_Reflection_Fixture_TaggedClass2', 'sometag2'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isClassAbstractTellsIfAClassIsAbstract() {
		$availableClassNames = array(
			'F3_FLOW3_Tests_Reflection_Fixture_DummyClass',
			'F3_FLOW3_Tests_Reflection_Fixture_DummyAbstractClass',
		);
		$reflectionService = new F3_FLOW3_Reflection_Service();
		$reflectionService->initialize($availableClassNames);

		$this->assertTrue($reflectionService->isClassAbstract('F3_FLOW3_Tests_Reflection_Fixture_DummyAbstractClass'));
		$this->assertFalse($reflectionService->isClassAbstract('F3_FLOW3_Tests_Reflection_Fixture_DummyClass'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isClassFinalTellsIfAClassIsFinal() {
		$availableClassNames = array(
			'F3_FLOW3_Tests_Reflection_Fixture_DummyClass',
			'F3_FLOW3_Tests_Reflection_Fixture_DummyFinalClass',
		);
		$reflectionService = new F3_FLOW3_Reflection_Service();
		$reflectionService->initialize($availableClassNames);

		$this->assertTrue($reflectionService->isClassFinal('F3_FLOW3_Tests_Reflection_Fixture_DummyFinalClass'));
		$this->assertFalse($reflectionService->isClassFinal('F3_FLOW3_Tests_Reflection_Fixture_DummyClass'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassMethodNamesReturnsNamesOfAllMethodsOfAClass() {
		$availableClassNames = array(
			'F3_FLOW3_Tests_Reflection_Fixture_DummyClass',
			'F3_FLOW3_Tests_Reflection_Fixture_DummyClassWithMethods',
		);
		$reflectionService = new F3_FLOW3_Reflection_Service();
		$reflectionService->initialize($availableClassNames);

		$expectedMethodNames = array('firstMethod', 'secondMethod');
		$detectedMethodNames = $reflectionService->getClassMethodNames('F3_FLOW3_Tests_Reflection_Fixture_DummyClassWithMethods');
		$this->assertEquals($expectedMethodNames, $detectedMethodNames);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassPropertyNamesReturnsNamesOfAllPropertiesOfAClass() {
		$availableClassNames = array(
			'F3_FLOW3_Tests_Reflection_Fixture_DummyClass',
			'F3_FLOW3_Tests_Reflection_Fixture_DummyClassWithProperties',
		);
		$reflectionService = new F3_FLOW3_Reflection_Service();
		$reflectionService->initialize($availableClassNames);

		$expectedPropertyNames = array('firstProperty', 'secondProperty');
		$detectedPropertyNames = $reflectionService->getClassPropertyNames('F3_FLOW3_Tests_Reflection_Fixture_DummyClassWithProperties');
		$this->assertEquals($expectedPropertyNames, $detectedPropertyNames);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethodTagsValuesReturnsArrayOfTagsAndValuesOfAMethod() {
		$availableClassNames = array(
			'F3_FLOW3_Tests_Reflection_Fixture_DummyClassWithMethods',
		);
		$reflectionService = new F3_FLOW3_Reflection_Service();
		$reflectionService->initialize($availableClassNames);

		$expectedTags = array('firsttag' => array(), 'return' => array('void'), 'secondtag' => array('a', 'b'));
		$detectedTags = $reflectionService->getMethodTagsValues('F3_FLOW3_Tests_Reflection_Fixture_DummyClassWithMethods', 'firstMethod');
		ksort($detectedTags);
		$this->assertEquals($expectedTags, $detectedTags);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPropertyTagsValuesReturnsArrayOfTagsAndValuesOfAProperty() {
		$availableClassNames = array(
			'F3_FLOW3_Tests_Reflection_Fixture_DummyClassWithProperties',
		);
		$reflectionService = new F3_FLOW3_Reflection_Service();
		$reflectionService->initialize($availableClassNames);

		$expectedTags = array('firsttag' => array(), 'secondtag' => array('x', 'y'));
		$detectedTags = $reflectionService->getPropertyTagsValues('F3_FLOW3_Tests_Reflection_Fixture_DummyClassWithProperties', 'firstProperty');
		ksort($detectedTags);
		$this->assertEquals($expectedTags, $detectedTags);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theInitializedFlagIsSetToTrueAfterCallingImportOrInitialize() {
		$reflectionService = new F3_FLOW3_Reflection_Service();
		$this->assertFalse($reflectionService->isInitialized());
		$reflectionService->initialize(array(__CLASS__));
		$this->assertTrue($reflectionService->isInitialized());

		$reflectionService = new F3_FLOW3_Reflection_Service();
		$this->assertFalse($reflectionService->isInitialized());
		$reflectionService->import(array());
		$this->assertTrue($reflectionService->isInitialized());
	}
}

?>