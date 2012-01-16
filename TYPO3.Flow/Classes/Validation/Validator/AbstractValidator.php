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
 * Abstract validator
 *
 * @api
 */
abstract class AbstractValidator implements \TYPO3\FLOW3\Validation\Validator\ValidatorInterface {

	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * @var \TYPO3\FLOW3\Error\Result
	 */
	protected $result;

	/**
	 * Constructs the validator and sets validation options
	 *
	 * @param array $options Options for the validator
	 * @api
	 */
	public function __construct(array $options = array()) {
		$this->options = $options;
	}

	/**
	 * Checks if the given value is valid according to the validator, and returns
	 * the Error Messages object which occured.
	 *
	 * @param mixed $value The value that should be validated
	 * @return \TYPO3\FLOW3\Error\Result
	 * @api
	 */
	public function validate($value) {
		$this->result = new \TYPO3\FLOW3\Error\Result();
		$this->isValid($value);
		return $this->result;
	}

	/**
	 * Check if $value is valid. If it is not valid, needs to add an error
	 * to Result.
	 *
	 * @return void
	 */
	abstract protected function isValid($value);

	/**
	 * Creates a new validation error object and adds it to $this->errors
	 *
	 * @param string $message The error message
	 * @param integer $code The error code (a unix timestamp)
	 * @param array $arguments Arguments to be replaced in message
	 * @return void
	 * @api
	 */
	protected function addError($message, $code, array $arguments = array()) {
		$this->result->addError(new \TYPO3\FLOW3\Validation\Error($message, $code, $arguments));
	}

	/**
	 * Returns the options of this validator
	 *
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}
}

?>