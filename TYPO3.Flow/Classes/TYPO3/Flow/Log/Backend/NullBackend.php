<?php
namespace TYPO3\Flow\Log\Backend;

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
 * A backend which just ignores everything
 *
 * @api
 */
class NullBackend extends \TYPO3\Flow\Log\Backend\AbstractBackend {

	/**
	 * Does nothing
	 *
	 * @return void
	 * @api
	 */
	public function open() {}

	/**
	 * Ignores the call
	 *
	 * @param string $message The message to log
	 * @param integer $severity One of the LOG_* constants
	 * @param mixed $additionalData A variable containing more information about the event to be logged
	 * @param string $packageKey Key of the package triggering the log (determined automatically if not specified)
	 * @param string $className Name of the class triggering the log (determined automatically if not specified)
	 * @param string $methodName Name of the method triggering the log (determined automatically if not specified)
	 * @return void
	 * @api
	 */
	public function append($message, $severity = 1, $additionalData = NULL, $packageKey = NULL, $className = NULL, $methodName = NULL) {}

	/**
	 * Does nothing
	 *
	 * @return void
	 * @api
	 */
	public function close() {}

}
