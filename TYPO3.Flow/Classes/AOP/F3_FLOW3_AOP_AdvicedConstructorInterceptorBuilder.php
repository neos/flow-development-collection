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
 * A method interceptor build for constructors with advice.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class AdvicedConstructorInterceptorBuilder extends \F3\FLOW3\AOP\AbstractMethodInterceptorBuilder {

	/**
	 * Builds interception PHP code for an adviced constructor
	 *
	 * @param string $methodName Name of the method to build an interceptor for
	 * @param array $interceptedMethods An array of method names and their meta information, including advices for the method (if any)
	 * @param string $targetClassName Name of the target class to build the interceptor for
	 * @param array
	 * @return string PHP code of the interceptor
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function build($methodName, array $interceptedMethods, $targetClassName) {
		if ($methodName !== '__construct') throw new \F3\FLOW3\AOP\Exception('The ' . __CLASS__ . ' can only build constructor interceptor code.', 1231789021);

		$declaringClassName = $interceptedMethods['__construct']['declaringClassName'];
		if (method_exists($declaringClassName, '__construct')) {
			$callParentCode = 'parent::__construct(' . $this->buildMethodParametersCode($declaringClassName, '__construct', FALSE) . ');';
		} else {
			$callParentCode = 'return;';
		}

		$interceptionCode = '
		if (isset($this->methodIsInAdviceMode[\'__construct\'])) {
			' . $callParentCode . '
		} else {
			$methodArguments = array(' . $this->buildMethodArgumentsArrayCode($declaringClassName, '__construct') . '	\'AOPProxyObjectManager\' => $AOPProxyObjectManager, \'AOPProxyObjectFactory\' => $AOPProxyObjectFactory
			);
			$this->methodIsInAdviceMode[\'__construct\'] = TRUE;
			' . $this->buildAdvicesCode($interceptedMethods['__construct']['groupedAdvices'], '__construct', $targetClassName) . '
			unset ($this->methodIsInAdviceMode[\'__construct\']);
		}
';
		$methodParametersDocumentation = '';
		$methodParametersCode = $this->buildMethodParametersCode($declaringClassName, '__construct', TRUE, $methodParametersDocumentation);
		$constructorCode = '
	/**
	 * Interceptor for the constructor __construct().
	 * ' . $methodParametersDocumentation . '
	 * @return mixed Result of the advice chain or the original method
	 */
	public function __construct(' . $methodParametersCode . (strlen($methodParametersCode) ? ', ' : '') . '\F3\FLOW3\Object\ManagerInterface $AOPProxyObjectManager, \F3\FLOW3\Object\FactoryInterface $AOPProxyObjectFactory) {
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