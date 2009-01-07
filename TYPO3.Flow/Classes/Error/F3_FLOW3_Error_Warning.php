<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Error;

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
 * @subpackage Error
 */

/**
 * An object representation of a generic warning. Subclass this to create
 * more specific warnings if necessary.
 *
 * @package FLOW3
 * @subpackage Error
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class Warning {

	/**
	 * @var string The default (english) error message.
	 */
	protected $message = 'Unknown warning';

	/**
	 * @var string The error code
	 */
	protected $code;

	/**
	 * Constructs this warning
	 *
	 * @param string $message: An english error message which is used if no other error message can be resolved
	 * @param integer $code: A unique error code
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($message, $code) {
		$this->message = $message;
		$this->code = 0;
	}

	/**
	 * Returns the error message
	 * @return string The error message
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getErrorMessage() {
		return $this->message;
	}

	/**
	 * Returns the error code
	 * @return string The error code
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getErrorCode() {
		return $this->code;
	}
}

?>