<?php
declare(ENCODING = 'utf-8');

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
 * An AOP interceptor code builder for methods enriched by advices.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:F3_FLOW3_AOP_AdvicedMethodInterceptorBuilder.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_AOP_AdvicedMethodInterceptorBuilder extends F3_FLOW3_AOP_AbstractMethodInterceptorBuilder {

	/**
	 * Builds interception PHP code for an adviced method
	 *
	 * @param string $methodName Name of the method to build an interceptor for
	 * @param array $interceptedMethods An array of method names and their meta information, including advices for the method (if any)
	 * @param F3_FLOW3_Reflection_Class $targetClass A reflection of the target class to build the interceptor for
	 * @return string PHP code of the interceptor
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static public function build($methodName, array $interceptedMethods, F3_FLOW3_Reflection_Class $targetClass) {
		if ($methodName === self::getConstructorName($targetClass)) throw new F3_FLOW3_AOP_Exception('The ' . __CLASS__ . ' cannot build constructor interceptor code.', 1173107446);

		$groupedAdvices = $interceptedMethods[$methodName]['groupedAdvices'];
		$declaringClass = $interceptedMethods[$methodName]['declaringClass'];
		$method = ($declaringClass !== NULL) ? $declaringClass->getMethod($methodName) : NULL;

		$methodInterceptorCode = '';
		$advicesCode = self::buildAdvicesCode($groupedAdvices, $methodName, $targetClass);

		$methodParametersDocumentation = '';
		$methodParametersCode = self::buildMethodParametersCode($method, TRUE, $methodParametersDocumentation);

		$staticKeyword = ($method !== NULL && $method->isStatic()) ? 'static ' : '';

		$methodInterceptorCode .= '
	/**
	 * Interceptor for the method ' . $methodName . '().
	 * ' . $methodParametersDocumentation . '
	 * @return mixed Result of the advice chain or the original method
	 */
	' . $staticKeyword . 'public function ' . $methodName . '(' . $methodParametersCode . ') {
';
		if ($method !== NULL) {
			$methodInterceptorCode .= '
		if (isset($this->methodIsInAdviceMode[\'' . $methodName . '\'])) {
';

			if ($declaringClass->isInterface()) {
				$methodInterceptorCode .= '
			$result = NULL;
';
			} else {
				$methodInterceptorCode .= '
			$result = parent::' . $methodName . '(' . self::buildMethodParametersCode($method, FALSE) . ');
';
			}
			$methodInterceptorCode .= '
		} else {';
			if ($methodName == '__wakeup') {
				$methodInterceptorCode .= self::buildWakeupCode();
			}
			$methodInterceptorCode .= '
			$methodArguments = array(' . self::buildMethodArgumentsArrayCode($declaringClass->getMethod($methodName)) . ');
			$this->methodIsInAdviceMode[\'' . $methodName . '\'] = TRUE;
			' . $advicesCode . '
			unset ($this->methodIsInAdviceMode[\'' . $methodName . '\']);
		}
		return $result;
';
		} else {
			if ($methodName == '__wakeup') {
				$methodInterceptorCode .= self::buildWakeupCode();
				if ($targetClass->hasMethod('__wakeup')) {
					$methodInterceptorCode .= "\n\t\tparent::__wakeup();\n";
				}
			}
		}
		$methodInterceptorCode .= '
	}
';
		return $methodInterceptorCode;
	}
}

?>