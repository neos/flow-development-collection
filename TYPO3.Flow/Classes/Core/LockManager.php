<?php
namespace TYPO3\FLOW3\Core;

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

/**
 * The Lock Manager controls the master lock of the whole site which is mainly
 * used to regenerate code caches in peace.
 *
 * @FLOW3\Scope("singleton")
 */
class LockManager {

	const LOCKFILE_MAXIMUM_AGE = 90;

	/**
	 * @var \TYPO3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var \TYPO3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var string
	 */
	protected $lockPathAndFilename;

	/**
	 * @var boolean
	 */
	protected $siteLocked = FALSE;

	/**
	 * Injects the environment utility
	 *
	 * @param \TYPO3\FLOW3\Utility\Environment $environment
	 * @return void
	 */
	public function injectEnvironment(\TYPO3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Injects the system logger
	 *
	 * @param \TYPO3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 */
	public function injectSystemLogger(\TYPO3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Initializes the manager
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->lockPathAndFilename = $this->environment->getPathToTemporaryDirectory() . 'FLOW3.lock';
		if (file_exists($this->lockPathAndFilename)) {
			if (filemtime($this->lockPathAndFilename) < (time() - self::LOCKFILE_MAXIMUM_AGE)) {
				unlink($this->lockPathAndFilename);;
			} else {
				$this->siteLocked = TRUE;
			}
		}
	}

	/**
	 * Tells if the site is currently locked
	 *
	 * @return boolean
	 * @api
	 */
	public function isSiteLocked() {
		return $this->siteLocked;
	}

	/**
	 * Exits if the site is currently locked
	 *
	 * @return void
	 */
	public function exitIfSiteLocked() {
		if ($this->isSiteLocked() === TRUE) {
			if (FLOW3_SAPITYPE === 'Web') {
				header('HTTP/1.1 503 Service Temporarily Unavailable');
				readfile(FLOW3_PATH_FLOW3 . 'Resources/Private/Core/LockHoldingStackPage.html');
			} else {
				echo "Site is currently locked, exiting.\n";
			}
			$this->systemLogger->log('Site is locked, exiting.', LOG_NOTICE);
			exit(1);
		}
	}

	/**
	 * Locks the site for further requests.
	 *
	 * @return void
	 * @api
	 */
	public function lockSiteOrExit() {
		$this->exitIfSiteLocked();
		$this->systemLogger->log('Locking site. Lock file: ' . $this->lockPathAndFilename, LOG_NOTICE);
		$this->siteLocked = TRUE;
		file_put_contents($this->lockPathAndFilename, '');
	}

	/**
	 * Unlocks the site if this request has locked it.
	 *
	 * @return void
	 * @api
	 */
	public function unlockSite() {
		if ($this->siteLocked === TRUE) {
			if (file_exists($this->lockPathAndFilename)) {
				unlink($this->lockPathAndFilename);
			} else {
				$this->systemLogger->log('Site is locked but no lockfile could be found.', LOG_WARNING);
			}
			$this->siteLocked = FALSE;
			$this->systemLogger->log('Unlocked site.', LOG_NOTICE);
		}
	}
}
?>