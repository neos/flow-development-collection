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
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\AccountInterface;
use Neos\Flow\Security\AccountRepository;
use Neos\Flow\Security\AccountRepositoryInterface;
use Neos\Flow\Security\Authentication\Token\UsernamePasswordTokenInterface;
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
class PersistedUsernamePasswordProvider extends AbstractProvider
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
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
        return [UsernamePasswordTokenInterface::class];
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
        if (!($authenticationToken instanceof UsernamePasswordTokenInterface)) {
            throw new UnsupportedAuthenticationTokenException(sprintf('This provider cannot authenticate the given token. The token must implement %s', UsernamePasswordTokenInterface::class), 1217339840);
        }

        /** @var $account AccountInterface */
        $account = null;

        if ($authenticationToken->getAuthenticationStatus() !== TokenInterface::AUTHENTICATION_SUCCESSFUL) {
            $authenticationToken->setAuthenticationStatus(TokenInterface::NO_CREDENTIALS_GIVEN);
        }

        $username = $authenticationToken->getUsername();
        $password = $authenticationToken->getPassword();

        if ($username === '' || $password === '') {
            return;
        }

        $providerName = $this->options['lookupProviderName'] ?? $this->name;
        $this->securityContext->withoutAuthorizationChecks(function () use ($username, &$account, $providerName) {
            $account = $this->accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName($username, $providerName);

        });

        $authenticationToken->setAuthenticationStatus(TokenInterface::WRONG_CREDENTIALS);

        if ($account === null) {
            // validate the account anyways (with a dummy salt) in order to prevent timing attacks on this provider
            $this->hashService->validatePassword($password, 'bcrypt=>$2a$16$RW.NZM/uP3mC8rsXKJGuN.2pG52thRp5w39NFO.ShmYWV7mJQp0rC');
            return;
        }

        if ($this->hashService->validatePassword($password, (string) $account->getCredentialsSource())) {
            $authenticationToken->setAuthenticationStatus(TokenInterface::AUTHENTICATION_SUCCESSFUL);
            $authenticationToken->setAccount($account);
        }
    }

    /**
     * @return AccountRepositoryInterface
     * @throws SecurityException
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
