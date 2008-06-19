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
 * @version $Id:$
 */

require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_DummyInterface1.php');
require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_DummyInterface2.php');
require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_DummyInterface3.php');
require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_ImplementationOfDummyInterface1.php');
require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_Implementation1OfDummyInterface3.php');
require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_Implementation2OfDummyInterface3.php');

/**
 * Testcase for the Reflection Service
 *
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id:$
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

}

?>