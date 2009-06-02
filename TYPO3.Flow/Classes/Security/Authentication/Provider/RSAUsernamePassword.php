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
 * An authentication provider that authenticates \F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword tokens.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class RSAUsernamePassword implements \F3\FLOW3\Security\Authentication\ProviderInterface {

	/**
	 * The RSAWalletService
	 * @var \F3\FLOW3\Security\Cryptography\RSAWalletServiceInterface
	 */
	protected $RSAWalletService;

	/**
	 * Inject the RSAWAlletService, used to decrypt the username
	 *
	 * @param \F3\FLOW3\Security\Cryptography\RSAWalletServiceInterface $RSAWalletService The RSAWalletService
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @internal
	 */
	public function injectRSAWalletService(\F3\FLOW3\Security\Cryptography\RSAWalletServiceInterface $RSAWalletService) {
		$this->RSAWalletService = $RSAWalletService;
	}

	/**
	 * Returns TRUE if the given token can be authenticated by this provider
	 *
	 * @param \F3\FLOW3\Security\Authentication\TokenInterface $token The token that should be authenticated
	 * @return boolean TRUE if the given token class can be authenticated by this provider
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @internal
	 */
	public function canAuthenticate(\F3\FLOW3\Security\Authentication\TokenInterface $token) {
		if ($token instanceof \F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword) return TRUE;
		return FALSE;
	}

	/**
	 * Returns the classnames of the tokens this provider is responsible for.
	 *
	 * @return array The classname of the token this provider is responsible for
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @internal
	 */
	public function getTokenClassNames() {
		return array('F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword');
	}

	/**
	 * Sets isAuthenticated to TRUE for all tokens.
	 *
	 * @param \F3\FLOW3\Security\Authentication\TokenInterface $authenticationToken The token to be authenticated
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @internal
	 */
	public function authenticate(\F3\FLOW3\Security\Authentication\TokenInterface $authenticationToken) {
		if (!($authenticationToken instanceof \F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword)) throw new \F3\FLOW3\Security\Exception\UnsupportedAuthenticationToken('This provider cannot authenticate the given token.', 1217339840);

		$credentials = $authenticationToken->getCredentials();

		if ($credentials['encryptedUsername'] !== '' && $credentials['encryptedPassword'] !== '') {

			$passwordKeypairUUID = $authenticationToken->getPasswordKeypairUUID();
			$usernameKeypairUUID = $authenticationToken->getUsernameKeypairUUID();

			if ($usernameKeypairUUID !== NULL && $passwordKeypairUUID !== NULL) {

				$username = $this->RSAWalletService->decrypt(base64_decode($credentials['encryptedUsername']), $usernameKeypairUUID);

				if ($username === 'admin' && $this->RSAWalletService->checkRSAEncryptedPassword(base64_decode($credentials['encryptedPassword']), 'af1e8a52451786a6b3bf78838e03a0a2', 'a709157e66e0197cafa0c2ba99f6e252', $passwordKeypairUUID)) {
					$authenticationToken->setAuthenticationStatus(\F3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL);
				} else {
					$authenticationToken->setAuthenticationStatus(\F3\FLOW3\Security\Authentication\TokenInterface::WRONG_CREDENTIALS);
				}

				$authenticationToken->invalidateCurrentKeypairs();
			}
		} elseif ($authenticationToken->getAuthenticationStatus() !== \F3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL) {
			$authenticationToken->setAuthenticationStatus(\F3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN);
		}
	}
}

?>