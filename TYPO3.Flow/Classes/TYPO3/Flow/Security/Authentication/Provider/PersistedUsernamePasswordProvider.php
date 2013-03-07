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
 * An authentication provider that authenticates
 * TYPO3\Flow\Security\Authentication\Token\UsernamePassword tokens.
 * The accounts are stored in the Content Repository.
 */
class PersistedUsernamePasswordProvider extends \TYPO3\Flow\Security\Authentication\Provider\AbstractProvider {

	/**
	 * @var \TYPO3\Flow\Security\AccountRepository
	 * @Flow\Inject
	 */
	protected $accountRepository;

	/**
	 * @var \TYPO3\Flow\Security\Cryptography\HashService
	 * @Flow\Inject
	 */
	protected $hashService;

	/**
	 * Returns the class names of the tokens this provider can authenticate.
	 *
	 * @return array
	 */
	public function getTokenClassNames() {
		return array('TYPO3\Flow\Security\Authentication\Token\UsernamePassword', 'TYPO3\Flow\Security\Authentication\Token\UsernamePasswordHttpBasic');
	}

	/**
	 * Checks the given token for validity and sets the token authentication status
	 * accordingly (success, wrong credentials or no credentials given).
	 *
	 * @param \TYPO3\Flow\Security\Authentication\TokenInterface $authenticationToken The token to be authenticated
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception\UnsupportedAuthenticationTokenException
	 */
	public function authenticate(\TYPO3\Flow\Security\Authentication\TokenInterface $authenticationToken) {
		if (!($authenticationToken instanceof \TYPO3\Flow\Security\Authentication\Token\UsernamePassword)) {
			throw new \TYPO3\Flow\Security\Exception\UnsupportedAuthenticationTokenException('This provider cannot authenticate the given token.', 1217339840);
		}

		/** @var $account \TYPO3\Flow\Security\Account */
		$account = NULL;
		$credentials = $authenticationToken->getCredentials();

		if (is_array($credentials) && isset($credentials['username'])) {
			$account = $this->accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName($credentials['username'], $this->name);
		}

		if (is_object($account)) {
			if ($this->hashService->validatePassword($credentials['password'], $account->getCredentialsSource())) {
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