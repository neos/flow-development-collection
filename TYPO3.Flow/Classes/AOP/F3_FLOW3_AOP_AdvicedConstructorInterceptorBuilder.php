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
 * A method interceptor build for constructors with advice.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:F3_FLOW3_AOP_AdvicedConstructorInterceptorBuilder.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_AOP_AdvicedConstructorInterceptorBuilder extends F3_FLOW3_AOP_AbstractConstructorInterceptorBuilder {

	/**
	 * Builds interception PHP code for a constructor with advice
	 *
	 * @param string $methodName: Name of the method to build an interceptor for
	 * @param array $interceptedMethods: An array of method names and their meta information, including advices for the method (if any)
	 * @param ReflectionClass $targetClass: A reflection of the target class to build the interceptor for
	 * @return string PHP code of the interceptor
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static public function build($methodName, array $interceptedMethods, ReflectionClass $targetClass) {
		$constructor = $targetClass->getConstructor();
		$methodsAndAdvicesArrayCode = self::buildMethodsAndAdvicesArrayCode($interceptedMethods);
		$callParentCode = ($constructor === NULL) ? 'return;' : 'parent::' . $constructor->getName() . '(' . self::buildMethodParametersCode($constructor, FALSE) . ');';

		$interceptionCode = '
		if (isset($this->methodIsInAdviceMode[\'' . $methodName . '\'])) {
			' . $callParentCode . '
		} else {
			$methodArguments = array(' . self::buildMethodArgumentsArrayCode($constructor) . '	\'AOPProxyComponentManager\' => $AOPProxyComponentManager
			);
			$this->methodIsInAdviceMode[\'' . $methodName . '\'] = TRUE;
			' . self::buildAdvicesCode($interceptedMethods[$methodName]['groupedAdvices'], $methodName, $targetClass) . '
			unset ($this->methodIsInAdviceMode[\'' . $methodName . '\']);
		}
		';
		$methodParametersDocumentation = '';
		$methodParametersCode = self::buildMethodParametersCode($constructor, TRUE, $methodParametersDocumentation);
		$constructorCode = '
	/**
	 * Interceptor for the constructor ' . $methodName . '().
	 * ' . $methodParametersDocumentation . '
	 * @return mixed			Result of the advice chain or the original method
	 */
	public function ' . $methodName . '(' . $methodParametersCode . (F3_PHP6_Functions::strlen($methodParametersCode) ? ', ' : '') . 'F3_FLOW3_Component_ManagerInterface $AOPProxyComponentManager) {
		$result = NULL;
		$this->componentManager = $AOPProxyComponentManager;
		' . $methodsAndAdvicesArrayCode . '
		' . $interceptionCode . '
		return $result;
	}
		';
		return $constructorCode;
	}

}
?>