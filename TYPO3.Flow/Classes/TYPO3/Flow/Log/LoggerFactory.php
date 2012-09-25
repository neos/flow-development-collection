<?php
namespace TYPO3\Flow\Log;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * The logger factory used to create logger instances.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class LoggerFactory {

	/**
	 * Factory method which creates the specified logger along with the specified backend(s).
	 *
	 * @param string $identifier An identifier for the logger
	 * @param string $loggerObjectName Object name of the log frontend
	 * @param mixed $backendObjectNames Object name (or array of object names) of the log backend(s)
	 * @param array $backendOptions (optional) Array of backend options. If more than one backend is specified, this is an array of array.
	 * @return \TYPO3\Flow\Log\LoggerInterface The created logger frontend
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