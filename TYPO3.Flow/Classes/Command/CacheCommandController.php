<?php
namespace TYPO3\FLOW3\Command;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Command controller for managing caches
 *
 * NOTE: This command controller will run in compile time (as defined in the package bootstrap)
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 */
class CacheCommandController extends \TYPO3\FLOW3\MVC\Controller\CommandController {

	/**
	 * @var \TYPO3\FLOW3\Cache\CacheManager
	 */
	protected $cacheManager;

	/**
	 * @var \TYPO3\FLOW3\Core\LockManager
	 */
	protected $lockManager;

	/**
	 * Injects the cache manager
	 *
	 * @param \TYPO3\FLOW3\Cache\CacheManager $cacheManager
	 * @return void
	 */
	public function injectCacheManager(\TYPO3\FLOW3\Cache\CacheManager $cacheManager) {
		$this->cacheManager = $cacheManager;
	}

	/**
	 * Injects the Lock Manager
	 *
	 * @param \TYPO3\FLOW3\Core\LockManager $lockManager
	 * @return void
	 */
	public function injectLockManager(\TYPO3\FLOW3\Core\LockManager $lockManager) {
		$this->lockManager = $lockManager;
	}

	/**
	 * Flush all caches
	 *
	 * The flush command flushes all caches, including code caches, which have been
	 * registered with FLOW3's Cache Manager.
	 *
	 * @return void
	 * @see typo3.flow3:cache:warmup
	 */
	public function flushCommand() {
		$this->cacheManager->flushCaches();
		$this->outputLine('Flushed all caches.');
		if ($this->lockManager->isSiteLocked()) {
			$this->lockManager->unlockSite();
		}
		$this->sendAndExit(0);
	}

	/**
	 * Warm up caches
	 *
	 * The warm up caches command initializes and fills – as far as possible – all
	 * registered caches to get a snappier response on the first following request.
	 * Apart from caches, other parts of the application may hook into this command
	 * and execute tasks which take further steps for preparing the app for the big
	 * rush.
	 *
	 * @return void
	 * @see typo3.flow3:cache:flush
	 */
	public function warmupCommand() {
		$this->emitWarmupCaches();
		$this->outputLine('Warmed up caches.');
	}

	/**
	 * Signals that caches should be warmed up.
	 *
	 * Other application parts may subscribe to this signal and execute additional
	 * tasks for preparing the application for the first request.
	 *
	 * @return void
	 * @signal
	 */
	public function emitWarmupCaches() {
	}
}

?>