<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Log;

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
 * @subpackage Log
 * @version $Id$
 */

/**
 * An abstract Log backend
 *
 * @package FLOW3
 * @subpackage Log
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
abstract class AbstractBackend implements \F3\FLOW3\Log\BackendInterface {

	/**
	 * Constructs this log backend
	 *
	 * @param mixed $options Configuration options - depends on the actual backend
	 * @author Robert Lemke <robert@typo3.org>
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

}
?>