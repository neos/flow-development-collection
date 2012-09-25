<?php
namespace TYPO3\Flow\Security;

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
 * A factory for conveniently creating new accounts
 *
 * @Flow\Scope("singleton")
 */
class AccountFactory {

	/**
	 * @var \TYPO3\Flow\Security\Cryptography\HashService
	 * @Flow\Inject
	 */
	protected $hashService;

	/**
	 * Creates a new account and sets the given password and roles
	 *
	 * @param string $identifier Identifier of the account, must be unique
	 * @param string $password The clear text password
	 * @param array $roleIdentifiers Optionally an array of role identifiers to assign to the new account
	 * @param string $authenticationProviderName Optional name of the authentication provider the account is affiliated with
	 * @param string $passwordHashingStrategy Optional password hashing strategy to use for the password
	 * @return \TYPO3\Flow\Security\Account A new account, not yet added to the account repository
	 */
	public function createAccountWithPassword($identifier, $password, $roleIdentifiers = array(), $authenticationProviderName = 'DefaultProvider', $passwordHashingStrategy = 'default') {
		$roles = array();
		foreach ($roleIdentifiers as $roleIdentifier) {
			$roles[] = new \TYPO3\Flow\Security\Policy\Role($roleIdentifier);
		}

		$account = new \TYPO3\Flow\Security\Account();
		$account->setAccountIdentifier($identifier);
		$account->setCredentialsSource($this->hashService->hashPassword($password, $passwordHashingStrategy));
		$account->setAuthenticationProviderName($authenticationProviderName);
		$account->setRoles($roles);

		return $account;
	}
}

?>