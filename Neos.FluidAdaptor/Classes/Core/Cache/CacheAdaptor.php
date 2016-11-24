<?php
namespace Neos\FluidAdaptor\Core\Cache;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\PhpFrontend;
use TYPO3Fluid\Fluid\Core\Cache\FluidCacheInterface;
use TYPO3Fluid\Fluid\Core\Cache\FluidCacheWarmerInterface;
use TYPO3Fluid\Fluid\Core\Cache\StandardCacheWarmer;

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

    /**
     * Get an instance of FluidCacheWarmerInterface which
     * can warm up template files that would normally be
     * cached on-the-fly to this FluidCacheInterface
     * implementaion.
     *
     * @return FluidCacheWarmerInterface
     */
    public function getCacheWarmer()
    {
        return new StandardCacheWarmer();
    }
}
