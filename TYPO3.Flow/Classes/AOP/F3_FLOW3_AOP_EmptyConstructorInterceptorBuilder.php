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
 * An AOP constructor interceptor code builder for constructors without advice
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:F3_FLOW3_AOP_EmptyConstructorInterceptorBuilder.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_AOP_EmptyConstructorInterceptorBuilder extends F3_FLOW3_AOP_AbstractConstructorInterceptorBuilder {

	/**
	 * Builds interception PHP code for an empty constructor (ie. a constructor without advice)
	 *
	 * @param string $methodName: Name of the method to build an interceptor for
	 * @param array $interceptedMethods: An array of method names and their meta information, including advices for the method (if any)
	 * @param F3_FLOW3_Reflection_Class $targetClass: A reflection of the target class to build the interceptor for
	 * @return string PHP code of the interceptor
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static public function build($methodName, array $interceptedMethods, F3_FLOW3_Reflection_Class $targetClass) {
		$constructor = $targetClass->getConstructor();
		$methodsAndAdvicesArrayCode = self::buildMethodsAndAdvicesArrayCode($interceptedMethods);
		$callParentCode = ($constructor === NULL) ? '' : 'parent::' . $constructor->getName() . '(' . self::buildMethodParametersCode($constructor, FALSE) . ');';
		$parametersDocumentation = '';
		$parametersCode = ($constructor === NULL) ? '' : self::buildMethodParametersCode($constructor, TRUE, $parametersDocumentation);

		$constructorCode = '
	/**
	 * Non-advised constructor interceptor.
	 * ' . $parametersDocumentation . '
	 * @return void
	 */
	public function ' . $methodName . '(' . $parametersCode . (F3_PHP6_Functions::strlen($parametersCode) ? ', ' : '') . 'F3_FLOW3_Component_ManagerInterface $AOPProxyComponentManager) {
		$this->componentManager = $AOPProxyComponentManager;
		' . $methodsAndAdvicesArrayCode . '
		' . $callParentCode . '
	}
		';
		return $constructorCode;
	}
}

?>