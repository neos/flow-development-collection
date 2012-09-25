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
 * TYPO3\Flow\Security\Authentication\Token\PasswordToken tokens.
 * The passwords are stored as encrypted files in persisted data and
 * are fetched using the file based simple key service.
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
 */
class FileBasedSimpleKeyProvider extends \TYPO3\Flow\Security\Authentication\Provider\AbstractProvider {

	/**
	 * @var \TYPO3\Flow\Security\Cryptography\HashService
	 * @Flow\Inject
	 */
	protected $hashService;

	/**
	 * @var \TYPO3\Flow\Security\Cryptography\FileBasedSimpleKeyService
	 * @Flow\Inject
	 */
	protected $fileBasedSimpleKeyService;

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
	 * @param \TYPO3\Flow\Security\Authentication\TokenInterface $authenticationToken The token to be authenticated
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception\UnsupportedAuthenticationTokenException
	 */
	public function authenticate(\TYPO3\Flow\Security\Authentication\TokenInterface $authenticationToken) {
		if (!($authenticationToken instanceof \TYPO3\Flow\Security\Authentication\Token\PasswordToken)) {
			throw new \TYPO3\Flow\Security\Exception\UnsupportedAuthenticationTokenException('This provider cannot authenticate the given token.', 1217339840);
		}

		$credentials = $authenticationToken->getCredentials();
		if (is_array($credentials) && isset($credentials['password'])) {
			if ($this->hashService->validatePassword($credentials['password'], $this->fileBasedSimpleKeyService->getKey($this->options['keyName']))) {
				$authenticationToken->setAuthenticationStatus(\TYPO3\Flow\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL);
			} else {
				$authenticationToken->setAuthenticationStatus(\TYPO3\Flow\Security\Authentication\TokenInterface::WRONG_CREDENTIALS);
			}
		} elseif ($authenticationToken->getAuthenticationStatus() !== \TYPO3\Flow\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL) {
			$authenticationToken->setAuthenticationStatus(\TYPO3\Flow\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN);
		}
	}

}
?>