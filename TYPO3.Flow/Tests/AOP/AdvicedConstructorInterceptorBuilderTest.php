<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP;

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
 * Testcase for the AOP Adviced Method Interceptor Builder
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
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

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array('loadFromCache', 'saveToCache'), array(), '', FALSE, TRUE);
		$mockReflectionService->initialize(array($className));

		$expectedCode = '
	/**
	 * Interceptor for the constructor __construct().
	 * ' . '
	 * @return mixed Result of the advice chain or the original method
	 */
	public function __construct(PARAMETERSCODE2, \F3\FLOW3\Object\ManagerInterface $FLOW3_AOP_Proxy_objectManager, \F3\FLOW3\Object\FactoryInterface $FLOW3_AOP_Proxy_objectFactory) {
		$this->objectManager = $FLOW3_AOP_Proxy_objectManager;
		$this->objectFactory = $FLOW3_AOP_Proxy_objectFactory;
		$result = NULL;
		$this->FLOW3_AOP_Proxy_declareMethodsAndAdvices();
		' . '
		if (isset($this->methodIsInAdviceMode[\'__construct\'])) {
			parent::__construct(PARAMETERSCODE1);
		} else {
			$methodArguments = array(ARGUMENTSARRAYCODE	\'FLOW3_AOP_Proxy_objectManager\' => $FLOW3_AOP_Proxy_objectManager, \'FLOW3_AOP_Proxy_objectFactory\' => $FLOW3_AOP_Proxy_objectFactory
			);
			$this->methodIsInAdviceMode[\'__construct\'] = TRUE;
			ADVICESCODE
			unset ($this->methodIsInAdviceMode[\'__construct\']);
		}

		return $result;
	}
';

		$builder = $this->getMock('F3\FLOW3\AOP\AdvicedConstructorInterceptorBuilder', array('buildAdvicesCode', 'buildMethodParametersCode', 'buildMethodArgumentsArrayCode'), array(), '', FALSE);
		$builder->expects($this->at(0))->method('buildMethodParametersCode')->with($className, '__construct', FALSE)->will($this->returnValue('PARAMETERSCODE1'));
		$builder->expects($this->at(1))->method('buildMethodArgumentsArrayCode')->with($className, '__construct')->will($this->returnValue('ARGUMENTSARRAYCODE'));
		$builder->expects($this->once())->method('buildAdvicesCode')->with(array('groupedAdvicesDummy'), '__construct', 'Bar')->will($this->returnValue('ADVICESCODE'));
		$builder->expects($this->at(3))->method('buildMethodParametersCode')->with($className, '__construct', TRUE)->will($this->returnValue('PARAMETERSCODE2'));

		$builder->injectReflectionService($mockReflectionService);

		$actualCode = $builder->build('__construct', $interceptedMethods, 'Bar');
		$this->assertSame($expectedCode, $actualCode);
	}
}
?>