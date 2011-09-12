<?php
namespace TYPO3\FLOW3\Validation\Validator;

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
 * An abstract composite validator with consisting of other validators
 *
 * @api
 * @scope prototype
 */
abstract class AbstractCompositeValidator implements \TYPO3\FLOW3\Validation\Validator\ValidatorInterface, \Countable {

	/**
	 * @var \SplObjectStorage
	 */
	protected $validators;

	/**
	 * Constructs the validator conjunction
	 *
	 * @param array $validationOptions The validation options
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function __construct($validationOptions = array()) {
		$this->validators = new \SplObjectStorage();
	}

	/**
	 * Adds a new validator to the conjunction.
	 *
	 * @param \TYPO3\FLOW3\Validation\Validator\ValidatorInterface $validator The validator that should be added
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function addValidator(\TYPO3\FLOW3\Validation\Validator\ValidatorInterface $validator) {
		$this->validators->attach($validator);
	}

	/**
	 * Removes the specified validator.
	 *
	 * @param \TYPO3\FLOW3\Validation\Validator\ValidatorInterface $validator The validator to remove
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function removeValidator(\TYPO3\FLOW3\Validation\Validator\ValidatorInterface $validator) {
		if (!$this->validators->contains($validator)) throw new \TYPO3\FLOW3\Validation\Exception\NoSuchValidatorException('Cannot remove validator because its not in the conjunction.', 1207020177);
		$this->validators->detach($validator);
	}

	/**
	 * Returns the number of validators contained in this conjunction.
	 *
	 * @return integer The number of validators
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function count() {
		return count($this->validators);
	}
}

?>