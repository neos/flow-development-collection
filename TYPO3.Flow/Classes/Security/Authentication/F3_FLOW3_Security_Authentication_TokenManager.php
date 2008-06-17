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
 * The default authentication manager, which uses different F3_FLOW3_Security_Authentication_Providers
 * to authenticate the tokens stored in the security context.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_Authentication_TokenManager implements F3_FLOW3_Security_Authentication_ManagerInterface {

//TODO: this has to be set/filled by configuration
	/**
	 * @var array Array of F3_FLOW3_Security_Authentication_ProviderInterface objects
	 */
	protected $providers = array();

	/**
	 * Constructor.
	 *
	 * @param F3_FLOW3_Security_ContextHolderInterface $securityContextHolder The global security context holder
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(F3_FLOW3_Security_ContextHolderInterface $securityContextHolder) {

	}

	/**
	 * Tries to authenticate all tokens in the security context with the available authentication providers
	 *
	 * @return F3_FLOW3_Security_Authentication_TokenInterface The authenticated token, NULL if authentication failed
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticate() {
		//foreach providers: if($provider->canAuthenticate()) $provider->authenticate();
	}
}

?>