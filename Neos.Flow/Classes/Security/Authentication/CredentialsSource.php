<?php

declare(strict_types=1);

namespace Neos\Flow\Security\Authentication;

final class CredentialsSource
{

    /**
     * @var string
     */
    protected $credentialsSource;

    /**
     * @param string $credentialsSource
     */
    public function __construct(string $credentialsSource)
    {
        $this->credentialsSource = $credentialsSource;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->credentialsSource;
    }
}
