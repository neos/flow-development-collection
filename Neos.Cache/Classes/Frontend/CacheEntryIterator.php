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

use Neos\Cache\Backend\IterableBackendInterface;

/**
 * An iterator for cache entries
 *
 * @api
 */
class CacheEntryIterator implements \Iterator
{
    /**
     * @var FrontendInterface
     */
    protected $frontend;

    /**
     * @var IterableBackendInterface
     */
    protected $backend;

    /**
     * Constructs this Iterator
     *
     * @param FrontendInterface $frontend Frontend of the cache to iterate over
     * @param IterableBackendInterface $backend Backend of the cache
     */
    public function __construct(FrontendInterface $frontend, IterableBackendInterface $backend)
    {
        $this->frontend = $frontend;
        $this->backend = $backend;
        $this->backend->rewind();
    }

    /**
     * Returns the data of the current cache entry pointed to by the cache entry
     * iterator.
     *
     * @return mixed
     * @api
     */
    public function current()
    {
        return $this->frontend->get((string) $this->backend->key());
    }

    /**
     * Move forward to the next cache entry
     *
     * @return void
     * @api
     */
    public function next()
    {
        $this->backend->next();
    }

    /**
     * Returns the identifier of the current cache entry pointed to by the cache
     * entry iterator.
     *
     * @return string
     * @api
     */
    public function key(): string
    {
        return (string) $this->backend->key();
    }

    /**
     * Checks if current position of the cache entry iterator is valid
     *
     * @return boolean true if the current element of the iterator is valid, otherwise false
     * @api
     */
    public function valid(): bool
    {
        return $this->backend->valid();
    }

    /**
     * Rewind the cache entry iterator to the first element
     *
     * @return void
     * @api
     */
    public function rewind()
    {
        $this->backend->rewind();
    }
}
