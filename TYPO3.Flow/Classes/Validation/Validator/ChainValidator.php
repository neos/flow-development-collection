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
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 */

/**
 * Validator to chain many validators
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class ChainValidator implements \F3\FLOW3\Validation\Validator\ValidatorInterface {

	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * @var array
	 */
	protected $validators = array();

	/**
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Checks if the given value is valid according to the validators of the chain..
	 *
	 * If at least one error occurred, the result is FALSE and any errors will
	 * be stored in the given errors object.
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE if the value is valid, FALSE if an error occured
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isValid($value) {
		$this->errors = array();
		$subjectIsValid = TRUE;
		foreach ($this->validators as $validator) {
			$subjectIsValid &= $validator->isValid($value);
			$this->errors = array_merge($this->errors, $validator->getErrors());
		}
		return (boolean)$subjectIsValid;
	}

	/**
	 * Sets options for the validator
	 *
	 * @param array $options Not used
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setOptions(array $options) {
	}

	/**
	 * Returns an array of errors which occurred during the last isValid() call.
	 *
	 * @return array An array of \F3\FLOW3\Validation\Error objects or an empty array if no errors occurred.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Adds a new validator to the chain. Returns the index of the chain entry.
	 *
	 * @param \F3\FLOW3\Validation\Validator\ValidatorInterface $validator The validator that should be added
	 * @return integer The index of the new chain entry
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addValidator(\F3\FLOW3\Validation\Validator\ValidatorInterface $validator) {
		$this->validators[] = $validator;
		return count($this->validators) - 1;
	}

	/**
	 * Returns the validator with the given index of the chain.
	 *
	 * @param integer $index The index of the validator that should be returned
	 * @return \F3\FLOW3\Validation\Validator\ValidatorInterface The requested validator
	 * @throws \F3\FLOW3\Validation\Exception\InvalidChainIndex
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getValidator($index) {
		if (!isset($this->validators[$index])) throw new \F3\FLOW3\Validation\Exception\InvalidChainIndex('Invalid chain index.', 1207215864);
		return $this->validators[$index];
	}

	/**
	 * Removes the validator with the given index of the chain.
	 *
	 * @param integer $index The index of the validator that should be removed
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function removeValidator($index) {
		if (!isset($this->validators[$index])) throw new \F3\FLOW3\Validation\Exception\InvalidChainIndex('Invalid chain index.', 1207020177);
		unset($this->validators[$index]);
	}
}

?>