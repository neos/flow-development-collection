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
 * Global error handler for FLOW3
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ErrorHandler implements \F3\FLOW3\Error\ErrorHandlerInterface {

	/**
	 * @var array
	 */
	protected $exceptionalErrors = array();

	/**
	 * Constructs this error handler - registers itself as the default error handler.
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		set_error_handler(array($this, 'handleError'));
	}

	/**
	 * Defines which error levels result should result in an exception thrown.
	 *
	 * @param array $exceptionalErrors An array of E_* error levels
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setExceptionalErrors(array $exceptionalErrors) {
		$this->exceptionalErrors = $exceptionalErrors;
	}

	/**
	 * Handles an error by converting it into an exception
	 *
	 * @param integer $errorLevel The error level - one of the E_* constants
	 * @param string $errorMessage The error message
	 * @param string $errorFile Name of the file the error occurred in
	 * @param integer $errorLine Line number where the error occurred
	 * @return void
	 * @throws \F3\FLOW3\Error\Exception with the data passed to this method
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function handleError($errorLevel, $errorMessage, $errorFile, $errorLine) {
		$errorLevels = array (
			E_WARNING            => 'Warning',
			E_NOTICE             => 'Notice',
			E_USER_ERROR         => 'User Error',
			E_USER_WARNING       => 'User Warning',
			E_USER_NOTICE        => 'User Notice',
			E_STRICT             => 'Runtime Notice',
			E_RECOVERABLE_ERROR  => 'Catchable Fatal Error'
		);

		if (in_array($errorLevel, (array)$this->exceptionalErrors)) {
			if (class_exists('F3\FLOW3\Error\Exception')) {
				throw new \F3\FLOW3\Error\Exception($errorLevels[$errorLevel] . ': ' . $errorMessage . ' in ' . $errorFile . ' line ' . $errorLine, 1);
			} else {
				throw new \Exception($errorLevels[$errorLevel] . ': ' . $errorMessage . ' in ' . $errorFile . ' line ' . $errorLine, 1);
			}
		}
	}
}

?>