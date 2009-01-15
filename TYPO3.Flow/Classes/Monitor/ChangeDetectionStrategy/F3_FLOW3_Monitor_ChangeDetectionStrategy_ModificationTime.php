<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Monitor\ChangeDetectionStrategy;

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
 * @package FLOW3
 * @subpackage Monitor
 * @version $Id$
 */

/**
 * A change detection strategy based on modification times
 *
 * @package FLOW3
 * @subpackage Monitor
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @author Robert Lemke <robert@typo3.org>
 */
class ModificationTime implements \F3\FLOW3\Monitor\ChangeDetectionStrategyInterface {

	/**
	 * @var \F3\FLOW3\Cache\VariableCache
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
	 */
	public function injectCache(\F3\FLOW3\Cache\VariableCache $cache) {
		$this->cache = $cache;
	}

	/**
	 * Initializes this strategy
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObject() {
		if ($this->cache->has('filesAndModificationTimes')) {
			$this->filesAndModificationTimes = $this->cache->get('filesAndModificationTimes');
		}
	}

	/**
	 * Checks if the specified file has changed
	 *
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
	 * Checks if the specified directory has changed
	 *
	 * @return integer One of the STATUS_* constants
	 */
	public function getDirectoryStatus($path) {
		return self::STATUS_UNCHANGED;
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