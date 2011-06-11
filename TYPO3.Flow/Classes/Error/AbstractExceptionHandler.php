<?php
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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class AbstractExceptionHandler implements ExceptionHandlerInterface {

	/**
	 * @var \F3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

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
	 * Handles the given exception
	 *
	 * @param \Exception $exception The exception object
	 * @return void
	 */
	public function handleException(\Exception $exception) {
		if (is_object($this->systemLogger)) {
			$this->systemLogger->logException($exception);
		}

		switch (PHP_SAPI) {
			case 'cli' :
				$this->echoExceptionCli($exception);
				break;
			default :
				$this->echoExceptionWeb($exception);
		}
	}

	/**
	 * Echoes an exception for the command line.
	 *
	 * @param \Exception $exception The exception
	 * @return void
	 */
	abstract protected function echoExceptionCli(\Exception $exception);

	/**
	 * Echoes an exception for the web.
	 *
	 * @param \Exception $exception The exception
	 * @return void
	 */
	abstract protected function echoExceptionWeb(\Exception $exception);
}
?>