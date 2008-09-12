<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::AOP;

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
 * Testcase for the AOP Adviced Method Interceptor Builder
 *
 * @package		FLOW3
 * @version 	$Id:F3::FLOW3::AOP::FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class AdvicedMethodInterceptorBuilderTest extends F3::Testing::BaseTestCase {

	/**
	 * checkIfParameterOfTypeArrayIsReflectedCorrectly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function checkIfParameterOfTypeArrayIsReflectedCorrectly() {
		$targetClass = new F3::FLOW3::Reflection::ReflectionClass('F3::TestPackage::BasicClass');
		$targetMethod = $targetClass->getMethod('methodWhichExpectsAnArrayArgument');

		$builder = new F3::FLOW3::AOP::AdvicedMethodInterceptorBuilder();
		$parameterCode = $builder->buildMethodParametersCode($targetMethod, TRUE);

		$this->assertEquals('array $someArray', $parameterCode, 'The parameters code for an array-type argument is not as expected.');
	}
}
?>