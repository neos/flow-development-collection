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

namespace Neos\Flow\Session\Data;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Algorithms;

/**
 * The StorageIdentifier is used by the SessionKeyValueStore to write / retrieve
 * th values for the given session. The StorageIdentifier is a secret of the server session
 * and  is never exposed to the outside. The StorageIdentifier stays the same if a
 * Session gets a new SessionIdentifier (renewId).
 *
 * @Flow\Proxy(false)
 * @internal
 */
class StorageIdentifier
{
    private function __construct(
        public readonly string $value
    ) {
    }

    public static function createFromString(string $value): self
    {
        return new self($value);
    }

    public static function createRandom(): self
    {
        return new self(Algorithms::generateUUID());
    }

    public function equals(StorageIdentifier $other): bool
    {
        return $this->value === $other->value;
    }
}
