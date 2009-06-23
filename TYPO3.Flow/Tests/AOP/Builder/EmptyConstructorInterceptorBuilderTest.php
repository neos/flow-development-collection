<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP\Builder;

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
 * @subpackage AOP
 * @version $Id$
 */

/**
 * Testcase for the AOP Empty Constructor Interceptor Builder
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class EmptyConstructorInterceptorBuilderTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildRendersCodeOfAPlaceHolderMethod() {
		$className = uniqid('TestClass');
		eval('
			class ' . $className . ' {
				public function __construct($arg1, $arg2) {}
			}
		');

		$interceptedMethods = array(
			'__construct' => array(
				'groupedAdvices' => array('groupedAdvicesDummy'),
				'declaringClassName' => $className
			)
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array('loadFromCache', 'saveToCache'), array(), '', FALSE, TRUE);
		$mockReflectionService->initialize(array($className));

		$expectedCode = '
	/**
	 * Non-advised constructor interceptor.
	 * ' . '
	 */
	public function __construct(PARAMETERSCODE) {
		$this->originalConstructorArguments = array(PARAMETERSARRAYCODE);
	}

	/**
	 * Initializes the proxy and calls the (parent) constructor with the orginial given arguments.
	 * @return void
	 * @internal
	 */
	public function FLOW3_AOP_Proxy_initializeProxy() {
		$this->FLOW3_AOP_Proxy_declareMethodsAndAdvices();
		parent::__construct(SAVEDCONSTRUCTORPARAMETERSCODE);
	}
';

		$builder = $this->getMock('F3\FLOW3\AOP\Builder\EmptyConstructorInterceptorBuilder', array('buildMethodParametersCode', 'buildMethodArgumentsArrayCode', 'buildSavedConstructorParametersCode'), array(), '', FALSE);
		$builder->injectReflectionService($mockReflectionService);
		$builder->expects($this->once())->method('buildMethodParametersCode')->with($className, '__construct', TRUE)->will($this->returnValue('PARAMETERSCODE'));
		$builder->expects($this->once())->method('buildMethodArgumentsArrayCode')->with($className, '__construct')->will($this->returnValue('PARAMETERSARRAYCODE'));
		$builder->expects($this->once())->method('buildSavedConstructorParametersCode')->with($className)->will($this->returnValue('SAVEDCONSTRUCTORPARAMETERSCODE'));

		$actualCode = $builder->build('__construct', $interceptedMethods, 'Bar');
		$this->assertSame($expectedCode, $actualCode);
	}
}
?>