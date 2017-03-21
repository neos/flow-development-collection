<?php
namespace Neos\Flow\Security\Authentication\Provider;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Authentication\Token\PasswordToken;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Cryptography\FileBasedSimpleKeyService;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Flow\Security\Exception\UnsupportedAuthenticationTokenException;
use Neos\Flow\Security\Policy\PolicyService;

/**
 * An authentication provider that authenticates
 * Neos\Flow\Security\Authentication\Token\PasswordToken tokens.
 * The passwords are stored as encrypted files in persisted data and
 * are fetched using the file based simple key service.
 *
 * The roles set in authenticateRoles will be added to the authenticated
 * token, but will not be persisted in the database as this provider is
 * used for situations in which no database connection might be present.
 *
 * = Example =
 *
 * Neos:
 *   Flow:
 *     security:
 *       authentication:
 *         providers:
 *           AdminInterfaceProvider:
 *             provider: FileBasedSimpleKeyProvider
 *             providerOptions:
 *               keyName: AdminKey
 *               authenticateRoles: ['Neos.Flow.SomeRole']
 */
class FileBasedSimpleKeyProvider extends AbstractProvider
{
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
    public function getTokenClassNames()
    {
        return [PasswordToken::class];
    }

    /**
     * Sets isAuthenticated to TRUE for all tokens.
     *
     * @param TokenInterface $authenticationToken The token to be authenticated
     * @return void
     * @throws UnsupportedAuthenticationTokenException
     */
    public function authenticate(TokenInterface $authenticationToken)
    {
        if (!($authenticationToken instanceof PasswordToken)) {
            throw new UnsupportedAuthenticationTokenException('This provider cannot authenticate the given token.', 1217339840);
        }

        $credentials = $authenticationToken->getCredentials();
        if (is_array($credentials) && isset($credentials['password'])) {
            if ($this->hashService->validatePassword($credentials['password'], $this->fileBasedSimpleKeyService->getKey($this->options['keyName']))) {
                $authenticationToken->setAuthenticationStatus(TokenInterface::AUTHENTICATION_SUCCESSFUL);
                $account = new Account();
                $roles = [];
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
