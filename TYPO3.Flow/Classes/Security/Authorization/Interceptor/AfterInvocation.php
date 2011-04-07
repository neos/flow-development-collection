<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authorization\Interceptor;

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
 * This is the second main security interceptor, which enforces the current security policy for return values and is usually applied over AOP:
 *
 * 1. We call the AfterInvocationManager with the method's return value as paramter
 * 2. If we had a "run as" support, we would have to reset the security context
 * 3. If a PermissionDeniedException was thrown we look for any an authentication entry point in the active tokens to redirect to authentication
 * 4. Then the value is returned to the caller
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class AfterInvocation implements \F3\FLOW3\Security\Authorization\InterceptorInterface {

	/**
	 * @var \F3\FLOW3\Security\Authorization\AfterInvocationManagerInterface
	 */
	protected $afterInvocationManager = NULL;

	/**
	 * Result of the (probably intercepted) target method
	 * @var mixed
	 */
	protected $result;

	/**
	 * Constructor.
	 *
	 * @param \F3\FLOW3\Security\Context $securityContext The current security context
	 * @param \F3\FLOW3\Security\Authorization\AfterInvocationManagerInterface $afterInvocationManager The after invocation manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(
		\F3\FLOW3\Security\Context $securityContext,
		\F3\FLOW3\Security\Authorization\AfterInvocationManagerInterface $afterInvocationManager
		) {

	}

	/**
	 * Sets the current joinpoint for this interception
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current joinpoint
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setJoinPoint(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {

	}

	/**
	 * Sets the result (return object) of the intercepted method
	 *
	 * @param mixed $result The result of the intercepted method
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setResult($result) {
		$this->result = $result;
	}

	/**
	 * Invokes the security interception
	 *
	 * @return boolean TRUE if the security checks was passed
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo Implement interception logic
	 */
	public function invoke() {
		return $this->result;
	}
}

?>