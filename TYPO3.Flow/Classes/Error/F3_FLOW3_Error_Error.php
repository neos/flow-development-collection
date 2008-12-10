<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Error;

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
 * @subpackage Error
 */

/**
 * An object representation of a generic error. Subclass this to create
 * more specific errors if necessary.
 *
 * @package FLOW3
 * @subpackage Error
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Error {

	/**
	 * @var string The default (english) error message.
	 */
	protected $message = 'Unknown error';

	/**
	 * @var string The error code
	 */
	protected $code;

	/**
	 * Constructs this error
	 *
	 * @param string $message: An english error message which is used if no other error message can be resolved
	 * @param integer $code: A unique error code
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($message, $code) {
		$this->message = $message;
		$this->code = $code;
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