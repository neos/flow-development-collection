<?php
declare(strict_types=1);

namespace Neos\Cache\Backend;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A contract for a Cache Backend which supports tagging.
 *
 * @api
 */
interface TaggableBackendInterface extends BackendInterface
{
    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     * @return integer The number of entries which have been affected by this flush
     * @api
     */
    public function flushByTag(string $tag): int;

    /**
     * Removes all cache entries of this cache which are tagged by any of the specified tags.
     *
     * @param array<string> $tags The tags the entries must have
     * @return integer The number of entries which have been affected by this flush
     * @api
     */
    public function flushByTags(array $tags): int;

    /**
     * Finds and returns all cache entry identifiers which are tagged by the
     * specified tag.
     *
     * @param string $tag The tag to search for
     * @return string[] An array with identifiers of all matching entries. An empty array if no entries matched
     * @api
     */
    public function findIdentifiersByTag(string $tag): array;
}
