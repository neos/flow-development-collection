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
 * An authentication provider that authenticates F3_FLOW3_Security_Authentication_Token_UsernamePassword tokens.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_Authentication_Provider_UsernamePassword implements F3_FLOW3_Security_Authentication_ProviderInterface {

	/**
	 * @var F3_FLOW3_Security_Authentication_EntryPointInterface The entry point for this provider
	 */
	protected $entryPoint = NULL;

	/**
	 * Returns TRUE if the given token class can be authenticated by this provider
	 *
	 * @param string $className The class name of the token that should be authenticated
	 * @return boolean TRUE if the given token class can be authenticated by this provider
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canAuthenticate($className) {
		if($className == 'F3_FLOW3_Security_Authentication_Token_UsernamePassword') return TRUE;
		return FALSE;
	}

	/**
	 * Returns the classname of the token this provider is responsible for.
	 *
	 * @return string The classname of the token this provider is responsible for
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getTokenClassname() {
		return 'F3_FLOW3_Security_Authentication_Token_UsernamePassword';
	}

	/**
	 * Sets isAuthenticated to TRUE for all tokens.
	 *
	 * @param F3_FLOW3_Security_Authentication_TokenInterface $authenticationToken The token to be authenticated
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticate(F3_FLOW3_Security_Authentication_TokenInterface $authenticationToken) {
		if(!($authenticationToken instanceof F3_FLOW3_Security_Authentication_Token_UsernamePassword)) throw new F3_FLOW3_Security_Exception_UnsupportedAuthenticationToken('This provider cannot authenticate the given token.', 1217339840);

		$credentials = $authenticationToken->getCredentials();
		if($credentials['username'] === 'FLOW3' && $credentials['password'] === 'verysecurepassword') $authenticationToken->setAuthenticationStatus(TRUE);
	}
}

?>