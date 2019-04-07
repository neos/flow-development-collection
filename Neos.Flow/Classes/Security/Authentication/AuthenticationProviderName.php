<?php

declare(strict_types=1);

namespace Neos\Flow\Security\Authentication;

final class AuthenticationProviderName
{

    /**
     * @var string
     */
    protected $authenticationProviderName;

    /**
     * @param string $authenticationProviderName
     */
    public function __construct(string $authenticationProviderName)
    {
        $this->authenticationProviderName = $authenticationProviderName;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->authenticationProviderName;
    }

}
