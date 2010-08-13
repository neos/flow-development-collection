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
 * Testcase for the AOP Adviced Method Interceptor Builder
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AdvicedConstructorInterceptorBuilderTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildRendersMethodCodeWithArgumentsOfTheOriginalMethodAndAdditionalInterceptionCode() {
		$className = uniqid('TestClass');
		eval('
			class ' . $className . ' {
				public function __construct($arg1, array $arg2, \ArrayObject $arg3, $arg4= "__construct", $arg5 = TRUE) {}
			}
		');

		$interceptedMethods = array(
			'__construct' => array(
				'groupedAdvices' => array('groupedAdvicesDummy'),
				'declaringClassName' => $className
			)
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'saveToCache'), array(), '', FALSE, TRUE);
		$mockReflectionService->initialize(array($className));

		$expectedCode = '
	/**
	 * Interceptor for the constructor __construct().
	 * ' . '
	 */
	public function __construct(PARAMETERSCODE) {
		$this->FLOW3_AOP_Proxy_originalConstructorArguments = array(ARGUMENTSARRAYCODE);
	}

	/**
	 * Initializes the proxy and calls the (parent) constructor with the orginial given arguments.
	 * @return void
	 */
	public function FLOW3_AOP_Proxy_construct() {
		$this->FLOW3_AOP_Proxy_declareMethodsAndAdvices();
		$result = NULL;
		' . '
		if (isset($this->FLOW3_AOP_Proxy_methodIsInAdviceMode[\'__construct\'])) {
			parent::__construct(SAVEDCONSTRUCTORPARAMETERSCODE);
		} else {
			$methodArguments = $this->FLOW3_AOP_Proxy_originalConstructorArguments;
			$this->FLOW3_AOP_Proxy_methodIsInAdviceMode[\'__construct\'] = TRUE;
			ADVICESCODE
			unset ($this->FLOW3_AOP_Proxy_methodIsInAdviceMode[\'__construct\']);
		}

		return $result;
	}
';

		$builder = $this->getMock('F3\FLOW3\AOP\Builder\AdvicedConstructorInterceptorBuilder', array('buildAdvicesCode', 'buildMethodParametersCode', 'buildMethodArgumentsArrayCode', 'buildSavedConstructorParametersCode'), array(), '', FALSE);
		$builder->expects($this->once())->method('buildMethodArgumentsArrayCode')->with($className, '__construct')->will($this->returnValue('ARGUMENTSARRAYCODE'));
		$builder->expects($this->once())->method('buildAdvicesCode')->with(array('groupedAdvicesDummy'), '__construct', 'Bar')->will($this->returnValue('ADVICESCODE'));
		$builder->expects($this->once())->method('buildMethodParametersCode')->with($className, '__construct', TRUE)->will($this->returnValue('PARAMETERSCODE'));
		$builder->expects($this->once())->method('buildSavedConstructorParametersCode')->with($className)->will($this->returnValue('SAVEDCONSTRUCTORPARAMETERSCODE'));

		$builder->injectReflectionService($mockReflectionService);

		$actualCode = $builder->build('__construct', $interceptedMethods, 'Bar');
		$this->assertSame($expectedCode, $actualCode);
	}
}
?>