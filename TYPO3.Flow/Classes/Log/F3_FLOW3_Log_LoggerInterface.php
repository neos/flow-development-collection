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
 * @package FLOW3
 * @subpackage Log
 * @version $Id$
 */

/**
 * Contract for a basic logger interface
 *
 * @package FLOW3
 * @subpackage Log
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 * @author Robert Lemke <robert@typo3.org>
 * @scope prototype
 */
interface LoggerInterface {

	const SEVERITY_DEBUG = -1;
	const SEVERITY_OK = 0;
	const SEVERITY_INFO = 1;
	const SEVERITY_NOTICE = 2;
	const SEVERITY_WARNING = 3;
	const SEVERITY_FATAL = 4;

	/**
	 * Adds a backend to which the logger sends the logging data
	 *
	 * @param BackendInterface $backend A backend implementation
	 * @return void
	 */
	public function addBackend(\F3\FLOW3\Log\BackendInterface $backend);

	/**
	 * Runs the close() method of a backend and removes the backend
	 * from the logger.
	 *
	 * @param BackendInterface $backend The backend to remove
	 * @return void
	 * @throws \F3\FLOW3\Log\Exception\NoSuchBackend if the given backend is unknown to this logger
	 */
	public function removeBackend(\F3\FLOW3\Log\BackendInterface $backend);

	/**
	 * Writes the given message along with the additional information into the log.
	 *
	 * @param string $message The message to log
	 * @param integer $severity An integer value: -1 (debug), 0 (ok), 1 (info), 2 (notice), 3 (warning), or 4 (fatal)
	 * @param mixed $additionalData A variable containing more information about the event to be logged
	 * @param string $packageKey Key of the package triggering the log (determined automatically if not specified)
	 * @param string $className Name of the class triggering the log (determined automatically if not specified)
	 * @param string $methodName Name of the method triggering the log (determined automatically if not specified)
	 * @return void
	 */
	public function log($message, $severity = 1, $additionalData = NULL, $packageKey = NULL, $className = NULL, $methodName = NULL);

}
?>