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
 * @version $Id:F3::FLOW3::AOP::AbstractMethodInterceptorBuilder.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
abstract class AbstractMethodInterceptorBuilder {

	/**
	 * Builds method interception PHP code
	 *
	 * @param string $methodName Name of the method to build an interceptor for
	 * @param array $interceptedMethods An array of method names and their meta information, including advices for the method (if any)
	 * @param F3::FLOW3::Reflection::ReflectionClass $targetClass A reflection of the target class to build the interceptor for
	 * @return string PHP code of the interceptor
	 * @author Robert Lemke <robert@typo3.org>
	 */
	abstract static public function build($methodName, array $methodMetaInformation, F3::FLOW3::Reflection::ReflectionClass $targetClass);

	/**
	 * Builds the PHP code for the parameters of the specified method to be
	 * used in a method interceptor in the proxy class
	 *
	 * @param F3::FLOW3::Reflection::Method $method The method to create the parameters code for
	 * @param boolean $addTypeAndDefaultValue Adds the type and default value for each parameters (if any)
	 * @return string A comma speparated list of parameters
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static public function buildMethodParametersCode(F3::FLOW3::Reflection::Method $method = NULL, $addTypeAndDefaultValue, &$parametersDocumentation = '') {
		$parametersCode = '';
		$parameterTypeName = '';
		$defaultValue = '';
		$byReferenceSign = '';

		if ($method === NULL) return '';
		if ($method->getNumberOfParameters() > 0) {
			$parameterCount = 0;
			foreach ($method->getParameters() as $parameter) {
				if ($addTypeAndDefaultValue) {
					try {
						if ($parameter->isArray()) {
							$parameterTypeName = 'array';
						} else {
							$parameterReflectionClass = $parameter->getClass();
							$parameterTypeName = (is_object($parameterReflectionClass) ? $parameterReflectionClass->getName() : '');
						}
					} catch (::Exception $exception) {
						throw new F3::FLOW3::AOP::Exception::InvalidConstructorSignature('The parameter reflection for the method ' . $method->getDeclaringClass()->getName() . '::' . $method->getName() . '() declared in file "' . $method->getFileName() . '" throwed an exception. Please check if the classes of the parameters exist.', 1169420882);
					}
					$parametersDocumentation .= "\n\t * @param  " . ($parameterTypeName ? $parameterTypeName . ' ' : 'unknown ' ) . "\t$" . $parameter->getName();
					if ($parameter->isDefaultValueAvailable()) {
						$rawDefaultValue = $parameter->getDefaultValue();
						if (is_null($rawDefaultValue)) {
							$defaultValue = ' = NULL';
						} elseif (is_bool($rawDefaultValue)) {
							$defaultValue = ($rawDefaultValue ? ' = TRUE' : ' = FALSE');
						} elseif (is_numeric($rawDefaultValue)) {
							$defaultValue = ' = ' . $rawDefaultValue;
						} elseif (is_string($rawDefaultValue)) {
							$defaultValue = " = '" . $rawDefaultValue . "'";
						}
					}
					$byReferenceSign = ($parameter->isPassedByReference() ? '&' : '');
				}

				$parametersCode .= ($parameterCount > 0 ? ', ' : '') . ($parameterTypeName ? $parameterTypeName . ' ' : '') . $byReferenceSign . '$' . $parameter->getName() . $defaultValue;
				$parameterCount ++;
			}
		}
		return $parametersCode;
	}

	/**
	 * Builds the PHP code for the method arguments array which is passed to
	 * the constructor of a new join point. Used in the method interceptor
	 * functions
	 *
	 * @param F3::FLOW3::Reflection::Method $method The method to create arguments array code for
	 * @return string The generated code to be used in an "array()" definition
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static protected function buildMethodArgumentsArrayCode(F3::FLOW3::Reflection::Method $method = NULL) {
		if ($method === NULL) return '';
		$argumentsArrayCode = '';
		if ($method->getNumberOfParameters() > 0) {
			$argumentsArrayCode .= "\n";
			foreach ($method->getParameters() as $parameter) {
				$parameterName = $parameter->getName();
				$argumentsArrayCode .= "\t\t\t\t'" . $parameterName . "' => \$" . $parameterName . ",\n";
			}
			$argumentsArrayCode .= "\t\t\t";
		}
		return $argumentsArrayCode;
	}

	/**
	 * Builds the advice interception code, to be used in a method interceptor.
	 *
	 * @param array $groupedAdvices The advices grouped by advice type
	 * @param string $methodName Name of the method the advice applies to
	 * @param F3::FLOW3::Reflection::ReflectionClass $targetClass Reflection of the target class
	 * @return string PHP code to be used in the method interceptor
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static protected function buildAdvicesCode(array $groupedAdvices, $methodName, F3::FLOW3::Reflection::ReflectionClass $targetClass) {
		$advicesCode = '';

		if (isset ($groupedAdvices['F3::FLOW3::AOP::AfterThrowingAdvice'])) {
			$advicesCode .= "\n\t\t\$result = NULL;\n\t\ttry {\n";
		}

		if (isset ($groupedAdvices['F3::FLOW3::AOP::BeforeAdvice'])) {
			$advicesCode .= '
			$advices = $this->targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'F3::FLOW3::AOP::BeforeAdvice\'];
			$joinPoint = new F3::FLOW3::AOP::JoinPoint($this, \'' . $targetClass->getName() . '\', \'' . $methodName . '\', $methodArguments);
			foreach ($advices as $advice) {
				$advice->invoke($joinPoint);
			}
';
		}

		if (isset ($groupedAdvices['F3::FLOW3::AOP::AroundAdvice'])) {
			$advicesCode .= '
			$adviceChains = $this->AOPProxyGetAdviceChains(\'' . $methodName . '\');
			$adviceChain = $adviceChains[\'F3::FLOW3::AOP::AroundAdvice\'];
			$adviceChain->rewind();
			$result = $adviceChain->proceed(new F3::FLOW3::AOP::JoinPoint($this, \'' . $targetClass->getName() . '\', \'' . $methodName . '\', $methodArguments, $adviceChain));
			';
		} else {
			$advicesCode .= '
			$joinPoint = new F3::FLOW3::AOP::JoinPoint($this, \'' . $targetClass->getName() . '\', \'' . $methodName . '\', $methodArguments);
			$result = $this->AOPProxyInvokeJoinPoint($joinPoint);
';
		}

		if (isset ($groupedAdvices['F3::FLOW3::AOP::AfterReturningAdvice'])) {
			$advicesCode .= '
			$advices = $this->targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'F3::FLOW3::AOP::AfterReturningAdvice\'];
			$joinPoint = new F3::FLOW3::AOP::JoinPoint($this, \'' . $targetClass->getName() . '\', \'' . $methodName . '\', $methodArguments, NULL, $result);
			foreach ($advices as $advice) {
				$advice->invoke($joinPoint);
			}
';
		}

		if (isset ($groupedAdvices['F3::FLOW3::AOP::AfterThrowingAdvice'])) {
			$advicesCode .= '
		} catch (::Exception $exception) {
			$advices = $this->targetMethodsAndGroupedAdvices[\'' . $methodName . '\'][\'F3::FLOW3::AOP::AfterThrowingAdvice\'];
			$joinPoint = new F3::FLOW3::AOP::JoinPoint($this, \'' . $targetClass->getName() . '\', \'' . $methodName . '\', $methodArguments, NULL, NULL, $exception);
			foreach ($advices as $advice) {
				$advice->invoke($joinPoint);
			}
		}
';
		}
		return $advicesCode;
	}

	/**
	 * Builds code for the __wakeup() method to fetch a component factory, set
	 * up AOP internals and collect properties after reconstitution.
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	static protected function buildWakeupCode() {
		$wakeupCode = '
		$this->componentFactory = $GLOBALS[\'reconstituteComponentObject\'][\'componentFactory\'];
		$this->AOPProxyDeclareMethodsAndAdvices();
		foreach ($GLOBALS[\'reconstituteComponentObject\'][\'properties\'] as $property => $value) {
			$this->$property = $value;
		}';
		return $wakeupCode;
	}

}

?>