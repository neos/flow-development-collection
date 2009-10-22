<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Core;

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
 * The Lock Manager controls the master lock of the whole site which is mainly
 * used to regenerate code caches in peace.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class LockManager {

	const LOCKFILE_MAXIMUM_AGE = 60;

	/**
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var \F3\FLOW3\Log\SystemLoggerInterface
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
	 * @param \F3\FLOW3\Utility\Environment $environment
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectEnvironment(\F3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Injects the system logger
	 *
	 * @param \F3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSystemLogger(\F3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Initializes the manager
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function isSiteLocked() {
		return $this->siteLocked;
	}

	/**
	 * Locks the site for further requests.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function lockSite() {
		$this->systemLogger->log('Locking site. Lock file: ' . $this->lockPathAndFilename, LOG_NOTICE);
		$this->siteLocked = TRUE;
		file_put_contents($this->lockPathAndFilename, '');
	}

	/**
	 * Unlocks the site if this request has locked it.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
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