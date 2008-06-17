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
 * Default Firewall which analyzes the request with a RequestFilter chain.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_Authorization_FilterFirewall implements F3_FLOW3_Security_Authorization_FirewallInterface {

//TODO: This array hast to be configured/filled by configuration
	/**
	 * @var array Array of F3_FLOW3_Security_RequestFilter objects
	 */
	protected $filters = array();

//TODO: This has to be set by configuration
	/**
	 * @var boolean If set to TRUE the firewall will reject any request except the ones explicitly whitelisted by a F3_FLOW3_Security_Authorization_AccessGrantInterceptor
	 */
	protected $rejectAll = FALSE;

	/**
	 * Analyzes a request by passing it to the registered RequestFilters
	 *
	 * @param F3_FLOW3_MVC_Request $request The request to be analyzed
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function analyzeRequest(F3_FLOW3_MVC_Request $request) {
		//foreach filters: filter->filterRequest($request)
		//If no filter matched and $rejectAll == TRUE -> access denied
	}
}

?>