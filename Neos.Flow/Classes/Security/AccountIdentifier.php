<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace Neos\Flow\Security;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class AccountIdentifier
{

    /**
     * @var string
     */
    private $identifier;

    /**
     * @param string $identifier
     */
    private function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @param string $identifier
     * @return self
     */
    public static function fromString(string $identifier): self
    {
        return new static($identifier);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->identifier;
    }
}
