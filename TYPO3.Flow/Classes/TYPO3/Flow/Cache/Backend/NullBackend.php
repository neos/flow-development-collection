<?php
namespace TYPO3\Flow\Cache\Backend;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

// @codeCoverageIgnoreStart

/**
 * A caching backend which forgets everything immediately
 *
 * @api
 */
class NullBackend extends AbstractBackend implements PhpCapableBackendInterface, TaggableBackendInterface
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
    public function set($entryIdentifier, $data, array $tags = array(), $lifetime = null)
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
        return array();
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
