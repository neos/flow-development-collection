<?php
namespace TYPO3\FLOW3\Log;

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
 * The logger factory used to create logger instances.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope singleton
 */
class LoggerFactory {

	/**
	 * Factory method which creates the specified logger along with the specified backend(s).
	 *
	 * @param string $identifier An identifier for the logger
	 * @param string $loggerObjectName Object name of the log frontend
	 * @param mixed $backendObjectNames Object name (or array of object names) of the log backend(s)
	 * @param array $backendOptions (optional) Array of backend options. If more than one backend is specified, this is an array of array.
	 * @return \TYPO3\FLOW3\Log\LoggerInterface The created logger frontend
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	static public function create($identifier, $loggerObjectName, $backendObjectNames, array $backendOptions = array()) {
		$logger = new $loggerObjectName;

		if (is_array($backendObjectNames)) {
			foreach ($backendObjectNames as $i => $backendObjectName) {
				if (isset($backendOptions[$i])) {
					$backend = new $backendObjectName($backendOptions[$i]);
					$logger->addBackend($backend);
				}
			}
		} else {
			$backend = new $backendObjectNames($backendOptions);
			$logger->addBackend($backend);
		}
		return $logger;
	}

}
?>