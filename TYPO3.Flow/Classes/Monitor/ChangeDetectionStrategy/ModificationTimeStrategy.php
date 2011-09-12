<?php
namespace TYPO3\FLOW3\Monitor\ChangeDetectionStrategy;

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

/**
 * A change detection strategy based on modification times
 *
 * @author Robert Lemke <robert@typo3.org>
 * @scope singleton
 */
class ModificationTimeStrategy implements \TYPO3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface {

	/**
	 * @var \TYPO3\FLOW3\Cache\Frontend\VariableFrontend
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
	 * Injects the FLOW3_Monitor cache
	 *
	 * @param \TYPO3\FLOW3\Cache\Frontend\VariableFrontend $cache
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectCache(\TYPO3\FLOW3\Cache\Frontend\VariableFrontend $cache) {
		$this->cache = $cache;
	}

	/**
	 * Initializes this strategy
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObject() {
		$this->filesAndModificationTimes = $this->cache->get('filesAndModificationTimes');
	}

	/**
	 * Checks if the specified file has changed
	 *
	 * @param string $pathAndFilename
	 * @return integer One of the STATUS_* constants
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function shutdownObject() {
		if ($this->modificationTimesChanged === TRUE) {
			$this->cache->set('filesAndModificationTimes', $this->filesAndModificationTimes);
		}
	}
}
?>