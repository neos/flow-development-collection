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
 * A AOP method interceptor code builder which generates an empty method as used
 * for introductions without advices delivering the implementation of the introduced
 * method.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:F3_FLOW3_AOP_EmptyMethodInterceptorBuilder.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_AOP_EmptyMethodInterceptorBuilder extends F3_FLOW3_AOP_AbstractMethodInterceptorBuilder {

	/**
	 * Builds PHP code for an empty method
	 *
	 * @param string $methodName Name of the method to build an interceptor for
	 * @param array $interceptedMethods An array of method names and their meta information, including advices for the method (if any)
	 * @param F3_FLOW3_Reflection_Class $targetClass A reflection of the target class to build the interceptor for
	 * @return string PHP code of the interceptor
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	static public function build($methodName, array $interceptedMethods, F3_FLOW3_Reflection_Class $targetClass) {
		if ($methodName === '__construct') throw new RuntimeException('The ' . __CLASS__ . ' cannot build constructor interceptor code.', 1173112554);

		$declaringClass = $interceptedMethods[$methodName]['declaringClass'];
		$method = ($declaringClass !== NULL && $declaringClass->hasMethod($methodName)) ? $declaringClass->getMethod($methodName) : NULL;

		$methodParametersDocumentation = '';
		$methodParametersCode = ($method !== NULL) ? self::buildMethodParametersCode($method, TRUE, $methodParametersDocumentation) : '';

		$staticKeyword = ($method !== NULL && $method->isStatic()) ? 'static ' : '';
		$declaringClassName = ($declaringClass !== NULL) ? $declaringClass->getName() : '[AOP proxy internals]';

		$emptyInterceptorCode = '
	/**
	 * Placeholder for the method ' . $methodName . '() declared in
	 * ' . $declaringClassName. '.
	 * ' . $methodParametersDocumentation . '
	 * @return void
	 */
	' . $staticKeyword . 'public function ' . $methodName . '(' . $methodParametersCode . ') {';
		if ($methodName == '__wakeup') {
			$emptyInterceptorCode .= self::buildWakeupCode();
			if ($targetClass->hasMethod('__wakeup')) {
				$emptyInterceptorCode .= "\n\t\tparent::__wakeup();\n";
			}
		}
		$emptyInterceptorCode .= '
	}
';
		return $emptyInterceptorCode;
	}

}

?>