<?php
namespace TYPO3\FLOW3\Validation\Validator;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * An abstract composite validator with consisting of other validators
 *
 * @api
 */
abstract class AbstractCompositeValidator implements \TYPO3\FLOW3\Validation\Validator\ValidatorInterface, \Countable {

	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * @var \SplObjectStorage
	 */
	protected $validators;

	/**
	 * Constructs the composite validator and sets validation options
	 *
	 * @param array $options Options for the validator
	 * @api
	 */
	public function __construct(array $options = array()) {
		$this->options = $options;
		$this->validators = new \SplObjectStorage();
	}

	/**
	 * Adds a new validator to the conjunction.
	 *
	 * @param \TYPO3\FLOW3\Validation\Validator\ValidatorInterface $validator The validator that should be added
	 * @return void
	 * @api
	 */
	public function addValidator(\TYPO3\FLOW3\Validation\Validator\ValidatorInterface $validator) {
		$this->validators->attach($validator);
	}

	/**
	 * Removes the specified validator.
	 *
	 * @param \TYPO3\FLOW3\Validation\Validator\ValidatorInterface $validator The validator to remove
	 * @throws \TYPO3\FLOW3\Validation\Exception\NoSuchValidatorException
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
	 * @api
	 */
	public function count() {
		return count($this->validators);
	}

	/**
	 * Returns the child validators of this Composite Validator
	 *
	 * @return \SplObjectStorage
	 */
	public function getValidators() {
		return $this->validators;
	}

	/**
	 * Returns the options for this validator
	 *
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}
}

?>