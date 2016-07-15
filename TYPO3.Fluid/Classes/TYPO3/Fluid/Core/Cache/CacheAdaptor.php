<?php
namespace TYPO3\Fluid\Core\Cache;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\Frontend\PhpFrontend;
use TYPO3Fluid\Fluid\Core\Cache\FluidCacheInterface;

/**
 * @Flow\Scope("singleton")
 */
class CacheAdaptor implements FluidCacheInterface
{
    /**
     * @var PhpFrontend
     */
    protected $flowCache;

    /**
     * Gets an entry from the cache or NULL if the
     * entry does not exist.
     *
     * @param string $name
     * @return string
     */
    public function get($name)
    {
        if ($this->flowCache->has($name)) {
            $this->flowCache->requireOnce($name);
        }

        return $this->flowCache->getWrapped($name);
    }

    /**
     * Set or updates an entry identified by $name
     * into the cache.
     *
     * @param string $name
     * @param string $value
     */
    public function set($name, $value)
    {
        // we need to strip the first line with the php header as the flow cache adds that again.
        return $this->flowCache->set($name, substr($value, strpos($value, "\n") + 1));
    }

    /**
     * Flushes the cache either by entry or flushes
     * the entire cache if no entry is provided.
     *
     * @param string|null $name
     * @return bool|void
     */
    public function flush($name = null)
    {
        if ($name !== null) {
            return $this->flowCache->remove($name);
        } else {
            return $this->flowCache->flush();
        }
    }
}
