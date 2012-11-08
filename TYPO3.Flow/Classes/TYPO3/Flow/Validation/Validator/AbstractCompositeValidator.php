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
 * An abstract composite validator consisting of other validators
 *
 * @api
 */
abstract class AbstractCompositeValidator implements ObjectValidatorInterface, \Countable {

	/**
	 * This contains the supported options, their default values and descriptions.
	 *
	 * @var array
	 */
	protected $supportedOptions = array();

	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * @var \SplObjectStorage
	 */
	protected $validators;

	/**
	 * @var \SplObjectStorage
	 */
	protected $validatedInstancesContainer;

	/**
	 * Constructs the composite validator and sets validation options
	 *
	 * @param array $options Options for the validator
	 * @api
	 */
	public function __construct(array $options = array()) {
			// check for options given but not supported
		if (($unsupportedOptions = array_diff_key($options, $this->supportedOptions)) !== array()) {
			throw new \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException('Unsupported validation option(s) found: ' . implode(', ', array_keys($unsupportedOptions)), 1339079804);
		}

			// check for required options being set
		array_walk(
			$this->supportedOptions,
			function($supportedOptionData, $supportedOptionName, $options) {
				if (isset($supportedOptionData[3]) && !array_key_exists($supportedOptionName, $options)) {
					throw new \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException('Required validation option not set: ' . $supportedOptionName, 1339163922);
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
		$this->validators = new \SplObjectStorage();
	}

	/**
	 * Allows to set a container to keep track of validated instances.
	 *
	 * @param \SplObjectStorage $validatedInstancesContainer A container to keep track of validated instances
	 * @return void
	 * @api
	 */
	public function setValidatedInstancesContainer(\SplObjectStorage $validatedInstancesContainer) {
		$this->validatedInstancesContainer = $validatedInstancesContainer;
	}

	/**
	 * Adds a new validator to the conjunction.
	 *
	 * @param \TYPO3\Flow\Validation\Validator\ValidatorInterface $validator The validator that should be added
	 * @return void
	 * @api
	 */
	public function addValidator(\TYPO3\Flow\Validation\Validator\ValidatorInterface $validator) {
		if ($validator instanceof ObjectValidatorInterface) {
			$validator->setValidatedInstancesContainer = $this->validatedInstancesContainer;
		}
		$this->validators->attach($validator);
	}

	/**
	 * Removes the specified validator.
	 *
	 * @param \TYPO3\Flow\Validation\Validator\ValidatorInterface $validator The validator to remove
	 * @throws \TYPO3\Flow\Validation\Exception\NoSuchValidatorException
	 * @api
	 */
	public function removeValidator(\TYPO3\Flow\Validation\Validator\ValidatorInterface $validator) {
		if (!$this->validators->contains($validator)) throw new \TYPO3\Flow\Validation\Exception\NoSuchValidatorException('Cannot remove validator because its not in the conjunction.', 1207020177);
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