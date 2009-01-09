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
 * An AOP constructor interceptor code builder for constructors without advice
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class EmptyConstructorInterceptorBuilder extends \F3\FLOW3\AOP\AbstractMethodInterceptorBuilder {

	/**
	 * Builds interception PHP code for an empty constructor (ie. a constructor without advice)
	 *
	 * @param string $methodName: Name of the method to build an interceptor for
	 * @param array $interceptedMethods: An array of method names and their meta information, including advices for the method (if any)
	 * @param \F3\FLOW3\Reflection\ClassReflection $targetClass: A reflection of the target class to build the interceptor for
	 * @return string PHP code of the interceptor
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	static public function build($methodName, array $interceptedMethods, \F3\FLOW3\Reflection\ClassReflection $targetClass) {
		$constructor = $targetClass->getConstructor();
		$callParentCode = ($constructor === NULL) ? '' : 'parent::__construct(' . self::buildMethodParametersCode($constructor, FALSE) . ');';
		$parametersDocumentation = '';
		$parametersCode = ($constructor === NULL) ? '' : self::buildMethodParametersCode($constructor, TRUE, $parametersDocumentation);

		$constructorCode = '
	/**
	 * Non-advised constructor interceptor.
	 * ' . $parametersDocumentation . '
	 * @return void
	 */
	public function ' . $methodName . '(' . $parametersCode . (\F3\PHP6\Functions::strlen($parametersCode) ? ', ' : '') . '\F3\FLOW3\Object\ManagerInterface $AOPProxyObjectManager, \F3\FLOW3\Object\FactoryInterface $AOPProxyObjectFactory) {
		$this->objectManager = $AOPProxyObjectManager;
		$this->objectFactory = $AOPProxyObjectFactory;
		$this->AOPProxyDeclareMethodsAndAdvices();
		' . $callParentCode . '
	}
';
		return $constructorCode;
	}
}

?>