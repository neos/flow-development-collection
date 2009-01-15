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

	/**
	 * Severities according to RFC3164
	 *
	 * @see http://www.faqs.org/rfcs/rfc3164.html
	 */
	const SEVERITY_EMERGENCY = LOG_EMERG; # Emergency: system is unusable
	const SEVERITY_ALERT = LOG_ALERT;     # Alert: action must be taken immediately
	const SEVERITY_CRITICAL = LOG_CRIT;   # Critical: critical conditions
	const SEVERITY_ERROR = LOG_ERR;       # Error: error conditions
	const SEVERITY_WARNING = LOG_WARNING; # Warning: warning conditions
	const SEVERITY_NOTICE = LOG_NOTICE;   # Notice: normal but significant condition
	const SEVERITY_INFO = LOG_INFO;       # Informational: informational messages
	const SEVERITY_DEBUG = LOG_DEBUG;     # Debug: debug-level messages

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
	 * @param integer $severity An integer value, one of the SEVERITY_* constants
	 * @param mixed $additionalData A variable containing more information about the event to be logged
	 * @param string $packageKey Key of the package triggering the log (determined automatically if not specified)
	 * @param string $className Name of the class triggering the log (determined automatically if not specified)
	 * @param string $methodName Name of the method triggering the log (determined automatically if not specified)
	 * @return void
	 */
	public function log($message, $severity = 6, $additionalData = NULL, $packageKey = NULL, $className = NULL, $methodName = NULL);

}
?>