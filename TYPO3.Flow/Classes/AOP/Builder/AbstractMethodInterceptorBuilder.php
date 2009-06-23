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
 * An abstract class with builder functions for AOP method interceptors code
 * builders.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class AbstractMethodInterceptorBuilder {

	/**
	 * @var F3\FLOW3\Reflection\Service
	 */
	protected $reflectionService;

	/**
	 * Injects the reflection service
	 *
	 * @param F3\FLOW3\Reflection\Service $reflectionService The reflection service
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Builds method interception PHP code
	 *
	 * @param string $methodName Name of the method to build an interceptor for
	 * @param array $interceptedMethods An array of method names and their meta information, including advices for the method (if any)
	 * @param string $targetClassName Name of the target class to build the interceptor for
	 * @return string PHP code of the interceptor
	 * @author Robert Lemke <robert@typo3.org>
	 */
	abstract public function build($methodName, array $methodMetaInformation, $targetClassName);

	/**
	 * Builds the PHP code for the parameters of the specified method to be
	 * used in a method interceptor in the proxy class
	 *
	 * @param string $className Name of the class the method is declared in
	 * @param string $methodName Name of the method to create the parameters code for
	 * @param boolean $addTypeAndDefaultValue Adds the type and default value for each parameters (if any)
	 * @param string $methodParametersDocumentation Passed by reference, will contain the DocComment for the given method
	 * @return string A comma speparated list of parameters
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function buildMethodParametersCode($className, $methodName, $addTypeAndDefaultValue, &$methodParametersDocumentation = '') {
		$methodParametersCode = '';
		$methodParameterTypeName = '';
		$defaultValue = '';
		$byReferenceSign = '';

		if ($className === NULL || $methodName === NULL) return '';

		$methodParameters = $this->reflectionService->getMethodParameters($className, $methodName);
		if (count($methodParameters) > 0) {
			$methodParametersCount = 0;
			$methodParameterComments = $this->reflectionService->getMethodTagsValues($className, $methodName);
			foreach ($methodParameters as $methodParameterName => $methodParameterInfo) {
				if ($addTypeAndDefaultValue) {
					if ($methodParameterInfo['array'] === TRUE) {
						$methodParameterTypeName = 'array';
					} else {
						$methodParameterTypeName = ($methodParameterInfo['class'] === NULL) ? '' : '\\' . $methodParameterInfo['class'];
					}
					$methodParameterDocumentationTypeName = ($methodParameterTypeName ? $methodParameterTypeName : 'unknown_type' );
					if (isset($methodParameterComments['param'][$methodParameterInfo['position']])) {
						$explodedComment = explode(' ', $methodParameterComments['param'][$methodParameterInfo['position']]);
						if ($methodParameterDocumentationTypeName === 'unknown_type') {
							$methodParameterDocumentationTypeName = $explodedComment[0];
						}
						$methodParameterComment = isset($explodedComment[2]) ? ' ' . implode(' ', array_slice($explodedComment, 2)) : '';
					} else {
						$methodParameterComment = '';
					}
					$methodParametersDocumentation .= "\n\t * @param  " . $methodParameterDocumentationTypeName . " $" . $methodParameterName . $methodParameterComment;
					if ($methodParameterInfo['optional'] === TRUE) {
						$rawDefaultValue = $methodParameterInfo['defaultValue'];
						if ($rawDefaultValue === NULL) {
							$defaultValue = ' = NULL';
						} elseif (is_bool($rawDefaultValue)) {
							$defaultValue = ($rawDefaultValue ? ' = TRUE' : ' = FALSE');
						} elseif (is_numeric($rawDefaultValue)) {
							$defaultValue = ' = ' . $rawDefaultValue;
						} elseif (is_string($rawDefaultValue)) {
							$defaultValue = " = '" . $rawDefaultValue . "'";
						}
					}
					$byReferenceSign = ($methodParameterInfo['byReference'] ? '&' : '');
				}

				$methodParametersCode .= ($methodParametersCount > 0 ? ', ' : '') . ($methodParameterTypeName ? $methodParameterTypeName . ' ' : '') . $byReferenceSign . '$' . $methodParameterName . $defaultValue;
				$methodParametersCount ++;
			}
			if (isset($methodParameterComments['return'])) {
				$methodParametersDocumentation  .= "\n\t * @return " . implode(' ', $methodParameterComments['return']);
			}
		}
		return $methodParametersCode;
	}

	/**
	 * Builds the PHP code for the method arguments array which is passed to
	 * the constructor of a new join point. Used in the method interceptor
	 * functions
	 *
	 * @param string $className Name of the declaring class of the method
	 * @param string $methodName Name of the method to create arguments array code for
	 * @return string The generated code to be used in an "array()" definition
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function buildMethodArgumentsArrayCode($className, $methodName) {
		if ($className === NULL || $methodName === NULL) return '';
		$argumentsArrayCode = '';
		$methodParameters = $this->reflectionService->getMethodParameters($className, $methodName);
		if (count($methodParameters) > 0) {
			$argumentsArrayCode .= "\n";
			foreach ($methodParameters as $methodParameterName => $methodParameterInfo) {
				$argumentsArrayCode .= "\t\t\t\t'" . $methodParameterName . "' => \$" . $methodParameterName . ",\n";
			}
			$argumentsArrayCode .= "\t\t\t";
		}
		return $argumentsArrayCode;
	}

	/**
	 * Generates the parameters code needed to call the constructor with the saved parameters.
	 *
	 * @param string $className Name of the class the method is declared in
	 * @return string The generated paramters code
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function buildSavedConstructorParametersCode($className) {
		if ($className === NULL) return '';
		$parametersCode = '';
		$methodParameters = $this->reflectionService->getMethodParameters($className, '__construct');
		$methodParametersCount = count($methodParameters);
		if ($methodParametersCount > 0) {
			foreach ($methodParameters as $methodParameterName => $methodParameterInfo) {
				$methodParametersCount--;
				$parametersCode .= '$this->originalConstructorArguments[\'' . $methodParameterName . '\']' . ($methodParametersCount > 0 ? ', ' : '');
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
	 * @return string PHP code to be used in the method interceptor
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function buildAdvicesCode(array $groupedAdvices, $methodName, $targetClassName) {
		$advicesCode = '';

		if (isset ($groupedAdvices['F3\FLOW3\AOP\Advice\AfterThrowingAdvice']) || isset ($groupedAdvices['F3\FLOW3\AOP\Advice\AfterAdvice'])) {
			$advicesCode .= "\n\t\t\$result = NULL;\n\t\t\$afterAdviceInvoked = FALSE;\n\t\ttry {\n";
		}

		if (isset ($groupedAdvices['F3\FLOW3\AOP\Advice\BeforeAdvice'])) {
			$advicesCode .= '
			$advices = $this->targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'F3\FLOW3\AOP\Advice\BeforeAdvice\'];
			$joinPoint = new \F3\FLOW3\AOP\JoinPoint($this, \'' . $targetClassName . '\', \'' . $methodName . '\', $methodArguments);
			foreach ($advices as $advice) {
				$advice->invoke($joinPoint);
			}
';
		}

		if (isset ($groupedAdvices['F3\FLOW3\AOP\Advice\AroundAdvice'])) {
			$advicesCode .= '
			$adviceChains = $this->FLOW3_AOP_Proxy_getAdviceChains(\'' . $methodName . '\');
			$adviceChain = $adviceChains[\'F3\FLOW3\AOP\Advice\AroundAdvice\'];
			$adviceChain->rewind();
			$result = $adviceChain->proceed(new \F3\FLOW3\AOP\JoinPoint($this, \'' . $targetClassName . '\', \'' . $methodName . '\', $methodArguments, $adviceChain));
';
		} else {
			$advicesCode .= '
			$joinPoint = new \F3\FLOW3\AOP\JoinPoint($this, \'' . $targetClassName . '\', \'' . $methodName . '\', $methodArguments);
			$result = $this->FLOW3_AOP_Proxy_invokeJoinPoint($joinPoint);
';
		}

		if (isset ($groupedAdvices['F3\FLOW3\AOP\Advice\AfterReturningAdvice'])) {
			$advicesCode .= '
			$advices = $this->targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'F3\FLOW3\AOP\Advice\AfterReturningAdvice\'];
			$joinPoint = new \F3\FLOW3\AOP\JoinPoint($this, \'' . $targetClassName . '\', \'' . $methodName . '\', $methodArguments, NULL, $result);
			foreach ($advices as $advice) {
				$advice->invoke($joinPoint);
			}
';
		}

		if (isset ($groupedAdvices['F3\FLOW3\AOP\Advice\AfterAdvice'])) {
			$advicesCode .= '
			$advices = $this->targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'F3\FLOW3\AOP\Advice\AfterAdvice\'];
			$joinPoint = new \F3\FLOW3\AOP\JoinPoint($this, \'' . $targetClassName . '\', \'' . $methodName . '\', $methodArguments, NULL, $result);
			$afterAdviceInvoked = TRUE;
			foreach ($advices as $advice) {
				$advice->invoke($joinPoint);
			}
';
		}

		if (isset ($groupedAdvices['F3\FLOW3\AOP\Advice\AfterThrowingAdvice']) || isset ($groupedAdvices['F3\FLOW3\AOP\Advice\AfterAdvice'])) {
			$advicesCode .= '
		} catch (\Exception $exception) {
';
		}

		if (isset ($groupedAdvices['F3\FLOW3\AOP\Advice\AfterThrowingAdvice'])) {
			$advicesCode .= '
			$advices = $this->targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'F3\FLOW3\AOP\Advice\AfterThrowingAdvice\'];
			$joinPoint = new \F3\FLOW3\AOP\JoinPoint($this, \'' . $targetClassName . '\', \'' . $methodName . '\', $methodArguments, NULL, NULL, $exception);
			foreach ($advices as $advice) {
				$advice->invoke($joinPoint);
			}
';
		}

		if (isset ($groupedAdvices['F3\FLOW3\AOP\Advice\AfterAdvice'])) {
			$advicesCode .= '
			if (!$afterAdviceInvoked) {
				$advices = $this->targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'F3\FLOW3\AOP\Advice\AfterAdvice\'];
				$joinPoint = new \F3\FLOW3\AOP\JoinPoint($this, \'' . $targetClassName . '\', \'' . $methodName . '\', $methodArguments, NULL, NULL, $exception);
				foreach ($advices as $advice) {
					$advice->invoke($joinPoint);
				}
			}
';
		}

		if (isset ($groupedAdvices['F3\FLOW3\AOP\Advice\AfterThrowingAdvice']) || isset ($groupedAdvices['F3\FLOW3\AOP\Advice\AfterAdvice'])) {
			$advicesCode .= '
		}
';
		}

		return $advicesCode;
	}

}

?>