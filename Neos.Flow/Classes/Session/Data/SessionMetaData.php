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

/**
 * @Flow\Proxy(false)
 */
class SessionMetaData
{
    protected int $lastActivityTimestamp;
    protected string $storageIdentifier;

    /**
     * @var string[]
     */
    protected array $tags;

    /**
     * @param string $storageIdentifier
     * @param int $lastActivityTimestamp
     * @param string[] $tags
     */
    public function __construct(string $storageIdentifier, int $lastActivityTimestamp, array $tags)
    {
        $this->lastActivityTimestamp = $lastActivityTimestamp;
        $this->storageIdentifier = $storageIdentifier;
        $this->tags = $tags;
    }

    public static function fromClassicArrayFormat(array $data): self
    {
        return new self(
            $data['storageIdentifier'],
            $data['lastActivityTimestamp'],
            $data['tags']
        );
    }

    public function withLastActivityTimestamp(int $lastActivityTimestamp): self
    {
        return new self(
            $this->storageIdentifier,
            $lastActivityTimestamp,
            $this->tags
        );
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
