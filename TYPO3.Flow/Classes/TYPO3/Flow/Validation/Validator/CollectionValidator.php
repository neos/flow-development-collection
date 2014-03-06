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

use TYPO3\Flow\Annotations as Flow;

/**
 * A generic collection validator.
 *
 * @api
 */
class CollectionValidator extends GenericObjectValidator {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'elementValidator' => array(NULL, 'The validator type to use for the collection elements', 'string'),
		'elementType' => array(NULL, 'The type of the elements in the collection', 'string'),
		'validationGroups' => array(NULL, 'The validation groups to link to', 'string'),
	);

	/**
	 * @var \TYPO3\Flow\Validation\ValidatorResolver
	 * @Flow\Inject
	 */
	protected $validatorResolver;

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
			if ($value instanceof \Doctrine\ORM\PersistentCollection && !$value->isInitialized()) {
				return $this->result;
			} elseif ((is_object($value) && !\TYPO3\Flow\Utility\TypeHandling::isCollectionType(get_class($value))) && !is_array($value)) {
				$this->addError('The given subject was not a collection.', 1317204797);
				return $this->result;
			} elseif (is_object($value) && $this->isValidatedAlready($value)) {
				return $this->result;
			} else {
				$this->isValid($value);
			}
		}
		return $this->result;
	}

	/**
	 * Checks for a collection and if needed validates the items in the collection.
	 * This is done with the specified element validator or a validator based on
	 * the given element type and validation group.
	 *
	 * Either elementValidator or elementType must be given, otherwise validation
	 * will be skipped.
	 *
	 * @param mixed $value A collection to be validated
	 * @return void
	 */
	protected function isValid($value) {
		foreach ($value as $index => $collectionElement) {
			if (isset($this->options['elementValidator'])) {
				$collectionElementValidator = $this->validatorResolver->createValidator($this->options['elementValidator']);
			} elseif (isset($this->options['elementType'])) {
				if (isset($this->options['validationGroups'])) {
					$collectionElementValidator = $this->validatorResolver->getBaseValidatorConjunction($this->options['elementType'], $this->options['validationGroups']);
				} else {
					$collectionElementValidator = $this->validatorResolver->getBaseValidatorConjunction($this->options['elementType']);
				}
			} else {
				return;
			}
			if ($collectionElementValidator instanceof ObjectValidatorInterface) {
				$collectionElementValidator->setValidatedInstancesContainer($this->validatedInstancesContainer);
			}
			$this->result->forProperty($index)->merge($collectionElementValidator->validate($collectionElement));
		}
	}
}
