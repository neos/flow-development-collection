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

    public function get(string $name): mixed
    {
        if ($this->flowCache->has($name)) {
            $this->flowCache->requireOnce($name);
        }

        return $this->flowCache->getWrapped($name);
    }

    public function set(string $name, mixed $value): void
    {
        $this->flowCache->set($name, substr($value, strpos($value, "\n") + 1));
    }

    public function flush(?string $name = null): void
    {
        if ($name === null) {
            $this->flowCache->flush();
            return;
        }
        $this->flowCache->remove($name);
    }

    public function getCacheWarmer(): FluidCacheWarmerInterface
    {
        return new StandardCacheWarmer();
    }
}
