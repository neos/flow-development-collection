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
 * @version $Id: $
 */

/**
 * A AOP method interceptor code builder which generates an empty method as used
 * for introductions without advices delivering the implementation of the introduced
 * method.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:T3_FLOW3_AOP_EmptyMethodInterceptorBuilder.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_AOP_EmptyMethodInterceptorBuilder extends T3_FLOW3_AOP_AbstractMethodInterceptorBuilder {

	/**
	 * Builds PHP code for an empty method
	 *
	 * @param string $methodName: Name of the method to build an interceptor for
	 * @param array $interceptedMethods: An array of method names and their meta information, including advices for the method (if any)
	 * @param ReflectionClass $targetClass: A reflection of the target class to build the interceptor for
	 * @return string PHP code of the interceptor
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static public function build($methodName, array $interceptedMethods, ReflectionClass $targetClass) {
		if ($methodName === self::getConstructorName($targetClass)) throw new RuntimeException('The ' . __CLASS__ . ' cannot build constructor interceptor code.', 1173112554);

		$declaringClass = $interceptedMethods[$methodName]['declaringClass'];
		$method = ($declaringClass !== NULL) ? $declaringClass->getMethod($methodName) : NULL;

		$methodParametersDocumentation = '';
		$methodParametersCode = self::buildMethodParametersCode($method, TRUE, $methodParametersDocumentation);

		$staticKeyword = ($method !== NULL && $method->isStatic()) ? 'static ' : '';

		$emptyInterceptorCode = '
	/**
	 * Placeholder for the method ' . $methodName . '() declared in
	 * ' . $declaringClass->getName() . '.
	 * ' . $methodParametersDocumentation . '
	 * @return void
	 */
	' . $staticKeyword . 'public function ' . $methodName . '(' . $methodParametersCode . ') {
	}
		';
		return $emptyInterceptorCode;
	}

}