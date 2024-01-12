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
    /**
     * @param string $sessionIdentifier
     * @param string $storageIdentifier
     * @param int $lastActivityTimestamp
     * @param string[] $tags
     */
    public function __construct(
        public /** readonly */ string $sessionIdentifier,
        public /** readonly */ string $storageIdentifier,
        public /** readonly */ int $lastActivityTimestamp,
        public /** readonly */ array $tags
    ) {
    }

    public static function createWithTimestamp(int $timestamp): self
    {
        return new self(
            Algorithms::generateRandomString(32),
            Algorithms::generateUUID(),
            $timestamp,
            []
        );
    }

    /**
     * Create session metadata from classic cache format for backwards compatibility
     * @param string $sessionIdentifier
     * @param array{'storageIdentifier': string, 'lastActivityTimestamp': int, 'tags': string[]} $data
     * @deprecated this will be removed with flow 10
     */
    public static function createFromSessionIdentifierAndOldArrayCacheFormat(string $sessionIdentifier, array $data): self
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

    public function withNewSessionIdentifier(): self
    {
        return new self(
            Algorithms::generateRandomString(32),
            $this->storageIdentifier,
            $this->lastActivityTimestamp,
            $this->tags
        );
    }

    public function withAddedTag(string $tag): self
    {
        $tags = $this->tags;
        if (!in_array($tag, $this->tags)) {
            $tags[] = $tag;
        }
        return new self(
            $this->sessionIdentifier,
            $this->storageIdentifier,
            $this->lastActivityTimestamp,
            $tags
        );
    }

    public function withRemovedTag(string $tag): self
    {
        $tags = $this->tags;
        $index = array_search($tag, $tags);
        if ($index !== false) {
            unset($tags[$index]);
        }
        return new self(
            $this->sessionIdentifier,
            $this->storageIdentifier,
            $this->lastActivityTimestamp,
            $tags
        );
    }

    /**
     * Determine whether the metadata is equal in all aspects other than lastActivityTimestamp
     */
    public function isSame(SessionMetaData $other): bool
    {
        if ($this->sessionIdentifier !== $other->sessionIdentifier) {
            return false;
        }

        if ($this->storageIdentifier !== $other->storageIdentifier) {
            return false;
        }

        if ($this->tags !== $other->tags) {
            return false;
        }

        return true;
    }

    /**
     * Determine the age difference between the metadata items
     */
    public function ageDifference(SessionMetaData $other): ?int
    {
        return $this->lastActivityTimestamp - $other->lastActivityTimestamp;
    }
}
