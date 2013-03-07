<?php
namespace TYPO3\Flow\Aop\Builder;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * An abstract class with builder functions for AOP method interceptors code
 * builders.
 *
 */
abstract class AbstractMethodInterceptorBuilder {

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\Flow\Object\Proxy\Compiler
	 */
	protected $compiler;

	/**
	 * Injects the reflection service
	 *
	 * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService The reflection service
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param \TYPO3\Flow\Object\Proxy\Compiler $compiler
	 * @return void
	 */
	public function injectCompiler(\TYPO3\Flow\Object\Proxy\Compiler $compiler) {
		$this->compiler = $compiler;
	}

	/**
	 * Builds method interception PHP code
	 *
	 * @param string $methodName Name of the method to build an interceptor for
	 * @param array $methodMetaInformation An array of method names and their meta information, including advices for the method (if any)
	 * @param string $targetClassName Name of the target class to build the interceptor for
	 * @return string PHP code of the interceptor
	 */
	abstract public function build($methodName, array $methodMetaInformation, $targetClassName);

	/**
	 * Builds a string containing PHP code to build the array given as input.
	 *
	 * @param array $array
	 * @return string e.g. 'array()' or 'array(1 => 'bar')
	 */
	protected function buildArraySetupCode(array $array) {
		$code = 'array(';
		foreach ($array as $key => $value) {
			$code .= (is_string($key)) ? "'" . $key  . "'" : $key;
			$code .= ' => ';
			if ($value === NULL) {
				$code .= 'NULL';
			} elseif (is_bool($value)) {
				$code .= ($value ? 'TRUE' : 'FALSE');
			} elseif (is_numeric($value)) {
				$code .= $value;
			} elseif (is_string($value)) {
				$code .= "'" . $value . "'";
			}
			$code .= ', ';
		}
		return rtrim($code, ', ') . ')';
	}

	/**
	 * Builds the PHP code for the method arguments array which is passed to
	 * the constructor of a new join point. Used in the method interceptor
	 * functions.
	 *
	 * @param string $className Name of the declaring class of the method
	 * @param string $methodName Name of the method to create arguments array code for
	 * @param boolean $useArgumentsArray If set, the $methodArguments array will be built from $arguments instead of using the actual parameter variables.
	 * @return string The generated code to be used in an "array()" definition
	 */
	protected function buildMethodArgumentsArrayCode($className, $methodName, $useArgumentsArray = FALSE) {
		if ($className === NULL || $methodName === NULL) {
			return '';
		}

		$argumentsArrayCode = "\n\t\t\t\t\t\$methodArguments = array();\n";

		$methodParameters = $this->reflectionService->getMethodParameters($className, $methodName);
		if (count($methodParameters) > 0) {
			$argumentsArrayCode .= "\n";
			$argumentIndex = 0;
			foreach ($methodParameters as $methodParameterName => $methodParameterInfo) {
				if ($useArgumentsArray) {
					$argumentsArrayCode .= "\t\t\t\tif (array_key_exists(" . $argumentIndex . ", \$arguments)) \$methodArguments['" . $methodParameterName . "'] = \$arguments[" . $argumentIndex . "];\n";
				} else {
					$argumentsArrayCode .= "\t\t\t\t\$methodArguments['" . $methodParameterName . "'] = ";
					$argumentsArrayCode .= $methodParameterInfo['byReference'] ? '&' : '';
					$argumentsArrayCode .= '$' . $methodParameterName . ";\n";
				}
				$argumentIndex ++;
			}
			$argumentsArrayCode .= "\t\t\t";
		}
		return $argumentsArrayCode;
	}

	/**
	 * Generates the parameters code needed to call the constructor with the saved parameters.
	 *
	 * @param string $className Name of the class the method is declared in
	 * @return string The generated parameters code
	 */
	protected function buildSavedConstructorParametersCode($className) {
		if ($className === NULL) {
			return '';
		}

		$parametersCode = '';
		$methodParameters = $this->reflectionService->getMethodParameters($className, '__construct');
		$methodParametersCount = count($methodParameters);
		if ($methodParametersCount > 0) {
			foreach ($methodParameters as $methodParameterName => $methodParameterInfo) {
				$methodParametersCount--;
				$parametersCode .= '$this->Flow_Aop_Proxy_originalConstructorArguments[\'' . $methodParameterName . '\']' . ($methodParametersCount > 0 ? ', ' : '');
			}
		}
		return $parametersCode;
	}

