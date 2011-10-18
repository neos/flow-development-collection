<?php
namespace TYPO3\FLOW3\Log;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * The default logger of the FLOW3 framework
 *
 * @api
 */
class Logger implements \TYPO3\FLOW3\Log\SystemLoggerInterface, \TYPO3\FLOW3\Log\SecurityLoggerInterface {

	/**
	 * @var \SplObjectStorage
	 */
	protected $backends;

	/**
	 * Constructs the logger
	 *
	 */
	public function __construct() {
		$this->backends = new \SplObjectStorage();
	}

	/**
	 * Sets the given backend as the only backend for this Logger.
	 *
	 * This method allows for conveniently injecting a backend through some Objects.yaml configuration.
	 *
	 * @param \TYPO3\FLOW3\Log\Backend\BackendInterface $backend A backend implementation
	 * @return void
	 * @api
	 */
	public function setBackend(\TYPO3\FLOW3\Log\Backend\BackendInterface $backend) {
		$this->backends = new \SplObjectStorage();
		$this->backends->attach($backend);
	}

	/**
	 * Adds the backend to which the logger sends the logging data
	 *
	 * @param \TYPO3\FLOW3\Log\Backend\BackendInterface $backend A backend implementation
	 * @return void
	 * @api
	 */
	public function addBackend(\TYPO3\FLOW3\Log\Backend\BackendInterface $backend) {
		$this->backends->attach($backend);
		$backend->open();
	}

	/**
	 * Runs the close() method of a backend and removes the backend
	 * from the logger.
	 *
	 * @param \TYPO3\FLOW3\Log\Backend\BackendInterface $backend The backend to remove
	 * @return void
	 * @throws \TYPO3\FLOW3\Log\Exception\NoSuchBackendException if the given backend is unknown to this logger
	 * @api
	 */
	public function removeBackend(\TYPO3\FLOW3\Log\Backend\BackendInterface $backend) {
		if (!$this->backends->contains($backend)) throw new \TYPO3\FLOW3\Log\Exception\NoSuchBackendException('Backend is unknown to this logger.', 1229430381);
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
	 * @api
	 */
	public function log($message, $severity = LOG_INFO, $additionalData = NULL, $packageKey = NULL, $className = NULL, $methodName = NULL) {
		if ($packageKey === NULL) {
			$backtrace = debug_backtrace(FALSE);
			$className = isset($backtrace[1]['class']) ? $backtrace[1]['class'] : NULL;
			$methodName = isset($backtrace[1]['function']) ? $backtrace[1]['function'] : NULL;
			$explodedClassName = explode('\\', $className);
				// FIXME: This is not really the package key:
			$packageKey = isset($explodedClassName[1]) ? $explodedClassName[1] : '';
		}
		foreach ($this->backends as $backend) {
			$backend->append($message, $severity, $additionalData, $packageKey, $className, $methodName);
		}
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
		$backTrace = $exception->getTrace();
		$className = isset($backTrace[0]['class']) ? $backTrace[0]['class'] : '?';
		$methodName = isset($backTrace[0]['function']) ? $backTrace[0]['function'] : '?';
		$message = $this->getExceptionLogMessage($exception);

		if ($exception->getPrevious() !== NULL) {
			$additionalData['previousException'] = $this->getExceptionLogMessage($exception->getPrevious());
		}

		$explodedClassName = explode('\\', $className);
			// FIXME: This is not really the package key:
		$packageKey = (isset($explodedClassName[1])) ? $explodedClassName[1] : NULL;

		if (!file_exists(FLOW3_PATH_DATA . 'Logs/Exceptions')) {
			mkdir(FLOW3_PATH_DATA . 'Logs/Exceptions');
		}
		if (file_exists(FLOW3_PATH_DATA . 'Logs/Exceptions') && is_dir(FLOW3_PATH_DATA . 'Logs/Exceptions') && is_writable(FLOW3_PATH_DATA . 'Logs/Exceptions')) {
			$referenceCode = ($exception instanceof \TYPO3\FLOW3\Exception) ? $exception->getReferenceCode() : date('YmdHis' , $_SERVER['REQUEST_TIME']) . substr(md5(rand()), 0, 6);
			$exceptionDumpPathAndFilename = FLOW3_PATH_DATA . 'Logs/Exceptions/' . $referenceCode . '.txt';
			file_put_contents($exceptionDumpPathAndFilename, $message . PHP_EOL . PHP_EOL . \TYPO3\FLOW3\Error\Debugger::getBacktraceCode($backTrace, FALSE, TRUE));
			$message .= ' - See also: ' . basename($exceptionDumpPathAndFilename);
		} else {
			$this->log(sprintf('Could not write exception backtrace into %s because the directory could not be created or is not writable.', FLOW3_PATH_DATA . 'Logs/Exceptions/'), LOG_WARNING, array(), 'FLOW3', __CLASS__, __FUNCTION__);
		}

		$this->log($message, LOG_CRIT, $additionalData, $packageKey, $className, $methodName);
	}

	/**
	 * @param \Exception $exception
	 * @return string
	 */
	protected function getExceptionLogMessage(\Exception $exception) {
		$exceptionCodeNumber = ($exception->getCode() > 0) ? ' #' . $exception->getCode() : '';
		$backTrace = $exception->getTrace();
		$line = isset($backTrace[0]['line']) ? ' in line ' . $backTrace[0]['line'] . ' of ' . $backTrace[0]['file'] : '';
		return 'Uncaught exception' . $exceptionCodeNumber . $line . ': ' . $exception->getMessage() ;
	}

	/**
	 * Cleanly closes all registered backends before destructing this Logger
	 *
	 * @return void
	 */
	public function shutdownObject() {
		foreach ($this->backends as $backend) {
			$backend->close();
		}
	}
}
?>