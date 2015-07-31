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
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\Authentication\Token\PasswordToken;
use TYPO3\Flow\Security\Authentication\TokenInterface;
use TYPO3\Flow\Security\Cryptography\FileBasedSimpleKeyService;
use TYPO3\Flow\Security\Cryptography\HashService;
use TYPO3\Flow\Security\Exception\UnsupportedAuthenticationTokenException;
use TYPO3\Flow\Security\Policy\PolicyService;

/**
 * An authentication provider that authenticates
 * TYPO3\Flow\Security\Authentication\Token\PasswordToken tokens.
 * The passwords are stored as encrypted files in persisted data and
 * are fetched using the file based simple key service.
 *
 * The roles set in authenticateRoles will be added to the authenticated
 * token, but will not be persisted in the database as this provider is
 * used for situations in which no database connection might be present.
 *
 * = Example =
 *
 * TYPO3:
 *   Flow:
 *     security:
 *       authentication:
 *         providers:
 *           AdminInterfaceProvider:
 *             provider: FileBasedSimpleKeyProvider
 *             providerOptions:
 *               keyName: AdminKey
 *               authenticateRoles: ['TYPO3.Flow.SomeRole']
 */
class FileBasedSimpleKeyProvider extends AbstractProvider {

	/**
	 * @Flow\Inject
	 * @var HashService
	 */
	protected $hashService;

	/**
	 * @Flow\Inject
	 * @var FileBasedSimpleKeyService
	 */
	protected $fileBasedSimpleKeyService;

	/**
	 * @Flow\Inject
	 * @var PolicyService
	 */
	protected $policyService;

	/**
	 * Returns the class names of the tokens this provider can authenticate.
	 *
	 * @return array
	 */
	public function getTokenClassNames() {
		return array('TYPO3\Flow\Security\Authentication\Token\PasswordToken');
	}

	/**
	 * Sets isAuthenticated to TRUE for all tokens.
	 *
	 * @param TokenInterface $authenticationToken The token to be authenticated
	 * @return void
	 * @throws UnsupportedAuthenticationTokenException
	 */
	public function authenticate(TokenInterface $authenticationToken) {
		if (!($authenticationToken instanceof PasswordToken)) {
			throw new UnsupportedAuthenticationTokenException('This provider cannot authenticate the given token.', 1217339840);
		}

		$credentials = $authenticationToken->getCredentials();
		if (is_array($credentials) && isset($credentials['password'])) {
			if ($this->hashService->validatePassword($credentials['password'], $this->fileBasedSimpleKeyService->getKey($this->options['keyName']))) {
				$authenticationToken->setAuthenticationStatus(TokenInterface::AUTHENTICATION_SUCCESSFUL);
				$account = new Account();
				$roles = array();
				foreach ($this->options['authenticateRoles'] as $roleIdentifier) {
					$roles[] = $this->policyService->getRole($roleIdentifier);
				}
				$account->setRoles($roles);
				$authenticationToken->setAccount($account);
			} else {
				$authenticationToken->setAuthenticationStatus(TokenInterface::WRONG_CREDENTIALS);
			}
		} elseif ($authenticationToken->getAuthenticationStatus() !== TokenInterface::AUTHENTICATION_SUCCESSFUL) {
			$authenticationToken->setAuthenticationStatus(TokenInterface::NO_CREDENTIALS_GIVEN);
		}
	}

}
