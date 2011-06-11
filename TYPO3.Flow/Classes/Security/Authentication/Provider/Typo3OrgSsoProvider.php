<?php
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
 * An authentication provider that authenticates SSO requests from typo3.org
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Typo3OrgSsoProvider implements \F3\FLOW3\Security\Authentication\AuthenticationProviderInterface {

	/**
	 * @var \F3\FLOW3\Security\AccountRepository
	 * @inject
	 */
	protected $accountRepository;

	/**
	 * @var \F3\FLOW3\Security\Cryptography\RsaWalletServiceInterface
	 * @inject
	 */
	protected $rsaWalletService;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * Constructor
	 *
	 * @param string $name The name of this authentication provider
	 * @param array $options Additional configuration options
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct($name, array $options) {
		$this->name = $name;
		$this->options = $options;
	}

	/**
	 * Returns TRUE if the given token can be authenticated by this provider
	 *
	 * @param F3\FLOW3\Security\Authentication\TokenInterface $authenticationToken The token that should be authenticated
	 * @return boolean TRUE if the given token class can be authenticated by this provider
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canAuthenticate(\F3\FLOW3\Security\Authentication\TokenInterface $authenticationToken) {
		if ($authenticationToken->getAuthenticationProviderName() === $this->name) return TRUE;
		return FALSE;
	}

	/**
	 * Returns the classnames of the tokens this provider is responsible for.
	 *
	 * @return string The classname of the token this provider is responsible for
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getTokenClassNames() {
		return array('F3\FLOW3\Security\Authentication\Token\Typo3OrgSsoToken');
	}

	/**
	 * Sets isAuthenticated to TRUE for all tokens.
	 *
	 * @param F3\FLOW3\Security\Authentication\TokenInterface $authenticationToken The token to be authenticated
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticate(\F3\FLOW3\Security\Authentication\TokenInterface $authenticationToken) {
		if (!($authenticationToken instanceof \F3\FLOW3\Security\Authentication\Token\Typo3OrgSsoToken)) {
			throw new \F3\FLOW3\Security\Exception\UnsupportedAuthenticationTokenException('This provider cannot authenticate the given token.', 1217339840);
		}

		$account = NULL;
		$credentials = $authenticationToken->getCredentials();

		if (is_array($credentials) && isset($credentials['username'])) {
			$account = $this->accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName($credentials['username'], $this->name);
		}

		if (is_object($account)) {
			$authenticationData = 'version=' . $credentials['version'] .
									'&user=' . $credentials['username'] .
									'&tpa_id=' . $credentials['tpaId'] .
									'&expires=' . $credentials['expires'] .
									'&action=' . $credentials['action'] .
									'&flags=' . $credentials['flags'] .
									'&userdata=' . $credentials['userdata'];

			if ($this->rsaWalletService->verifySignature($authenticationData, $credentials['signature'], $this->options['rsaKeyUuid'])
				&& $credentials['expires'] > time()) {

				$authenticationToken->setAuthenticationStatus(\F3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL);
				$authenticationToken->setAccount($account);
			} else {
				$authenticationToken->setAuthenticationStatus(\F3\FLOW3\Security\Authentication\TokenInterface::WRONG_CREDENTIALS);
			}
		} elseif ($authenticationToken->getAuthenticationStatus() !== \F3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL) {
			$authenticationToken->setAuthenticationStatus(\F3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN);
		}
	}
}

?>