	/**
	 * Builds the advice interception code, to be used in a method interceptor.
	 *
	 * @param array $groupedAdvices The advices grouped by advice type
	 * @param string $methodName Name of the method the advice applies to
	 * @param string $targetClassName Name of the target class
	 * @param string $declaringClassName Name of the declaring class. This is usually the same as the $targetClassName. However, it is the introduction interface for introduced methods.
	 * @return string PHP code to be used in the method interceptor
	 */
	protected function buildAdvicesCode(array $groupedAdvices, $methodName, $targetClassName, $declaringClassName) {
		$advicesCode = $this->buildMethodArgumentsArrayCode($declaringClassName, $methodName, ($methodName === '__construct'));

		if (isset ($groupedAdvices['TYPO3\Flow\Aop\Advice\AfterThrowingAdvice']) || isset ($groupedAdvices['TYPO3\Flow\Aop\Advice\AfterAdvice'])) {
			$advicesCode .= "\n\t\t\$result = NULL;\n\t\t\$afterAdviceInvoked = FALSE;\n\t\ttry {\n";
		}

		$methodArgumentsCode = '$methodArguments';

		if (isset ($groupedAdvices['TYPO3\Flow\Aop\Advice\BeforeAdvice'])) {
			$advicesCode .= '
					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'TYPO3\Flow\Aop\Advice\BeforeAdvice\'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, \'' . $targetClassName . '\', \'' . $methodName . '\', ' . $methodArgumentsCode . ');
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}
';
			$methodArgumentsCode = '$joinPoint->getMethodArguments()';
		}

		if (isset ($groupedAdvices['TYPO3\Flow\Aop\Advice\AroundAdvice'])) {
			$advicesCode .= '
					$adviceChains = $this->Flow_Aop_Proxy_getAdviceChains(\'' . $methodName . '\');
					$adviceChain = $adviceChains[\'TYPO3\Flow\Aop\Advice\AroundAdvice\'];
					$adviceChain->rewind();
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, \'' . $targetClassName . '\', \'' . $methodName . '\', ' . $methodArgumentsCode . ', $adviceChain);
					$result = $adviceChain->proceed($joinPoint);
';
			$methodArgumentsCode = '$joinPoint->getMethodArguments()';
		} else {
			$advicesCode .= '
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, \'' . $targetClassName . '\', \'' . $methodName . '\', ' . $methodArgumentsCode . ');
					$result = $this->Flow_Aop_Proxy_invokeJoinPoint($joinPoint);
';
			$methodArgumentsCode = '$joinPoint->getMethodArguments()';
		}

		if (isset ($groupedAdvices['TYPO3\Flow\Aop\Advice\AfterReturningAdvice'])) {
			$advicesCode .= '
					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'TYPO3\Flow\Aop\Advice\AfterReturningAdvice\'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, \'' . $targetClassName . '\', \'' . $methodName . '\', ' . $methodArgumentsCode . ', NULL, $result);
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}
';
			$methodArgumentsCode = '$joinPoint->getMethodArguments()';
		}

		if (isset ($groupedAdvices['TYPO3\Flow\Aop\Advice\AfterAdvice'])) {
			$advicesCode .= '
					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'TYPO3\Flow\Aop\Advice\AfterAdvice\'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, \'' . $targetClassName . '\', \'' . $methodName . '\', ' . $methodArgumentsCode . ', NULL, $result);
					$afterAdviceInvoked = TRUE;
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}
';
			$methodArgumentsCode = '$joinPoint->getMethodArguments()';
		}

		if (isset ($groupedAdvices['TYPO3\Flow\Aop\Advice\AfterThrowingAdvice']) || isset ($groupedAdvices['TYPO3\Flow\Aop\Advice\AfterAdvice'])) {
			$advicesCode .= '
			} catch (\Exception $exception) {
';
		}

		if (isset ($groupedAdvices['TYPO3\Flow\Aop\Advice\AfterThrowingAdvice'])) {
			$advicesCode .= '
				$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'TYPO3\Flow\Aop\Advice\AfterThrowingAdvice\'];
				$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, \'' . $targetClassName . '\', \'' . $methodName . '\', ' . $methodArgumentsCode . ', NULL, NULL, $exception);
				foreach ($advices as $advice) {
					$advice->invoke($joinPoint);
				}
';
			$methodArgumentsCode = '$joinPoint->getMethodArguments()';
		}

		if (isset ($groupedAdvices['TYPO3\Flow\Aop\Advice\AfterAdvice'])) {
			$advicesCode .= '
				if (!$afterAdviceInvoked) {
					$advices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'TYPO3\Flow\Aop\Advice\AfterAdvice\'];
					$joinPoint = new \TYPO3\Flow\Aop\JoinPoint($this, \'' . $targetClassName . '\', \'' . $methodName . '\', ' . $methodArgumentsCode . ', NULL, NULL, $exception);
					foreach ($advices as $advice) {
						$advice->invoke($joinPoint);
					}
				}
';
		}

		if (isset ($groupedAdvices['TYPO3\Flow\Aop\Advice\AfterThrowingAdvice']) || isset ($groupedAdvices['TYPO3\Flow\Aop\Advice\AfterAdvice'])) {
			$advicesCode .= '
				throw $exception;
		}
';
		}

		return $advicesCode;
	}

}

?>