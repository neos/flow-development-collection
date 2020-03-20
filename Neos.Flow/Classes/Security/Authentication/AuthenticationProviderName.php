<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace Neos\Flow\Security\Authentication;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class AuthenticationProviderName
{
    /**
     * @var string
     */
    private $authenticationProviderName;

    /**
     * @param string $authenticationProviderName
     */
    private function __construct(string $authenticationProviderName)
    {
        $this->authenticationProviderName = $authenticationProviderName;
    }

    /**
     * @param string $authenticationProviderName
     * @return self
     */
    public static function fromString(string $authenticationProviderName): self
    {
        return new static($authenticationProviderName);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->authenticationProviderName;
    }
}
