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
 * 1. Checks all authentication tokens in the security context if isAuthenticated() returns TRUE
 * 1.1. If not it calls the configured authentication managers to authenticate their tokens
 * 2. Then the configured AccessDecisionManager is called to authorize the request/action
 * 3. If we have something like a "run as" functionality in the future, it will be invoked at this point (for now we don't have something like that)
 * 4. If no exception has been thrown we pass over the controll to the requested resource (i.e. a secured method)
 * 5. Right before the method returns we call any configured AfterInvocationManager with the method's return value as paramter
 * 6. If we had a "run as" support, we would have to reset the security context
 * 7. Then the value is returned to the caller
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_Authorization_PolicyEnforcementInterceptor implements F3_FLOW3_Security_Authorization_InterceptorInterface {

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