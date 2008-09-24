<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Security::Authorization::Interceptor;

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
 * @subpackage Security
 * @version $Id:$
 */

/**
 * This is the second main security interceptor, which enforces the current security policy for return values and is usually applied over AOP:
 *
 * 1. We call the AfterInvocationManager with the method's return value as paramter
 * 2. If we had a "run as" support, we would have to reset the security context
 * 3. If a PermissionDeniedException was thrown we look for any an authentication entry point in the active tokens to redirect to authentication
 * 4. Then the value is returned to the caller
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class AfterInvocation implements F3::FLOW3::Security::Authorization::InterceptorInterface {

	/**
	 * @var F3_FLOW3_Security_Authorization_AfterInvocationManagerInterface The after invocation manager
	 */
	protected $afterInvocationManager = NULL;

	/**
	 * Constructor.
	 *
	 * @param F3_FLOW3_Security_ContextHolderInterface $securityContextHolder The current security context
	 * @param F3_FLOW3_Security_Authorization_AfterInvocationManagerInterface $afterInvocationManager The after invocation manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(
					F3_FLOW3_Security_ContextHolderInterface $securityContextHolder,
					F3_FLOW3_Security_Authorization_AfterInvocationManagerInterface $afterInvocationManager
					) {

	}

	/**
	 * Sets the current joinpoint for this interception
	 *
	 * @param F3_FLOW3_AOP_JoinPointInterface $joinPoint The current joinpoint
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setJoinPoint(F3_FLOW3_AOP_JoinPointInterface $joinPoint) {

	}

	/**
	 * Sets the result (return object) of the intercepted method
	 *
	 * @param mixed The result of the intercepted method
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setResult($result) {

	}

	/**
	 * Invokes the security interception
	 *
	 * @return boolean TRUE if the security checks was passed
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function invoke() {

	}
}

?>