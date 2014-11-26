<?php
namespace TYPO3\Flow\Monitor\ChangeDetectionStrategy;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Cache\Frontend\StringFrontend;
use TYPO3\Flow\Monitor\FileMonitor;

use TYPO3\Flow\Annotations as Flow;

/**
 * A change detection strategy based on modification times
 */
class ModificationTimeStrategy implements ChangeDetectionStrategyInterface {

	/**
	 * @var \TYPO3\Flow\Monitor\FileMonitor
	 */
	protected $fileMonitor;

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\StringFrontend
	 */
	protected $cache;

	/**
	 * @var array
	 */
	protected $filesAndModificationTimes = array();

	/**
	 * If the modification times changed and therefore need to be cached
	 * @var boolean
	 */
	protected $modificationTimesChanged = FALSE;

	/**
	 * Injects the Flow_Monitor cache
	 *
	 * @param \TYPO3\Flow\Cache\Frontend\StringFrontend $cache
	 * @return void
	 */
	public function injectCache(StringFrontend $cache) {
		$this->cache = $cache;
	}

	/**
	 * Initializes this strategy
	 *
	 * @param FileMonitor $fileMonitor
	 * @return void
	 */
	public function setFileMonitor(FileMonitor $fileMonitor) {
		$this->fileMonitor = $fileMonitor;
		$this->filesAndModificationTimes = json_decode($this->cache->get($this->fileMonitor->getIdentifier() . '_filesAndModificationTimes'), TRUE);
	}

	/**
	 * Checks if the specified file has changed
	 *
	 * @param string $pathAndFilename
	 * @return integer One of the STATUS_* constants
	 */
	public function getFileStatus($pathAndFilename) {
		$actualModificationTime = @filemtime($pathAndFilename);
		if (isset($this->filesAndModificationTimes[$pathAndFilename])) {
			if ($actualModificationTime !== FALSE) {
				if ($this->filesAndModificationTimes[$pathAndFilename] === $actualModificationTime) {
					return self::STATUS_UNCHANGED;
				} else {
					$this->filesAndModificationTimes[$pathAndFilename] = $actualModificationTime;
					$this->modificationTimesChanged = TRUE;
					return self::STATUS_CHANGED;
				}
			} else {
				unset($this->filesAndModificationTimes[$pathAndFilename]);
				$this->modificationTimesChanged = TRUE;
				return self::STATUS_DELETED;
			}
		} else {
			if ($actualModificationTime !== FALSE) {
				$this->filesAndModificationTimes[$pathAndFilename] = $actualModificationTime;
				$this->modificationTimesChanged = TRUE;
				return self::STATUS_CREATED;
			} else {
				return self::STATUS_UNCHANGED;
			}
		}
	}

	/**
	 * Caches the file modification times
	 *
	 * @return void
	 */
	public function shutdownObject() {
		if ($this->modificationTimesChanged === TRUE) {
			$this->cache->set($this->fileMonitor->getIdentifier() . '_filesAndModificationTimes', json_encode($this->filesAndModificationTimes));
		}
	}
}
