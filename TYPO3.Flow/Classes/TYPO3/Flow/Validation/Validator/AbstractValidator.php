<?php
namespace TYPO3\Flow\Validation\Validator;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
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
abstract class AbstractValidator implements ValidatorInterface {

	/**
	 * Specifies whether this validator accepts empty values.
	 *
	 * If this is TRUE, the validators isValid() method is not called in case of an empty value
	 * Note: A value is considered empty if it is NULL or an empty string!
	 * By default all validators except for NotEmpty and the Composite Validators accept empty values
	 *
	 * @var boolean
	 */
	protected $acceptsEmptyValues = TRUE;

	/**
	 * This contains the supported options, each being an array of:
	 *
	 * 0 => default value
	 * 1 => description
	 * 2 => type
	 * 3 => required (boolean, optional)
	 *
	 * @var array
	 */
	protected $supportedOptions = array();

	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * @var \TYPO3\Flow\Error\Result
	 */
	protected $result;

	/**
	 * Constructs the validator and sets validation options
	 *
	 * @param array $options Options for the validator
	 * @throws \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException if unsupported options are found
	 * @api
	 */
	public function __construct(array $options = array()) {
		// check for options given but not supported
		if (($unsupportedOptions = array_diff_key($options, $this->supportedOptions)) !== array()) {
			throw new \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException('Unsupported validation option(s) found: ' . implode(', ', array_keys($unsupportedOptions)), 1339079393);
		}

		// check for required options being set
		array_walk(
			$this->supportedOptions,
			function($supportedOptionData, $supportedOptionName, $options) {
				if (array_key_exists(3, $supportedOptionData) && $supportedOptionData[3] === TRUE && !array_key_exists($supportedOptionName, $options)) {
					throw new \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException('Required validation option not set: ' . $supportedOptionName, 1339163902);
				}
			},
			$options
		);

		// merge with default values
		$this->options = array_merge(
			array_map(
				function ($value) {
					return $value[0];
				},
				$this->supportedOptions
			),
			$options
		);
	}

	/**
	 * Checks if the given value is valid according to the validator, and returns
	 * the Error Messages object which occurred.
	 *
	 * @param mixed $value The value that should be validated
	 * @return \TYPO3\Flow\Error\Result
	 * @api
	 */
	public function validate($value) {
		$this->result = new \TYPO3\Flow\Error\Result();
		if ($this->acceptsEmptyValues === FALSE || $this->isEmpty($value) === FALSE) {
			$this->isValid($value);
		}
		return $this->result;
	}

	/**
	 * Check if $value is valid. If it is not valid, needs to add an error
	 * to Result.
	 *
	 * @param mixed $value
	 * @return void
	 * @throws \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException if invalid validation options have been specified in the constructor
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
		$this->result->addError(new \TYPO3\Flow\Validation\Error($message, $code, $arguments));
	}

	/**
	 * Returns the options of this validator
	 *
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * @param mixed $value
	 * @return boolean TRUE if the given $value is NULL or an empty string ('')
	 */
	final protected function isEmpty($value) {
		return $value === NULL || $value === '';
	}
}
