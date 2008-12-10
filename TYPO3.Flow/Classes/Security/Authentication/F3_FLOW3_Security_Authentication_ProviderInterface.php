<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authentication;

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
 * @version $Id$
 */

/**
 * Contract for an authentication provider used by the \F3\FLOW3\Security\Authenticaton\ProviderManager.
 * Has to add a \F3\FLOW3\Security\Authentication\TokenInterface to the securit context, which contains
 * a \F3\FLOW3\Security\Authentication\UserDetailsInterface.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface ProviderInterface {

	/**
	 * Returns TRUE if the given token can be authenticated by this provider
	 *
	 * @param \F3\FLOW3\Security\Authentication\TokenInterface $token The token that should be authenticated
	 * @return boolean TRUE if the given token class can be authenticated by this provider
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canAuthenticate(\F3\FLOW3\Security\Authentication\TokenInterface $token);

	/**
	 * Returns the classname of the token this provider is responsible for.
	 *
	 * @return string The classname of the token this provider is responsible for
	 */
	public function getTokenClassname();

	/**
	 * Tries to authenticate the given token. Sets isAuthenticated to TRUE if authentication succeeded.
	 *
	 * @param \F3\FLOW3\Security\Authentication\TokenInterface $authenticationToken The token to be authenticated
	 * @return void
	 */
	public function authenticate(\F3\FLOW3\Security\Authentication\TokenInterface $authenticationToken);
}

?>