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
 * This security interceptor invokes the authentication of the authentication tokens in the security context.
 * It is usally used by the firewall to define secured request that need proper authentication.
 *
 * Checks the authentication tokens in the security context (in the given order) if isAuthenticated() returns TRUE.
 * If context->authenticateAllTokens() returns TRUE all tokens have be authenticated, otherwise there has to be at least one
 * authenticated token to have a valid authentication.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_Authorization_RequireAuthenticationInterceptor implements F3_FLOW3_Security_Authorization_InterceptorInterface {

	/**
	 * Constructor.
	 *
	 * @param F3_FLOW3_Security_Context $securityContext The current security context
	 * @param F3_FLOW3_Security_Authentication_ManagerInterface $authenticationManager The authentication Manager
	 * @param F3_Log_LoggerInterface $logger A logger to log security relevant actions
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(
					F3_FLOW3_Security_Context $securityContext,
					F3_FLOW3_Security_Authentication_ManagerInterface $authenticationManager,
					F3_Log_LoggerInterface $logger
					) {

	}

	/**
	 * Invokes the the authentication, if needed.
	 *
	 * @return boolean TRUE if the security checks was passed
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function invoke() {

	}
}

?>