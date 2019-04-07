<?php

namespace Neos\Flow\Security;

use Neos\Flow\Security\Authentication\AuthenticationProviderName;
use Neos\Flow\Security\Authentication\CredentialsSource;

interface AccountInterface
{

    /**
     * @return AccountIdentifier
     */
    public function getIdentifier(): AccountIdentifier;

    /**
     * @return AuthenticationProviderName
     */
    public function getProviderName(): AuthenticationProviderName;

    /**
     * @return mixed
     */
    public function getCredentialsSource(): CredentialsSource;
}
