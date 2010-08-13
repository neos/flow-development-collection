<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Log;

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
 * The default "system logger" of the FLOW3 framework
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Logger implements \F3\FLOW3\Log\SystemLoggerInterface {

	/**
	 * @var \SplObjectStorage
	 */
	protected $backends;

	/**
	 * Constructs the logger
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		$this->backends = new \SplObjectStorage();
	}

	/**
	 * Adds the backend to which the logger sends the logging data
	 *
	 * @param \F3\FLOW3\Log\Backend\BackendInterface $backend A backend implementation
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function addBackend(\F3\FLOW3\Log\Backend\BackendInterface $backend) {
		$this->backends->attach($backend);
		$backend->open();
	}

	/**
	 * Runs the close() method of a backend and removes the backend
	 * from the logger.
	 *
	 * @param \F3\FLOW3\Log\Backend\BackendInterface $backend The backend to remove
	 * @return void
	 * @throws \F3\FLOW3\Log\Exception\NoSuchBackendException if the given backend is unknown to this logger
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function removeBackend(\F3\FLOW3\Log\Backend\BackendInterface $backend) {
		if (!$this->backends->contains($backend)) throw new \F3\FLOW3\Log\Exception\NoSuchBackendException('Backend is unknown to this logger.', 1229430381);
		$backend->close();
		$this->backends->detach($backend);
	}

	/**
	 * Writes the given message along with the additional information into the log.
	 *
	 * @param string $message The message to log
	 * @param integer $severity An integer value, one of the LOG_* constants
	 * @param mixed $additionalData A variable containing more information about the event to be logged
	 * @param string $packageKey Key of the package triggering the log (determined automatically if not specified)
	 * @param string $className Name of the class triggering the log (determined automatically if not specified)
	 * @param string $methodName Name of the method triggering the log (determined automatically if not specified)
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function log($message, $severity = LOG_INFO, $additionalData = NULL, $packageKey = NULL, $className = NULL, $methodName = NULL) {
		if ($packageKey === NULL) {
			$backtrace = debug_backtrace(FALSE);
			$className = isset($backtrace[1]['class']) ? $backtrace[1]['class'] : '';
			$methodName = isset($backtrace[1]['function']) ? $backtrace[1]['function'] : '';
			$explodedClassName = explode('\\', $className);
			$packageKey = isset($explodedClassName[1]) ? $explodedClassName[1] : '';
		}
		foreach ($this->backends as $backend) {
			$backend->append($message, $severity, $additionalData, $packageKey, $className, $methodName);
		}
	}

	/**
	 * Cleanly closes all registered backends before destructing this Logger
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function shutdownObject() {
		foreach ($this->backends as $backend) {
			$backend->close();
		}
	}
}
?>