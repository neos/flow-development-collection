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
 * @subpackage Security
 * @version $Id:$
 */

/**
 * This is the main security interceptor, which enforces the current security policy and is usually applied over AOP:
 *
 * 1. Checks the authentication tokens in the security context (in the given order) if isAuthenticated() returns TRUE.
 *    If context->authenticateAllTokens() returns TRUE all tokens have be authenticated, otherwise there has to be at least one
 *    authenticated token to have a valid authentication.
 * 1.1. If there is no valid authentication the configured authentication manager is called to authenticate its tokens
 *      If there hast to be only one authenticated token, authentication stops after the first successfully authenticated token.
 * 2. If we have something like a "run as" functionality in the future, it will be invoked at this point (for now we don't have something like that)
 * 3. Then the configured AccessDecisionManager is called to authorize the request/action
 * 4. If no exception has been thrown we pass over the controll to the requested resource (i.e. a secured method)
 * 5. Right before the method returns we call the AfterInvocationManager with the method's return value as paramter
 * 6. If we had a "run as" support, we would have to reset the security context
 * 7. If a PermissionDeniedException was thrown we look for any an authentication entry point in the active tokens to redirect to authentication
 * 8. Then the value is returned to the caller
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_Authorization_Interceptor_PolicyEnforcement implements F3_FLOW3_Security_Authorization_InterceptorInterface {

	/**
	 * @var F3_FLOW3_Secuirty_Authentication_ManagerInterface The authentication manager
	 */
	protected $authenticationManagers = NULL;

//TODO: This has to be filled/configured by configuration
	/**
	 * @var array Array of F3_FLOW3_Secuirty_Authorization_AccessDecisionManagerInterface objects
	 */
	protected $accessDecisionManagers = array();

	/**
	 * @var F3_FLOW3_Security_Authorization_AfterInvocationManagerInterface The after invocation manager
	 */
	protected $afterInvocationManager = NULL;

	/**
	 * Constructor.
	 *
	 * @param F3_FLOW3_Security_Context $securityContext The current security context
	 * @param F3_FLOW3_Security_Authentication_ManagerInterface $authenticationManager The authentication Manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(
					F3_FLOW3_Security_Context $securityContext,
					F3_FLOW3_Security_Authentication_ManagerInterface $authenticationManager
					) {

	}

	/**
	 * Sets the current joinpoint for this interception
	 *
	 * @param F3_FLOW3_AOP_JoinPoint $joinPoint The current joinpoint
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setJoinPoint(F3_FLOW3_AOP_JoinPoint $joinPoint) {

	}

	/**
	 * Invokes the security interception
	 *
	 * @return boolean TRUE if the security checks was passed
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function invoke() {

	}

	/**
	 * Injects the after invocation manager
	 *
	 * @param F3_FLOW3_Security_Authorization_AfterInvocationManagerInterface $afterInvocationManager The after invocation manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectAfterInvocationManager(F3_FLOW3_Security_Authorization_AfterInvocationManagerInterface $afterInvocationManager) {

	}
}

?>