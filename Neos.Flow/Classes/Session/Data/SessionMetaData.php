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
 * @Flow\Proxy(false)
 */
class SessionMetaData
{
    protected string $sessionIdentifier;

    protected string $storageIdentifier;

    protected int $lastActivityTimestamp;

    /**
     * @var string[]
     */
    protected array $tags;

    /**
     * @param string $sessionIdentifier
     * @param string $storageIdentifier
     * @param int $lastActivityTimestamp
     * @param string[] $tags
     */
    public function __construct(string $sessionIdentifier, string $storageIdentifier, int $lastActivityTimestamp, array $tags)
    {
        $this->sessionIdentifier = $sessionIdentifier;
        $this->storageIdentifier = $storageIdentifier;
        $this->lastActivityTimestamp = $lastActivityTimestamp;
        $this->tags = $tags;
    }

    public static function createNew(): self
    {
        return new self(
            Algorithms::generateUUID(),
            Algorithms::generateUUID(),
            time(),
            []
        );
    }

    public static function fromSessionIdentifierAndArray(string $sessionIdentifier, array $data): self
    {
        return new self(
            $sessionIdentifier,
            $data['storageIdentifier'],
            $data['lastActivityTimestamp'],
            $data['tags']
        );
    }

    public function withLastActivityTimestamp(int $lastActivityTimestamp): self
    {
        return new self(
            $this->sessionIdentifier,
            $this->storageIdentifier,
            $lastActivityTimestamp,
            $this->tags
        );
    }

    public function getSessionIdentifier(): string
    {
        return $this->sessionIdentifier;
    }

    public function getLastActivityTimestamp(): int
    {
        return $this->lastActivityTimestamp;
    }

    public function getStorageIdentifier(): string
    {
        return $this->storageIdentifier;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }
}
