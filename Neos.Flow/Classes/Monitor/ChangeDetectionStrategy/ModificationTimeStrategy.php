<?php
namespace Neos\Flow\Monitor\ChangeDetectionStrategy;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Monitor\FileMonitor;
use Neos\Flow\Annotations as Flow;

/**
 * A change detection strategy based on modification times
 */
class ModificationTimeStrategy implements ChangeDetectionStrategyInterface, StrategyWithMarkDeletedInterface
{
    /**
     * @var \Neos\Flow\Monitor\FileMonitor
     */
    protected $fileMonitor;

    /**
     * @var \Neos\Cache\Frontend\StringFrontend
     */
    protected $cache;

    /**
     * @var array
     */
    protected $filesAndModificationTimes = [];

    /**
     * If the modification times changed and therefore need to be cached
     * @var boolean
     */
    protected $modificationTimesChanged = false;

    /**
     * Injects the Flow_Monitor cache
     *
     * @param \Neos\Cache\Frontend\StringFrontend $cache
     * @return void
     */
    public function injectCache(StringFrontend $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Initializes this strategy
     *
     * @param FileMonitor $fileMonitor
     * @return void
     */
    public function setFileMonitor(FileMonitor $fileMonitor)
    {
        $this->fileMonitor = $fileMonitor;
        $this->filesAndModificationTimes = json_decode($this->cache->get($this->fileMonitor->getIdentifier() . '_filesAndModificationTimes'), true);
    }

    /**
     * Checks if the specified file has changed
     *
     * @param string $pathAndFilename
     * @return integer One of the STATUS_* constants
     */
    public function getFileStatus($pathAndFilename)
    {
        $actualModificationTime = @filemtime($pathAndFilename);
        if (isset($this->filesAndModificationTimes[$pathAndFilename])) {
            if ($actualModificationTime !== false) {
                if ($this->filesAndModificationTimes[$pathAndFilename] === $actualModificationTime) {
                    return self::STATUS_UNCHANGED;
                } else {
                    $this->filesAndModificationTimes[$pathAndFilename] = $actualModificationTime;
                    $this->modificationTimesChanged = true;
                    return self::STATUS_CHANGED;
                }
            } else {
                unset($this->filesAndModificationTimes[$pathAndFilename]);
                $this->modificationTimesChanged = true;
                return self::STATUS_DELETED;
            }
        } else {
            if ($actualModificationTime !== false) {
                $this->filesAndModificationTimes[$pathAndFilename] = $actualModificationTime;
                $this->modificationTimesChanged = true;
                return self::STATUS_CREATED;
            } else {
                return self::STATUS_UNCHANGED;
            }
        }
    }

    /**
     * Notify the change strategy that this file was deleted and does not need to be tracked anymore.
     *
     * @param string $pathAndFilename
     * @return void
     */
    public function setFileDeleted($pathAndFilename)
    {
        if (isset($this->filesAndModificationTimes[$pathAndFilename])) {
            unset($this->filesAndModificationTimes[$pathAndFilename]);
            $this->modificationTimesChanged = true;
        }
    }

    /**
     * Caches the file modification times
     *
     * @return void
     */
    public function shutdownObject()
    {
        if ($this->modificationTimesChanged === true) {
            $this->cache->set($this->fileMonitor->getIdentifier() . '_filesAndModificationTimes', json_encode($this->filesAndModificationTimes));
        }
    }
}
