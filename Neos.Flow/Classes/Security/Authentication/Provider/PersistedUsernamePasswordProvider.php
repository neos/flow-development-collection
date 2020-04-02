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
use Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException;
use Neos\Flow\ObjectManagement\Exception\CannotBuildObjectException;
use Neos\Flow\ObjectManagement\Exception\UnknownObjectException;
use Neos\Flow\ObjectManagement\ObjectManager;
use Neos\Flow\Security\AccountInterface;
use Neos\Flow\Security\AccountRepository;
use Neos\Flow\Security\AccountRepositoryInterface;
use Neos\Flow\Security\Authentication\Token\UsernamePassword;
use Neos\Flow\Security\Authentication\Token\UsernamePasswordHttpBasic;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Flow\Security\Exception as SecurityException;
use Neos\Flow\Security\Exception\UnsupportedAuthenticationTokenException;

/**
 * An authentication provider that authenticates
 * Neos\Flow\Security\Authentication\Token\UsernamePassword tokens.
 * The accounts are stored in the Content Repository.
 */
final class PersistedUsernamePasswordProvider extends AbstractProvider
{
    /**
     * @Flow\Inject
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var HashService
     */
    protected $hashService;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * Returns the name of this provider
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the class names of the tokens this provider can authenticate.
     *
     * @return array
     */
    public function getTokenClassNames()
    {
        return [UsernamePassword::class, UsernamePasswordHttpBasic::class];
    }

    /**
     * Checks the given token for validity and sets the token authentication status
     * accordingly (success, wrong credentials or no credentials given).
     *
     * @param TokenInterface $authenticationToken The token to be authenticated
     * @return void
     * @throws \Exception
     */
    public function authenticate(TokenInterface $authenticationToken)
    {
        if (!($authenticationToken instanceof UsernamePassword)) {
            throw new UnsupportedAuthenticationTokenException('This provider cannot authenticate the given token.', 1217339840);
        }

        /** @var $account AccountInterface */
        $account = null;
        $credentials = $authenticationToken->getCredentials();

        if ($authenticationToken->getAuthenticationStatus() !== TokenInterface::AUTHENTICATION_SUCCESSFUL) {
            $authenticationToken->setAuthenticationStatus(TokenInterface::NO_CREDENTIALS_GIVEN);
        }

        if (!isset($credentials['username'], $credentials['password'])) {
            return;
        }

        $this->securityContext->withoutAuthorizationChecks(function () use ($credentials, &$account) {
            $account = $this->getAccountRepository()->findActiveByAccountIdentifierAndAuthenticationProviderName($credentials['username'], $this->name);
        });

        $authenticationToken->setAuthenticationStatus(TokenInterface::WRONG_CREDENTIALS);

        if ($account === null) {
            // validate the account anyways (with a dummy salt) in order to prevent timing attacks on this provider
            $this->hashService->validatePassword($credentials['password'], 'bcrypt=>$2a$16$RW.NZM/uP3mC8rsXKJGuN.2pG52thRp5w39NFO.ShmYWV7mJQp0rC');
            return;
        }

        if ($this->hashService->validatePassword($credentials['password'], (string) $account->getCredentialsSource())) {
            $authenticationToken->setAuthenticationStatus(TokenInterface::AUTHENTICATION_SUCCESSFUL);
            $authenticationToken->setAccount($account);
        }
    }

    /**
     * @return AccountRepositoryInterface
     * @throws SecurityException
     * @throws InvalidConfigurationTypeException | CannotBuildObjectException | UnknownObjectException
     */
    private function getAccountRepository(): AccountRepositoryInterface
    {
        $accountRepository = $this->objectManager->get($this->options['accountRepositoryClassName'] ?? AccountRepository::class);
        if (!$accountRepository instanceof AccountRepositoryInterface) {
            throw new SecurityException(sprintf('The configured "accountRepositoryClassName" is not an instance of %s but of type %s. Check the %s authentication provider configuration', AccountRepositoryInterface::class, get_class($accountRepository), $this->name), 1585837588);
        }
        return $accountRepository;
    }
}
