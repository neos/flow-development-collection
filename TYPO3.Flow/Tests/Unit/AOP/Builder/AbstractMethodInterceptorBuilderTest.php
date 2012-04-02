<?php
namespace TYPO3\FLOW3\Tests\Unit\AOP\Builder;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Abstract Method Interceptor Builder
 *
 */
class AbstractMethodInterceptorBuilderTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function buildMethodArgumentsArrayCodeRendersCodeForPassingParametersToTheJoinPoint() {
		$className = 'TestClass' . md5(uniqid(mt_rand(), TRUE));
		eval('
			class ' . $className . ' {
				public function foo($arg1, array $arg2, \ArrayObject $arg3, &$arg4, $arg5= "foo", $arg6 = TRUE) {}
			}
		');
		$methodParameters = array(
			'arg1' => array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => TRUE
			),
			'arg2' => array(
				'position' => 1,
				'byReference' => FALSE,
				'array' => TRUE,
				'optional' => FALSE,
				'allowsNull' => TRUE
			),
			'arg3' => array(
				'position' => 2,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => TRUE
			),
			'arg4' => array(
				'position' => 3,
				'byReference' => TRUE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => TRUE
			),
			'arg5' => array(
				'position' => 4,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => TRUE,
				'allowsNull' => TRUE
			),
			'arg6' => array(
				'position' => 5,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => TRUE,
				'allowsNull' => TRUE
			),
		);

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getMethodParameters')->with($className, 'foo')->will($this->returnValue($methodParameters));

		$expectedCode = "
					\$methodArguments = array();

				\$methodArguments['arg1'] = \$arg1;
				\$methodArguments['arg2'] = \$arg2;
				\$methodArguments['arg3'] = \$arg3;
				\$methodArguments['arg4'] = &\$arg4;
				\$methodArguments['arg5'] = \$arg5;
				\$methodArguments['arg6'] = \$arg6;
			";

		$builder = $this->getAccessibleMock('TYPO3\FLOW3\AOP\Builder\AbstractMethodInterceptorBuilder', array('build'), array(), '', FALSE);
		$builder->injectReflectionService($mockReflectionService);

		$actualCode = $builder->_call('buildMethodArgumentsArrayCode', $className, 'foo');
		$this->assertSame($expectedCode, $actualCode);
	}

	/**
	 * @test
	 */
	public function buildMethodArgumentsArrayCodeReturnsAnEmptyStringIfTheClassNameIsNULL() {
		$builder = $this->getAccessibleMock('TYPO3\FLOW3\AOP\Builder\AbstractMethodInterceptorBuilder', array('build'), array(), '', FALSE);

		$actualCode = $builder->_call('buildMethodArgumentsArrayCode', NULL, 'foo');
		$this->assertSame('', $actualCode);
	}

	/**
	 * @test
	 */
	public function buildSavedConstructorParametersCodeReturnsTheCorrectParametersCode() {
		$className = 'TestClass' . md5(uniqid(mt_rand(), TRUE));
		eval('
			class ' . $className . ' {
				public function __construct($arg1, array $arg2, \ArrayObject $arg3, $arg4= "__construct", $arg5 = TRUE) {}
			}
		');
		$methodParameters = array(
			'arg1' => array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => TRUE
			),
			'arg2' => array(
				'position' => 1,
				'byReference' => FALSE,
				'array' => TRUE,
				'optional' => FALSE,
				'allowsNull' => TRUE
			),
			'arg3' => array(
				'position' => 2,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => TRUE
			),
			'arg4' => array(
				'position' => 3,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => TRUE,
				'allowsNull' => TRUE
			),
			'arg5' => array(
				'position' => 4,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => TRUE,
				'allowsNull' => TRUE
			),
		);

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getMethodParameters')->with($className, '__construct')->will($this->returnValue($methodParameters));

		$builder = $this->getAccessibleMock('TYPO3\FLOW3\AOP\Builder\AdvicedConstructorInterceptorBuilder', array('dummy'), array(), '', FALSE);
		$builder->injectReflectionService($mockReflectionService);

		$expectedCode = '$this->FLOW3_AOP_Proxy_originalConstructorArguments[\'arg1\'], $this->FLOW3_AOP_Proxy_originalConstructorArguments[\'arg2\'], $this->FLOW3_AOP_Proxy_originalConstructorArguments[\'arg3\'], $this->FLOW3_AOP_Proxy_originalConstructorArguments[\'arg4\'], $this->FLOW3_AOP_Proxy_originalConstructorArguments[\'arg5\']';
		$actualCode = $builder->_call('buildSavedConstructorParametersCode', $className);

		$this->assertSame($expectedCode, $actualCode);
	}

}
?>