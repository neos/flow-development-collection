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
 * An AOP interceptor code builder for methods enriched by advices.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AdvicedMethodInterceptorBuilder extends \F3\FLOW3\AOP\Builder\AbstractMethodInterceptorBuilder {

	/**
	 * Builds interception PHP code for an adviced method
	 *
	 * @param string $methodName Name of the method to build an interceptor for
	 * @param array $interceptedMethods An array of method names and their meta information, including advices for the method (if any)
	 * @param string $targetClassName Name of the target class to build the interceptor for
	 * @param array
	 * @return string PHP code of the interceptor
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function build($methodName, array $interceptedMethods, $targetClassName) {
		if ($methodName === '__construct') throw new \F3\FLOW3\AOP\Exception('The ' . __CLASS__ . ' cannot build constructor interceptor code.', 1173107446);

		$groupedAdvices = $interceptedMethods[$methodName]['groupedAdvices'];
		$declaringClassName = $interceptedMethods[$methodName]['declaringClassName'];

		$methodInterceptorCode = '';
		$advicesCode = $this->buildAdvicesCode($groupedAdvices, $methodName, $targetClassName);

		$methodDocumentation = $this->buildMethodDocumentation($declaringClassName, $methodName);
		$methodParametersCode = $this->buildMethodParametersCode($declaringClassName, $methodName, TRUE);

		$staticKeyword = $this->reflectionService->isMethodStatic($declaringClassName, $methodName) ? 'static ' : '';

		$methodInterceptorCode .= '
	/**
	 * Interceptor for the method ' . $methodName . '().
	 * ' . $methodDocumentation . '
	 * @return mixed Result of the advice chain or the original method (see earlier @return annotation)
	 */
	' . $staticKeyword . 'public function ' . $methodName . '(' . $methodParametersCode . ') {
';
		if ($methodName !== NULL || $methodName === '__wakeup') {
			$methodInterceptorCode .= '
		if (isset($this->methodIsInAdviceMode[\'' . $methodName . '\'])) {
';

			if ($declaringClassName === NULL || interface_exists($declaringClassName, TRUE)) {
				$methodInterceptorCode .= '
			$result = NULL;
';
			} else {
				$methodInterceptorCode .= '
			$result = parent::' . $methodName . '(' . $this->buildMethodParametersCode($declaringClassName, $methodName, FALSE) . ');
';
			}
			$methodInterceptorCode .= '
		} else {';
			$methodInterceptorCode .= '
			$methodArguments = array(' . $this->buildMethodArgumentsArrayCode($declaringClassName, $methodName) . ');
			$this->methodIsInAdviceMode[\'' . $methodName . '\'] = TRUE;
			' . $advicesCode . '
			unset ($this->methodIsInAdviceMode[\'' . $methodName . '\']);
		}
		return $result;
';
		}
		$methodInterceptorCode .= '
	}
';
		return $methodInterceptorCode;
	}
}

?>