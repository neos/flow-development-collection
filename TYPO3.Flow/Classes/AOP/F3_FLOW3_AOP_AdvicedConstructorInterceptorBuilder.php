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
 * A method interceptor build for constructors with advice.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:F3::FLOW3::AOP::AdvicedConstructorInterceptorBuilder.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class AdvicedConstructorInterceptorBuilder extends F3::FLOW3::AOP::AbstractMethodInterceptorBuilder {

	/**
	 * Builds interception PHP code for a constructor with advice
	 *
	 * @param string $methodName: Name of the method to build an interceptor for
	 * @param array $interceptedMethods: An array of method names and their meta information, including advices for the method (if any)
	 * @param F3::FLOW3::Reflection::ClassReflection $targetClass: A reflection of the target class to build the interceptor for
	 * @return string PHP code of the interceptor
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	static public function build($methodName, array $interceptedMethods, F3::FLOW3::Reflection::ClassReflection $targetClass) {
		$constructor = $targetClass->getConstructor();
		$callParentCode = ($constructor === NULL) ? 'return;' : 'parent::__construct(' . self::buildMethodParametersCode($constructor, FALSE) . ');';

		$interceptionCode = '
		if (isset($this->methodIsInAdviceMode[\'__construct\'])) {
			' . $callParentCode . '
		} else {
			$methodArguments = array(' . self::buildMethodArgumentsArrayCode($constructor) . '	\'AOPProxyObjectManager\' => $AOPProxyObjectManager, \'AOPProxyObjectFactory\' => $AOPProxyObjectFactory
			);
			$this->methodIsInAdviceMode[\'__construct\'] = TRUE;
			' . self::buildAdvicesCode($interceptedMethods['__construct']['groupedAdvices'], '__construct', $targetClass) . '
			unset ($this->methodIsInAdviceMode[\'__construct\']);
		}
';
		$methodParametersDocumentation = '';
		$methodParametersCode = self::buildMethodParametersCode($constructor, TRUE, $methodParametersDocumentation);
		$constructorCode = '
	/**
	 * Interceptor for the constructor __construct().
	 * ' . $methodParametersDocumentation . '
	 * @return mixed Result of the advice chain or the original method
	 */
	public function __construct(' . $methodParametersCode . (F3::PHP6::Functions::strlen($methodParametersCode) ? ', ' : '') . 'F3::FLOW3::Object::ManagerInterface $AOPProxyObjectManager, F3::FLOW3::Object::FactoryInterface $AOPProxyObjectFactory) {
		$this->objectManager = $AOPProxyObjectManager;
		$this->objectFactory = $AOPProxyObjectFactory;
		$result = NULL;
		$this->AOPProxyDeclareMethodsAndAdvices();
		' . $interceptionCode . '
		return $result;
	}
';
		return $constructorCode;
	}

}
?>