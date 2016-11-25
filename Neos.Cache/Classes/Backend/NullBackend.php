<?php
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

// @codeCoverageIgnoreStart

use Neos\Cache\Backend\AbstractBackend as AbstractCacheBackend;
use Neos\Cache\Backend\PhpCapableBackendInterface;
use Neos\Cache\Backend\TaggableBackendInterface;

/**
 * A caching backend which forgets everything immediately
 *
 * @api
 */
class NullBackend extends AbstractCacheBackend implements PhpCapableBackendInterface, TaggableBackendInterface
{
    /**
     * Acts as if it would save data
     *
     * @param string $entryIdentifier ignored
     * @param string $data ignored
     * @param array $tags ignored
     * @param integer $lifetime ignored
     * @return void
     * @api
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
    }

    /**
     * Returns False
     *
     * @param string $entryIdentifier ignored
     * @return boolean FALSE
     * @api
     */
    public function get($entryIdentifier)
    {
        return false;
    }

    /**
     * Returns False
     *
     * @param string $entryIdentifier ignored
     * @return boolean FALSE
     * @api
     */
    public function has($entryIdentifier)
    {
        return false;
    }

    /**
     * Does nothing
     *
     * @param string $entryIdentifier ignored
     * @return boolean FALSE
     * @api
     */
    public function remove($entryIdentifier)
    {
        return false;
    }

    /**
     * Returns an empty array
     *
     * @param string $tag ignored
     * @return array An empty array
     * @api
     */
    public function findIdentifiersByTag($tag)
    {
        return [];
    }

    /**
     * Does nothing
     *
     * @return void
     * @api
     */
    public function flush()
    {
    }

    /**
     * Does nothing
     *
     * @param string $tag ignored
     * @return void
     * @api
     */
    public function flushByTag($tag)
    {
    }

    /**
     * Does nothing
     *
     * @return void
     * @api
     */
    public function collectGarbage()
    {
    }

    /**
     * Does nothing
     *
     * @param string $identifier An identifier which describes the cache entry to load
     * @return void
     * @api
     */
    public function requireOnce($identifier)
    {
    }
}
// @codeCoverageIgnoreEnd
