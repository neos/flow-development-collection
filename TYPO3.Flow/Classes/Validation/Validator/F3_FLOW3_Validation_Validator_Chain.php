<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Validation::Validator;

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
 * @subpackage Validation
 * @version $Id$
 */

/**
 * Validator to chain many validators
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Chain implements F3::FLOW3::Validation::ValidatorInterface {

	/**
	 * @var array
	 */
	protected $validators = array();

	/**
	 * Returns TRUE, if the given property ($propertyValue) is a valid.
	 * Any errors will be stored in the given errors object.
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $propertyValue The value that should be validated
	 * @return boolean TRUE if the value could be validated. FALSE if an error occured
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isValidProperty($propertyValue, F3::FLOW3::Validation::Errors &$errors) {

		$subjectIsValid = TRUE;

		foreach ($this->validators as $validator) {
			$subjectIsValid &= $validator->isValidProperty($propertyValue, $errors);
		}

		return (boolean)$subjectIsValid;
	}

	/**
	 * Adds a new validator to the chain. Returns the index of the chain entry.
	 *
	 * @param F3::FLOW3::Validation::ValidatorInterface $validator The validator that should be added
	 * @return integer The index of the new chain entry
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addValidator(F3::FLOW3::Validation::ValidatorInterface $validator) {
		$this->validators[] = $validator;
		return count($this->validators) - 1;
	}

	/**
	 * Returns the validator with the given index of the chain.
	 *
	 * @param integer $index The index of the validator that should be returned
	 * @return F3::FLOW3::Validation::ValidatorInterface The requested validator
	 * @throws F3::FLOW3::Validation::Exception::InvalidChainIndex
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getValidator($index) {
		if (!isset($this->validators[$index])) throw new F3::FLOW3::Validation::Exception::InvalidChainIndex('Invalid chain index.', 1207215864);
		return $this->validators[$index];
	}

	/**
	 * Removes the validator with the given index of the chain.
	 *
	 * @param integer $index The index of the validator that should be removed
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function removeValidator($index) {
		if (!isset($this->validators[$index])) throw new F3::FLOW3::Validation::Exception::InvalidChainIndex('Invalid chain index.', 1207020177);
		unset($this->validators[$index]);
	}
}

?>