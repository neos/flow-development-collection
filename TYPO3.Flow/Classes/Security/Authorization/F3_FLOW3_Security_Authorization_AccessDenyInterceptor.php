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
 * This security interceptor always denys access.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_Authorization_AccessDenyInterceptor implements F3_FLOW3_Security_Authorization_InterceptorInterface {

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
	 * Invokes nothing, always throws an AccessDenied Exception.
	 *
	 * @return boolean Always returns FALSE
	 * @throws F3_FLOW3_Security_Exception_AccessDenied
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function invoke() {

	}
}

?>