<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Reflection;

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
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the Reflection Class Factory 
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::AOP::Framework.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ClassFactoryTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reflectReturnsClassReflectionsLoadedWithLoadReflections() {
		$reflectionFactory = new F3::FLOW3::Reflection::ReflectionClassFactory();
		$expectedReflection = new F3::FLOW3::Reflection::ReflectionClass(__CLASS__);

		$reflectionFactory->setReflections(array($expectedReflection));
		$actualReflection = $reflectionFactory->reflect(__CLASS__);
		$this->assertSame($expectedReflection, $actualReflection);		
	}
	
	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getReflectionsReturnsTheSameReflectionsWhichWereLoaded() {
		$reflectionFactory = new F3::FLOW3::Reflection::ReflectionClassFactory();
		$reflection = new F3::FLOW3::Reflection::ReflectionClass(__CLASS__);
		$reflectionFactory->setReflections(array($reflection));
		
		$this->assertSame($reflection, array_pop($reflectionFactory->getReflections()));
	}
	
	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reflectReturnsFreshClassReflectionIfItDoesntExistInTheCache() {
		$reflectionFactory = new F3::FLOW3::Reflection::ReflectionClassFactory();
		$expectedReflection = new F3::FLOW3::Reflection::ReflectionClass(__CLASS__);
		$actualReflection = $reflectionFactory->reflect(__CLASS__);
		$this->assertEquals($expectedReflection, $actualReflection);
	}
	
	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reflectReturnsSameClassReflectionIfCalledMultipleTimes() {
		$reflectionFactory = new F3::FLOW3::Reflection::ReflectionClassFactory();
		$reflection1 = $reflectionFactory->reflect(__CLASS__);
		$reflection2 = $reflectionFactory->reflect(__CLASS__);
		$reflection3 = $reflectionFactory->reflect(__CLASS__);
		$expectedReflection = new F3::FLOW3::Reflection::ReflectionClass(__CLASS__);
				
		$this->assertEquals($expectedReflection, $reflection1);
		$this->assertSame($reflection1, $reflection2);
		$this->assertSame($reflection2, $reflection3);		
	}
}
?>