<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Error
 * @version $Id$
 */

/**
 * A quite exception handler which catches but ignores any exception.
 *
 * @package FLOW3
 * @subpackage Error
 * @version $Id$
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Error_ProductionExceptionHandler implements F3_FLOW3_Error_ExceptionHandlerInterface {

	/**
	 * Constructs this exception handler - registers itself as the default exception handler.
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		@set_exception_handler(array($this, 'handleException'));
	}

	/**
	 * Displays the given exception
	 *
	 * @param Exception $exception: The exception object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function handleException(Exception $exception) {
		switch (php_sapi_name()) {
			case 'cli' :
				$this->echoExceptionCLI($exception);
				break;
			default :
				$this->echoExceptionWeb($exception);
		}
	}

	/**
	 * Echoes an exception for the web.
	 *
	 * @param Exception $exception The exception
	 * @return void
	 */
	public function echoExceptionWeb(Exception $exception) {
		if (!headers_sent()) {
			header("HTTP/1.1 500 Internal Server Error");
		}
		echo ('<html><body><p>500 Internal Server Error</p></body></html>');
	}

	/**
	 * Echoes an exception for the command line.
	 *
	 * @param Exception $exception The exception
	 * @return void
	 */
	public function echoExceptionCLI(Exception $exception) {
		exit(1);
	}
}
?>