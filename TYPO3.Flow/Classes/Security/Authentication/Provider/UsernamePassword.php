<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authentication\Provider;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 */

/**
 * An authentication provider that authenticates \F3\FLOW3\Security\Authentication\Token\UsernamePassword tokens.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class UsernamePassword implements \F3\FLOW3\Security\Authentication\ProviderInterface {

	/**
	 * @var \F3\FLOW3\Security\Authentication\EntryPointInterface The entry point for this provider
	 */
	protected $entryPoint = NULL;

	/**
	 * Returns TRUE if the given token can be authenticated by this provider
	 *
	 * @param \F3\FLOW3\Security\Authentication\TokenInterface $token The token that should be authenticated
	 * @return boolean TRUE if the given token class can be authenticated by this provider
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canAuthenticate(\F3\FLOW3\Security\Authentication\TokenInterface $token) {
		if ($token instanceof \F3\FLOW3\Security\Authentication\Token\UsernamePassword) return TRUE;
		return FALSE;
	}

	/**
	 * Returns the classnames of the tokens this provider is responsible for.
	 *
	 * @return string The classname of the token this provider is responsible for
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getTokenClassNames() {
		return array('F3\FLOW3\Security\Authentication\Token\UsernamePassword');
	}

	/**
	 * Sets isAuthenticated to TRUE for all tokens.
	 *
	 * @param \F3\FLOW3\Security\Authentication\TokenInterface $authenticationToken The token to be authenticated
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticate(\F3\FLOW3\Security\Authentication\TokenInterface $authenticationToken) {
		if (!($authenticationToken instanceof \F3\FLOW3\Security\Authentication\Token\UsernamePassword)) throw new \F3\FLOW3\Security\Exception\UnsupportedAuthenticationToken('This provider cannot authenticate the given token.', 1217339840);

		$credentials = $authenticationToken->getCredentials();
		if ($credentials['username'] === 'admin' && $credentials['password'] === 'password') $authenticationToken->setAuthenticationStatus(TRUE);
	}
}

?>