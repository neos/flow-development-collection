<?php
namespace TYPO3\Flow\Monitor\ChangeDetectionStrategy;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

use TYPO3\Flow\Cache\Frontend\VariableFrontend;
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
	 * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
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
	 * @param \TYPO3\Flow\Cache\Frontend\VariableFrontend $cache
	 * @return void
	 */
	public function injectCache(VariableFrontend $cache) {
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
		$this->filesAndModificationTimes = $this->cache->get($this->fileMonitor->getIdentifier() . '_filesAndModificationTimes');
	}

	/**
	 * Checks if the specified file has changed
	 *
	 * @param string $pathAndFilename
	 * @return integer One of the STATUS_* constants
	 */
	public function getFileStatus($pathAndFilename) {
		if (isset($this->filesAndModificationTimes[$pathAndFilename])) {
			if (file_exists($pathAndFilename)) {
				$actualModificationTime = filemtime($pathAndFilename);
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
			if (file_exists($pathAndFilename)) {
				$this->filesAndModificationTimes[$pathAndFilename] = filemtime($pathAndFilename);
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
			$this->cache->set($this->fileMonitor->getIdentifier() . '_filesAndModificationTimes', $this->filesAndModificationTimes);
		}
	}
}
?>