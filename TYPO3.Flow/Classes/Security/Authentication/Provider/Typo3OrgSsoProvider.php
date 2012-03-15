<?php
namespace TYPO3\FLOW3\Security\Authentication\Provider;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * An authentication provider that authenticates SSO requests from typo3.org
 */
class Typo3OrgSsoProvider extends \TYPO3\FLOW3\Security\Authentication\Provider\AbstractProvider {

	/**
	 * @var \TYPO3\FLOW3\Security\AccountRepository
	 * @FLOW3\Inject
	 */
	protected $accountRepository;

	/**
	 * @var \TYPO3\FLOW3\Security\Cryptography\RsaWalletServiceInterface
	 * @FLOW3\Inject
	 */
	protected $rsaWalletService;

	/**
	 * Returns the class names of the tokens this provider can authenticate.
	 *
	 * @return array
	 */
	public function getTokenClassNames() {
		return array('TYPO3\FLOW3\Security\Authentication\Token\Typo3OrgSsoToken');
	}

	/**
	 * Sets isAuthenticated to TRUE for all tokens.
	 *
	 * @param \TYPO3\FLOW3\Security\Authentication\TokenInterface $authenticationToken The token to be authenticated
	 * @return void
	 * @throws \TYPO3\FLOW3\Security\Exception\UnsupportedAuthenticationTokenException
	 */
	public function authenticate(\TYPO3\FLOW3\Security\Authentication\TokenInterface $authenticationToken) {
		if (!($authenticationToken instanceof \TYPO3\FLOW3\Security\Authentication\Token\Typo3OrgSsoToken)) {
			throw new \TYPO3\FLOW3\Security\Exception\UnsupportedAuthenticationTokenException('This provider cannot authenticate the given token.', 1217339840);
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

				$authenticationToken->setAuthenticationStatus(\TYPO3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL);
				$authenticationToken->setAccount($account);
			} else {
				$authenticationToken->setAuthenticationStatus(\TYPO3\FLOW3\Security\Authentication\TokenInterface::WRONG_CREDENTIALS);
			}
		} elseif ($authenticationToken->getAuthenticationStatus() !== \TYPO3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL) {
			$authenticationToken->setAuthenticationStatus(\TYPO3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN);
		}
	}

}
?>