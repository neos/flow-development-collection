<?php
namespace TYPO3\FLOW3\Security\Authorization\Interceptor;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
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
 */
class AfterInvocation implements \TYPO3\FLOW3\Security\Authorization\InterceptorInterface {

	/**
	 * @var \TYPO3\FLOW3\Security\Authorization\AfterInvocationManagerInterface
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
	 * @param \TYPO3\FLOW3\Security\Context $securityContext The current security context
	 * @param \TYPO3\FLOW3\Security\Authorization\AfterInvocationManagerInterface $afterInvocationManager The after invocation manager
	 * @return void
	 */
	public function __construct(
		\TYPO3\FLOW3\Security\Context $securityContext,
		\TYPO3\FLOW3\Security\Authorization\AfterInvocationManagerInterface $afterInvocationManager
		) {

	}

	/**
	 * Sets the current joinpoint for this interception
	 *
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return void
	 */
	public function setJoinPoint(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {

	}

	/**
	 * Sets the result (return object) of the intercepted method
	 *
	 * @param mixed $result The result of the intercepted method
	 * @return void
	 */
	public function setResult($result) {
		$this->result = $result;
	}

	/**
	 * Invokes the security interception
	 *
	 * @return boolean TRUE if the security checks was passed
	 * @todo Implement interception logic
	 */
	public function invoke() {
		return $this->result;
	}
}

?>