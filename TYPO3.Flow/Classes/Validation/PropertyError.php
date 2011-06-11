<?php
namespace F3\FLOW3\Validation;

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
 * This object holds validation errors for one property.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class PropertyError extends \F3\FLOW3\Validation\Error {

	/**
	 * @var string
	 */
	protected $message = '"%s": ';

	/**
	 * @var string
	 */
	protected $code = 1242859509;

	/**
	 * @var string
	 */
	protected $propertyName;

	/**
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Create a new property error with the given property name
	 *
	 * @param string $propertyName The property name
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	public function __construct($propertyName) {
		$this->propertyName = $propertyName;
		$this->message = sprintf($this->message, $propertyName);
	}

	/**
	 * Add errors
	 *
	 * @param array $errors Array of \F3\FLOW3\Validation\Error for the property
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	public function addErrors($errors) {
		$this->errors = array_merge($this->errors, $errors);
	}

	/**
	 * Get all errors for the property
	 *
	 * @return array An array of \F3\FLOW3\Validation\Error objects or an empty array if no errors occured for the property
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Get the property name
	 * @return string The property name for this error
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	public function getPropertyName() {
		return $this->propertyName;
	}
}

?>