<?php
namespace TYPO3\FLOW3\Persistence\Doctrine\Logging;

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
 * A SQL logger that logs to a FLOW3 logger.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class SqlLogger implements \Doctrine\DBAL\Logging\SQLLogger {

	/**
	 * @var \TYPO3\FLOW3\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * Logs a SQL statement to the system logger (DEBUG priority).
	 *
	 * @param string $sql The SQL to be executed
	 * @param array $params The SQL parameters
	 * @param array $types The SQL parameter types.
	 * @return void
	 */
	public function startQuery($sql, array $params = NULL, array $types = NULL) {
			// this is a safeguard for when no logger might be available...
		if ($this->logger !== NULL) {
			$this->logger->log($sql, LOG_DEBUG, array('params' => $params, 'types' => $types));
		}
	}

	/**
	 * @return void
	 */
	public function stopQuery() {}

}

?>