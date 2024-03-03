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


/**
 * An abstract cache
 *
 * @api
 */
abstract class AbstractFrontend extends AbstractLowLevelFrontend implements FrontendInterface
{
    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return boolean true if such an entry exists, false if not
     * @throws \InvalidArgumentException
     * @api
     */
    public function has(string $entryIdentifier): bool
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233058486);
        }

        return $this->backend->has($entryIdentifier);
    }

    /**
     * Removes the given cache entry from the cache.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return boolean true if such an entry exists, false if not
     * @throws \InvalidArgumentException
     * @api
     */
    public function remove(string $entryIdentifier): bool
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233058495);
        }

        return $this->backend->remove($entryIdentifier);
    }
}
