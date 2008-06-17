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
 * A RequestFilter is configured to match specific F3_FLOW3_MVC_Requests and call
 * a F3_FLOW3_Security_Authorization_InterceptorInterface if needed.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_Authorization_RequestFilter {

//TODO: This has to be configured/set by configuration
	/**
	 * @var F3_FLOW3_Security_RequestPattern The request pattern this filter should match
	 */
	protected $pattern = NULL;

//TODO: this has to be set by configuration
	/**
	 * @var F3_FLOW3_Security_Authorization_InterceptorInterface
	 */
	protected $securityInterceptor = NULL;

	/**
	 * Tries to match the given request against this filter and calls the set security interceptor on success.
	 *
	 * @param F3_FLOW3_MVC_Request $request The request to be matched
	 * @return boolean Returns TRUE if the filter matched, FALSE otherwise
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function filterRequest(F3_FLOW3_MVC_Request $request) {
		//$securityInterceptor->invoke();
	}
}

?>