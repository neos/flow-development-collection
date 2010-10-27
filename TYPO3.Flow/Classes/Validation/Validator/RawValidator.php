<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation\Validator;

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
 * A validator which accepts any input
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class RawValidator implements \F3\FLOW3\Validation\Validator\ValidatorInterface {

	/**
	 * Always returns TRUE
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function isValid($value) {
		return TRUE;
	}

	/**
	 * Sets options for the validator
	 *
	 * @param array $options Not used
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setOptions(array $options) {
	}

	/**
	 * Always returns an empty array.
	 *
	 * @return array An empty array.
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getErrors() {
		return array();
	}

}
?>