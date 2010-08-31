<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Log\Backend;

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
 * An abstract Log backend
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
abstract class AbstractBackend implements \F3\FLOW3\Log\Backend\BackendInterface {

	/**
	 * One of the LOG_* constants. Anything below that will be filtered out.
	 * @var integer
	 */
	protected $severityThreshold = LOG_INFO;

	/**
	 * Flag telling if the IP address of the current client (if available) should be logged.
	 * @var boolean
	 */
	protected $logIpAddress = FALSE;

	/**
	 * Constructs this log backend
	 *
	 * @param mixed $options Configuration options - depends on the actual backend
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function __construct($options = array()) {
		if (is_array($options) || $options instanceof ArrayAccess) {
			foreach ($options as $optionKey => $optionValue) {
				$methodName = 'set' . ucfirst($optionKey);
				if (method_exists($this, $methodName)) {
					$this->$methodName($optionValue);
				}
			}
		}
	}

	/**
	 * The maximum severity to log, anything less severe will not be logged.
	 *
	 * @param integer $severityThreshold One of the LOG_* constants
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function setSeverityThreshold($severityThreshold) {
		$this->severityThreshold = $severityThreshold;
	}

	/**
	 * Enables or disables logging of IP addresses.
	 *
	 * @param boolean $logIpAddress Set to TRUE to enable logging of IP address, or FALSE to disable
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setLogIpAddress($logIpAddress) {
		$this->logIpAddress = $logIpAddress;
	}

}
?>