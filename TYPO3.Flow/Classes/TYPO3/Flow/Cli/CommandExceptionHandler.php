<?php
namespace TYPO3\FLOW3\Cli;

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
 * An Exception handler exclusively responsible for Exceptions that occur during command controller invocation
 *
 * @FLOW3\Scope("singleton")
 */
class CommandExceptionHandler extends \TYPO3\FLOW3\Error\AbstractExceptionHandler {

	/**
	 * Dummy method to satisfy the parent abstract class
	 *
	 * @param \Exception $exception The exception
	 *
	 * @return void
	 */
	protected function echoExceptionWeb(\Exception $exception) {
	}

	/**
	 * Displays a human readable, partly beautified version of the given exception
	 * and stops the application, return a non-zero exit code.
	 *
	 * @param \Exception $exception
	 * @return void
	 */
	protected function echoExceptionCli(\Exception $exception) {
		self::writeResponseAndExit($exception);
	}

	/**
	 * Displays a human readable, partly beautified version of the given exception
	 * and stops the application, return a non-zero exit code.
	 *
	 * @static
	 * @param \Exception $exception
	 * @return void
	 */
	public static function writeResponseAndExit(\Exception $exception) {
		$response = new Response();

		$exceptionMessage = '';
		$exceptionReference = "\n<b>More Information</b>\n";
		$exceptionReference .= "  Exception code      #" . $exception->getCode() . "\n";
		$exceptionReference .= "  File                " . $exception->getFile() . ($exception->getLine() ? ' line ' . $exception->getLine() : '') . "\n";
		$exceptionReference .= ($exception instanceof \TYPO3\FLOW3\Exception ? "  Exception reference #" . $exception->getReferenceCode() . "\n" : '');
		foreach (explode(chr(10), wordwrap($exception->getMessage(), 73)) as $messageLine) {
			 $exceptionMessage .= "  $messageLine\n";
		}

		$response->setContent(sprintf("<b>Uncaught Exception</b>\n%s%s\n", $exceptionMessage, $exceptionReference));
		$response->send();
		exit(1);
	}
}

?>