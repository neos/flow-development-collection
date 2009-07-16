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
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AdvicedMethodInterceptorBuilderTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildRendersMethodCodeWithArgumentsOfTheOriginalMethodAndAdditionalInterceptionCode() {
		$className = uniqid('TestClass');
		eval('
			class ' . $className . ' {
				public function foo($arg1, array $arg2, \ArrayObject $arg3, $arg4= "foo", $arg5 = TRUE) {}
			}
		');

		$interceptedMethods = array(
			'foo' => array(
				'groupedAdvices' => array('groupedAdvicesDummy'),
				'declaringClassName' => $className
			)
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array('loadFromCache', 'saveToCache'), array(), '', FALSE, TRUE);
		$mockReflectionService->initialize(array($className));

		$expectedCode = '
	/**
	 * Interceptor for the method foo().
	 * ' . '
	 * @return mixed Result of the advice chain or the original method
	 */
	public function foo(PARAMETERSCODE1) {

		if (isset($this->methodIsInAdviceMode[\'foo\'])) {

			$result = parent::foo(PARAMETERSCODE2);

		} else {
			$methodArguments = array(
				\'arg1\' => $arg1,
				\'arg2\' => $arg2,
				\'arg3\' => $arg3,
				\'arg4\' => $arg4,
				\'arg5\' => $arg5,
			);
			$this->methodIsInAdviceMode[\'foo\'] = TRUE;
			ADVICESCODE
			unset ($this->methodIsInAdviceMode[\'foo\']);
		}
		return $result;

	}
';

		$builder = $this->getMock('F3\FLOW3\AOP\Builder\AdvicedMethodInterceptorBuilder', array('buildAdvicesCode', 'buildMethodParametersCode'), array(), '', FALSE);
		$builder->expects($this->once())->method('buildAdvicesCode')->with(array('groupedAdvicesDummy'), 'foo', 'Bar')->will($this->returnValue('ADVICESCODE'));
		$builder->expects($this->at(1))->method('buildMethodParametersCode')->with($className, 'foo', TRUE)->will($this->returnValue('PARAMETERSCODE1'));
		$builder->expects($this->at(2))->method('buildMethodParametersCode')->with($className, 'foo', FALSE)->will($this->returnValue('PARAMETERSCODE2'));

		$builder->injectReflectionService($mockReflectionService);

		$actualCode = $builder->build('foo', $interceptedMethods, 'Bar');

		$this->assertSame($expectedCode, $actualCode);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildRendersParentCallToWakeupMethodIfTargetClassHadAWakeupMethodAsWell() {
		$className = uniqid('TestClass');
		eval('
			class ' . $className . ' {
				public function __wakeup($arg1, $arg2) {}
			}
		');

		$interceptedMethods = array(
			'__wakeup' => array(
				'groupedAdvices' => array('groupedAdvicesDummy'),
				'declaringClassName' => $className
			)
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array('loadFromCache', 'saveToCache'), array(), '', FALSE, TRUE);
		$mockReflectionService->initialize(array($className));

		$expectedCode = '
	/**
	 * Interceptor for the method __wakeup().
	 * ' . '
	 * @return mixed Result of the advice chain or the original method
	 */
	public function __wakeup(PARAMETERSCODE1) {

		if (isset($this->methodIsInAdviceMode[\'__wakeup\'])) {

			$result = parent::__wakeup(PARAMETERSCODE2);

		} else {
			$methodArguments = array(
				\'arg1\' => $arg1,
				\'arg2\' => $arg2,
			);
			$this->methodIsInAdviceMode[\'__wakeup\'] = TRUE;
			ADVICESCODE
			unset ($this->methodIsInAdviceMode[\'__wakeup\']);
		}
		return $result;

	}
';

		$builder = $this->getMock('F3\FLOW3\AOP\Builder\AdvicedMethodInterceptorBuilder', array('buildAdvicesCode', 'buildMethodParametersCode', 'buildWakeupCode'), array(), '', FALSE);
		$builder->expects($this->once())->method('buildAdvicesCode')->with(array('groupedAdvicesDummy'), '__wakeup', 'Bar')->will($this->returnValue('ADVICESCODE'));
		$builder->expects($this->at(1))->method('buildMethodParametersCode')->with($className, '__wakeup', TRUE)->will($this->returnValue('PARAMETERSCODE1'));
		$builder->expects($this->at(2))->method('buildMethodParametersCode')->with($className, '__wakeup', FALSE)->will($this->returnValue('PARAMETERSCODE2'));

		$builder->injectReflectionService($mockReflectionService);

		$actualCode = $builder->build('__wakeup', $interceptedMethods, 'Bar');
		$this->assertSame($expectedCode, $actualCode);
	}
}
?>