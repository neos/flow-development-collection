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
 * Testcase for the Abstract Method Interceptor Builder
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:\F3\FLOW3\AOP\FrameworkTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AbstractMethodInterceptorBuilderTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildMethodParametersCodeRendersParametersCodeWithCorrectTypeHintsAndDefaultValues() {
		$className = uniqid('TestClass');
		eval('
			/**
			 * @param string $arg1 Arg1
			 */
			class ' . $className . ' {
				public function foo($arg1, array $arg2, \ArrayObject $arg3, $arg4= "foo", $arg5 = TRUE) {}
			}
		');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array('loadFromCache', 'saveToCache'), array(), '', FALSE, TRUE);
		$mockReflectionService->initialize(array($className));

		$expectedCode = '$arg1, array $arg2, \ArrayObject $arg3, $arg4 = \'foo\', $arg5 = TRUE';
		$parametersDocumentation = '';

		$builder = $this->getMock('F3\FLOW3\AOP\AbstractMethodInterceptorBuilder', array('build'), array(), '', FALSE);
		$builder->injectReflectionService($mockReflectionService);

		$actualCode = $builder->buildMethodParametersCode($className, 'foo', TRUE, $parametersDocumentation);
		$this->assertSame($expectedCode, $actualCode);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildMethodParametersCodeOmitsTypeHintsAndDefaultValuesIfToldSo() {
		$className = uniqid('TestClass');
		eval('
			class ' . $className . ' {
				public function foo($arg1, array $arg2, \ArrayObject $arg3, $arg4= "foo", $arg5 = TRUE) {}
			}
		');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array('loadFromCache', 'saveToCache'), array(), '', FALSE, TRUE);
		$mockReflectionService->initialize(array($className));

		$expectedCode = '$arg1, $arg2, $arg3, $arg4, $arg5';
		$parametersDocumentation = '';

		$builder = $this->getMock('F3\FLOW3\AOP\AbstractMethodInterceptorBuilder', array('build'), array(), '', FALSE);
		$builder->injectReflectionService($mockReflectionService);

		$actualCode = $builder->buildMethodParametersCode($className, 'foo', FALSE, $parametersDocumentation);
		$this->assertSame($expectedCode, $actualCode);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildMethodParametersCodeRendersDocCommentForTheDocumentedAndUndocumentedParameters() {
		$className = uniqid('TestClass');
		eval('
			class ' . $className . ' {
				/**
				 * @param string $arg1 Argument1
				 * @param array $arg2 Argument2
				 * @param \WrongDocumentedType $arg3 Argument3
				 * @return string ReturnValue
				 */
				public function foo($arg1, array $arg2, \ArrayObject $arg3, $arg4= "foo", $arg5 = TRUE) {}
			}
		');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array('loadFromCache', 'saveToCache'), array(), '', FALSE, TRUE);
		$mockReflectionService->initialize(array($className));

		$expectedParametersDocumentation = '
	 * @param  string $arg1 Argument1
	 * @param  array $arg2 Argument2
	 * @param  \ArrayObject $arg3 Argument3
	 * @param  unknown_type $arg4
	 * @param  unknown_type $arg5
	 * @return string ReturnValue';

		$actualParametersDocumentation = '';

		$builder = $this->getMock('F3\FLOW3\AOP\AbstractMethodInterceptorBuilder', array('build'), array(), '', FALSE);
		$builder->injectReflectionService($mockReflectionService);

		$builder->buildMethodParametersCode($className, 'foo', TRUE, $actualParametersDocumentation);
		$this->assertSame($expectedParametersDocumentation, $actualParametersDocumentation);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildMethodParametersCodeReturnsAnEmptyStringIfTheClassNameIsNULL() {
		$builder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\AbstractMethodInterceptorBuilder'), array('build'), array(), '', FALSE);

		$parametersDocumentation = '';
		$actualCode = $builder->buildMethodParametersCode(NULL, 'foo', TRUE, $parametersDocumentation);
		$this->assertSame('', $actualCode);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildMethodArgumentsArrayCodeRendersCodeForPassingParametersToTheJoinPoint() {
		$className = uniqid('TestClass');
		eval('
			class ' . $className . ' {
				public function foo($arg1, array $arg2, \ArrayObject $arg3, $arg4= "foo", $arg5 = TRUE) {}
			}
		');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array('loadFromCache', 'saveToCache'), array(), '', FALSE, TRUE);
		$mockReflectionService->initialize(array($className));

		$expectedCode = "
				'arg1' => \$arg1,
				'arg2' => \$arg2,
				'arg3' => \$arg3,
				'arg4' => \$arg4,
				'arg5' => \$arg5,
			";

		$builder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\AbstractMethodInterceptorBuilder'), array('build'), array(), '', FALSE);
		$builder->injectReflectionService($mockReflectionService);

		$actualCode = $builder->_call('buildMethodArgumentsArrayCode', $className, 'foo');
		$this->assertSame($expectedCode, $actualCode);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildMethodArgumentsArrayCodeReturnsAnEmptyStringIfTheClassNameIsNULL() {
		$builder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\AbstractMethodInterceptorBuilder'), array('build'), array(), '', FALSE);

		$actualCode = $builder->_call('buildMethodArgumentsArrayCode', NULL, 'foo');
		$this->assertSame('', $actualCode);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildAdvicesCodeRendersMethodInterceptionCodeForAfterThrowingAdvice() {
		$groupedAdvices = array(
			'F3\FLOW3\AOP\AfterThrowingAdvice' => array()
		);
		$expectedCode = '
		$result = NULL;
		$afterAdviceInvoked = FALSE;
		try {

			$joinPoint = new \F3\FLOW3\AOP\JoinPoint($this, \'TargetClass\', \'foo\', $methodArguments);
			$result = $this->AOPProxyInvokeJoinPoint($joinPoint);

		} catch (\Exception $exception) {

			$advices = $this->targetMethodsAndGroupedAdvices[\'foo\'][\'F3\FLOW3\AOP\AfterThrowingAdvice\'];
			$joinPoint = new \F3\FLOW3\AOP\JoinPoint($this, \'TargetClass\', \'foo\', $methodArguments, NULL, NULL, $exception);
			foreach ($advices as $advice) {
				$advice->invoke($joinPoint);
			}

		}' . chr(10);

		$builder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\AbstractMethodInterceptorBuilder'), array('build'), array(), '', FALSE);
		$actualCode = $builder->_call('buildAdvicesCode', $groupedAdvices, 'foo', 'TargetClass');
		$this->assertSame($expectedCode, $actualCode);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildAdvicesCodeRendersMethodInterceptionCodeForAroundAdviceCombinedWithBeforeAndAfterAdvice() {
		$groupedAdvices = array(
			'F3\FLOW3\AOP\BeforeAdvice' => array(),
			'F3\FLOW3\AOP\AroundAdvice' => array(),
			'F3\FLOW3\AOP\AfterAdvice' => array()
		);
		$expectedCode = '
		$result = NULL;
		$afterAdviceInvoked = FALSE;
		try {

			$advices = $this->targetMethodsAndGroupedAdvices[\'foo\'][\'F3\FLOW3\AOP\BeforeAdvice\'];
			$joinPoint = new \F3\FLOW3\AOP\JoinPoint($this, \'TargetClass\', \'foo\', $methodArguments);
			foreach ($advices as $advice) {
				$advice->invoke($joinPoint);
			}

			$adviceChains = $this->AOPProxyGetAdviceChains(\'foo\');
			$adviceChain = $adviceChains[\'F3\FLOW3\AOP\AroundAdvice\'];
			$adviceChain->rewind();
			$result = $adviceChain->proceed(new \F3\FLOW3\AOP\JoinPoint($this, \'TargetClass\', \'foo\', $methodArguments, $adviceChain));

			$advices = $this->targetMethodsAndGroupedAdvices[\'foo\'][\'F3\FLOW3\AOP\AfterAdvice\'];
			$joinPoint = new \F3\FLOW3\AOP\JoinPoint($this, \'TargetClass\', \'foo\', $methodArguments, NULL, $result);
			$afterAdviceInvoked = TRUE;
			foreach ($advices as $advice) {
				$advice->invoke($joinPoint);
			}

		} catch (\Exception $exception) {

			if (!$afterAdviceInvoked) {
				$advices = $this->targetMethodsAndGroupedAdvices[\'foo\'][\'F3\FLOW3\AOP\AfterAdvice\'];
				$joinPoint = new \F3\FLOW3\AOP\JoinPoint($this, \'TargetClass\', \'foo\', $methodArguments, NULL, NULL, $exception);
				foreach ($advices as $advice) {
					$advice->invoke($joinPoint);
				}
			}

		}' . chr(10);

		$builder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\AbstractMethodInterceptorBuilder'), array('build'), array(), '', FALSE);
		$actualCode = $builder->_call('buildAdvicesCode', $groupedAdvices, 'foo', 'TargetClass');
		$this->assertSame($expectedCode, $actualCode);
	}
}
?>