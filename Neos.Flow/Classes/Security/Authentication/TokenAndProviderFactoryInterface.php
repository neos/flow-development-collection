<?php
namespace Neos\Flow\Security\Authentication;

interface TokenAndProviderFactoryInterface
{
    /**
     * @return TokenInterface[]
     */
    public function getTokens(): array;

    /**
     * @return AuthenticationProviderInterface[]
     */
    public function getProviders(): array;
}
