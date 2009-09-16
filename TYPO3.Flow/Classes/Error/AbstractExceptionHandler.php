<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Error;

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
 * An abstract exception handler
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class AbstractExceptionHandler implements ExceptionHandlerInterface {

	/**
	 * @var \F3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var \F3\FLOW3\Core\LockManager
	 */
	protected $lockManager;

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
	 * Injects the Lock Manager
	 *
	 * @param \F3\FLOW3\Core\LockManager $lockManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectLockManager(\F3\FLOW3\Core\LockManager $lockManager) {
		$this->lockManager = $lockManager;
	}

	/**
	 * Handles the given exception
	 *
	 * @param \Exception $exception The exception object
	 * @return void
	 */
	public function handleException(\Exception $exception) {
		if (is_object($this->systemLogger)) {
			$exceptionCodeNumber = ($exception->getCode() > 0) ? ' #' . $exception->getCode() : '';
			$backTrace = $exception->getTrace();
			$className = isset($backTrace[0]['class']) ? $backTrace[0]['class'] : '?';
			$methodName = isset($backTrace[0]['function']) ? $backTrace[0]['function'] : '?';
			$line = isset($backTrace['line']) ? ' in line ' . $backTrace['line'] : '';
			$message = 'Uncaught exception' . $exceptionCodeNumber . '. ' . $exception->getMessage() . $line . '.';

			$explodedClassName = explode('\\', $className);
			$packageKey = (isset($explodedClassName[1])) ? $explodedClassName[1] : NULL;

			$this->systemLogger->log($message, LOG_CRIT, array(), $packageKey, $className, $methodName);
		}

		if (is_object($this->lockManager)) {
			$this->lockManager->unlockSite();
		}
	}

}
?>