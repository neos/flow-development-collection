<?php
namespace TYPO3\Flow\Security\Authentication\Provider;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * An authentication provider that authenticates SSO requests from typo3.org
 */
class Typo3OrgSsoProvider extends \TYPO3\Flow\Security\Authentication\Provider\AbstractProvider {

	/**
	 * @var \TYPO3\Flow\Security\AccountRepository
	 * @Flow\Inject
	 */
	protected $accountRepository;

	/**
	 * @var \TYPO3\Flow\Security\Cryptography\RsaWalletServiceInterface
	 * @Flow\Inject
	 */
	protected $rsaWalletService;

	/**
	 * Returns the class names of the tokens this provider can authenticate.
	 *
	 * @return array
	 */
	public function getTokenClassNames() {
		return array('TYPO3\Flow\Security\Authentication\Token\Typo3OrgSsoToken');
	}

	/**
	 * Sets isAuthenticated to TRUE for all tokens.
	 *
	 * @param \TYPO3\Flow\Security\Authentication\TokenInterface $authenticationToken The token to be authenticated
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception\UnsupportedAuthenticationTokenException
	 * @Flow\Session(autoStart=true)
	 */
	public function authenticate(\TYPO3\Flow\Security\Authentication\TokenInterface $authenticationToken) {
		if (!($authenticationToken instanceof \TYPO3\Flow\Security\Authentication\Token\Typo3OrgSsoToken)) {
			throw new \TYPO3\Flow\Security\Exception\UnsupportedAuthenticationTokenException('This provider cannot authenticate the given token.', 1217339840);
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

				$authenticationToken->setAuthenticationStatus(\TYPO3\Flow\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL);
				$authenticationToken->setAccount($account);
			} else {
				$authenticationToken->setAuthenticationStatus(\TYPO3\Flow\Security\Authentication\TokenInterface::WRONG_CREDENTIALS);
			}
		} elseif ($authenticationToken->getAuthenticationStatus() !== \TYPO3\Flow\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL) {
			$authenticationToken->setAuthenticationStatus(\TYPO3\Flow\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN);
		}
	}

}
?>
