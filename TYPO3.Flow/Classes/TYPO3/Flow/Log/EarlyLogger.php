<?php
namespace TYPO3\Flow\Log;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Early logger for logging things that happens earlier in the bootstrap than setup of system logger and dependency
 * injection
 *
 * @api
 */
class EarlyLogger implements SystemLoggerInterface {

	/**
	 * @var array
	 */
	protected $logEntries = array();

	/**
	 * @var array
	 */
	protected $exceptions = array();

	/**
	 * Adds a backend to which the logger sends the logging data
	 *
	 * @param \TYPO3\Flow\Log\Backend\BackendInterface $backend A backend implementation
	 * @return void
	 * @api
	 */
	public function addBackend(\TYPO3\Flow\Log\Backend\BackendInterface $backend) {
		$this->log('Method "addBackend" called on object earlyLogger. Not supported, silently ignoring.');
	}

	/**
	 * Runs the close() method of a backend and removes the backend
	 * from the logger.
	 *
	 * @param \TYPO3\Flow\Log\Backend\BackendInterface $backend The backend to remove
	 * @return void
	 * @api
	 */
	public function removeBackend(\TYPO3\Flow\Log\Backend\BackendInterface $backend) {
		$this->log('Method "removeBackend" called on object earlyLogger. Not supported, silently ignoring');
	}

	/**
	 * Resets internal log arrays
	 *
	 * @return void
	 */
	protected function resetInternalLogs() {
		$this->logEntries = array();
		$this->exceptions = array();
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
	 * @api
	 */
	public function log($message, $severity = LOG_INFO, $additionalData = NULL, $packageKey = NULL, $className = NULL, $methodName = NULL) {
		$this->logEntries[] = func_get_args();
	}

	/**
	 * Writes information about the given exception into the log.
	 *
	 * @param \Exception $exception The exception to log
	 * @param array $additionalData Additional data to log
	 * @return void
	 * @api
	 */
	public function logException(\Exception $exception, array $additionalData = array()) {
		$this->exceptions[] = func_get_args();
	}

	/**
	 * Replays internal logs on provided logger. Use to transfer early logs to real logger when available.
	 *
	 * @see \TYPO3\Flow\Package\PackageManager
	 *
	 * @param SystemLoggerInterface $logger
	 * @param boolean $resetLogs
	 * @return SystemLoggerInterface
	 */
	public function replayLogsOn(SystemLoggerInterface $logger, $resetLogs = TRUE) {
		if (count($this->logEntries) > 0) {
			$logger->log('[Replaying logs from instance of EarlyLogger. Order of internal log-entries is maintained, but other log-entries might not be in order.]');
			foreach ($this->logEntries as $logEntry) {
				call_user_func_array(array($logger, 'log'), $logEntry);
			}
			$logger->log('[Done replaying logs from instance of EarlyLogger.]');
		}
		if (count($this->exceptions) > 0) {
			foreach ($this->logEntries as $logEntry) {
				call_user_func_array(array($logger, 'logException'), $logEntry);
			}
		}
		if ($resetLogs === TRUE) {
			$this->resetInternalLogs();
		}

		return $logger;
	}
}

?>