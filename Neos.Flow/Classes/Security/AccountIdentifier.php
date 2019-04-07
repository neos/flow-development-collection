<?php

declare(strict_types=1);

namespace Neos\Flow\Security;

final class AccountIdentifier
{

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @param string $identifier
     */
    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->identifier;
    }
}
