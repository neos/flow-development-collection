<?php
declare(strict_types=1);

namespace Neos\Cache\Frontend;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Backend\BackendInterface;
use Neos\Cache\Backend\TaggableBackendInterface;

/**
 * An abstract cache
 *
 * @api
 */
abstract class AbstractLowLevelFrontend implements LowLevelFrontendInterface
{
    /**
     * Identifies this cache
     * @var string
     */
    protected $identifier;

    /**
     * @var BackendInterface
     */
    protected $backend;

    /**
     * Constructs the cache
     *
     * @param string $identifier A identifier which describes this cache
     * @param BackendInterface $backend Backend to be used for this cache
     * @throws \InvalidArgumentException if the identifier doesn't match PATTERN_ENTRYIDENTIFIER
     */
    public function __construct(string $identifier, BackendInterface $backend)
    {
        if (preg_match(self::PATTERN_ENTRYIDENTIFIER, $identifier) !== 1) {
            throw new \InvalidArgumentException('"' . $identifier . '" is not a valid cache identifier.', 1203584729);
        }
        $this->identifier = $identifier;
        $this->backend = $backend;
    }

    /**
     * Returns this cache's identifier
     *
     * @return string The identifier for this cache
     * @api
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Returns the backend used by this cache
     *
     * @return BackendInterface The backend used by this cache
     * @api
     */
    public function getBackend(): BackendInterface
    {
        return $this->backend;
    }

    /**
     * Removes all cache entries of this cache.
     *
     * @return void
     * @api
     */
    public function flush()
    {
        $this->backend->flush();
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     * @return integer The number of entries which have been affected by this flush
     * @throws \InvalidArgumentException
     * @api
     */
    public function flushByTag(string $tag): int
    {
        if (!$this->isValidTag($tag)) {
            throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1233057359);
        }
        if ($this->backend instanceof TaggableBackendInterface) {
            return $this->backend->flushByTag($tag);
        }
        return 0;
    }

    /**
     * Removes all cache entries of this cache which are tagged by any of the specified tags.
     *
     * @param array<string> $tags The tags the entries must have
     * @return integer The number of entries which have been affected by this flush
     * @throws \InvalidArgumentException
     * @api
     */
    public function flushByTags(array $tags): int
    {
        foreach ($tags as $tag) {
            if (!$this->isValidTag($tag)) {
                throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1646209443);
            }
        }
        if ($this->backend instanceof TaggableBackendInterface) {
            return $this->backend->flushByTags($tags);
        }
        return 0;
    }

    /**
     * Does garbage collection
     *
     * @return void
     * @api
     */
    public function collectGarbage()
    {
        $this->backend->collectGarbage();
    }

    /**
     * Checks the validity of an entry identifier. Returns true if it's valid.
     *
     * @param string $identifier An identifier to be checked for validity
     * @return boolean
     * @api
     */
    public function isValidEntryIdentifier(string $identifier): bool
    {
        return preg_match(self::PATTERN_ENTRYIDENTIFIER, $identifier) === 1;
    }

    /**
     * Checks the validity of a tag. Returns true if it's valid.
     *
     * @param string $tag An identifier to be checked for validity
     * @return boolean
     * @api
     */
    public function isValidTag(string $tag): bool
    {
        return preg_match(self::PATTERN_TAG, $tag) === 1;
    }
}
