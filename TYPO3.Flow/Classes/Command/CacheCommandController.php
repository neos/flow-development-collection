<?php
namespace TYPO3\FLOW3\Command;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use TYPO3\FLOW3\Cli\Response;
use TYPO3\FLOW3\Utility\Files;

/**
 * Command controller for managing caches
 *
 * NOTE: This command controller will run in compile time (as defined in the package bootstrap)
 *
 * @FLOW3\Scope("singleton")
 */
class CacheCommandController extends \TYPO3\FLOW3\Cli\CommandController {

	/**
	 * @var \TYPO3\FLOW3\Cache\CacheManager
	 */
	protected $cacheManager;

	/**
	 * @var \TYPO3\FLOW3\Core\LockManager
	 */
	protected $lockManager;

	/**
	 * @var \TYPO3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

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
	 * @param \TYPO3\FLOW3\Package\PackageManagerInterface $packageManager
	 * @return void
	 */
	public function injectPackageManager(\TYPO3\FLOW3\Package\PackageManagerInterface $packageManager) {
		$this->packageManager =  $packageManager;
	}

	/**
	 * Flush all caches
	 *
	 * The flush command flushes all caches, including code caches, which have been
	 * registered with FLOW3's Cache Manager.
	 *
	 * If fatal errors caused by a package prevent the compile time bootstrap
	 * from running, the removal of any temporary data can be forced by specifying
	 * the option <b>--force</b>.
	 *
	 * This command does not remove the precompiled data provided by frozen
	 * packages unless the <b>--force</b> option is used.
	 *
	 * @param boolean $force Force flushing of any temporary data
	 * @return void
	 * @see typo3.flow3:cache:warmup
	 * @see typo3.flow3:package:freeze
	 * @see typo3.flow3:package:refreeze
	 */
	public function flushCommand($force = FALSE) {

			// Internal note: the $force option is evaluated early in the FLOW3
			// bootstrap in order to reliably flush the temporary data before any
			// other code can cause fatal errors.

		$this->cacheManager->flushCaches();
		$this->outputLine('Flushed all caches.');
		if ($this->lockManager->isSiteLocked()) {
			$this->lockManager->unlockSite();
		}

		$numberOfFrozenPackages = 0;
		foreach (array_keys($this->packageManager->getActivePackages()) as $packageKey) {
			if ($this->packageManager->isPackageFrozen($packageKey)) {
				$numberOfFrozenPackages ++;
			}
		}
		if ($numberOfFrozenPackages > 0) {
			$this->outputLine();
			$this->output('NOTE: ');
			if ($numberOfFrozenPackages === 1) {
				$this->output('There is one frozen package. ');
			} else {
				$this->output('There are %d frozen packages. ', array($numberOfFrozenPackages));
			}
			$this->outputLine('Make sure to call typo3.flow3:package:refreeze or specify the --force option of the cache:flush command in order to recreate precompiled reflection data for frozen packages.');
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
	 * Call system function
	 *
	 * @FLOW3\Internal
	 * @param integer $address
	 * @return void
	 */
	public function sysCommand($address) {
		if ($address === 64738) {
			$this->cacheManager->flushCaches();
			$content = "G1syShtbMkobWzE7MzdtG1sxOzQ0bSAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAbWzBtChtbMTszN20bWzE7NDRtICAgICAgKioqKiBDT01NT0RPUkUgNjQgQkFTSUMgVjIgKioqKiAgICAgIBtbMG0KG1sxOzM3bRtbMTs0NG0gIDY0SyBSQU0gU1lTVEVNICAzODkxMSBCQVNJQyBCWVRFUyBGUkVFICAgG1swbQobWzE7MzdtG1sxOzQ0bSBSRUFEWS4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAbWzBtChtbMTszN20bWzE7NDRtIEZMVVNIIENBQ0hFICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIBtbMG0KG1sxOzM3bRtbMTs0NG0gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgG1swbQobWzE7MzdtG1sxOzQ0bSBPSywgRkxVU0hFRCBBTEwgQ0FDSEVTLiAgICAgICAgICAgICAgICAgICAbWzBtChtbMTszN20bWzE7NDRtIFJFQURZLiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIBtbMG0KG1sxOzM3bRtbMTs0NG0gG1sxOzQ3bSAbWzE7NDRtICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAbWzBtChtbMTszN20bWzE7NDRtICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIBtbMG0KG1sxOzM3bRtbMTs0NG0gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgG1swbQobWzE7MzdtG1sxOzQ0bSAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAbWzBtChtbMTszN20bWzE7NDRtICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIBtbMG0KG1sxOzM3bRtbMTs0NG0gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgG1swbQobWzE7MzdtG1sxOzQ0bSAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAbWzBtChtbMTszN20bWzE7NDRtICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIBtbMG0KG1sxOzM3bRtbMTs0NG0gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgG1swbQoK";
			$this->response->setOutputFormat(Response::OUTPUTFORMAT_RAW);
			$this->response->appendContent(base64_decode($content));
			if ($this->lockManager->isSiteLocked()) {
				$this->lockManager->unlockSite();
			}
			$this->sendAndExit(0);
		}
	}

	/**
	 * Signals that caches should be warmed up.
	 *
	 * Other application parts may subscribe to this signal and execute additional
	 * tasks for preparing the application for the first request.
	 *
	 * @return void
	 * @FLOW3\Signal
	 */
	public function emitWarmupCaches() {
	}

}

?>
