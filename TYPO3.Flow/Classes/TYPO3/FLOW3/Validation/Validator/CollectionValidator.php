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

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A generic collection validator
 *
 * @api
 */
class CollectionValidator extends GenericObjectValidator {

	/**
	 * @var \TYPO3\FLOW3\Validation\ValidatorResolver
	 * @FLOW3\Inject
	 */
	protected $validatorResolver;

	/**
	 * Checks if the given value is valid according to the validator, and returns
	 * the Error Messages object which occurred.
	 *
	 * @param mixed $value The value that should be validated
	 * @return \TYPO3\FLOW3\Error\Result
	 * @api
	 */
	public function validate($value) {
		$this->result = new \TYPO3\FLOW3\Error\Result();

		if ($this->acceptsEmptyValues === FALSE || $this->isEmpty($value) === FALSE) {
			if ($value instanceof \Doctrine\ORM\PersistentCollection && !$value->isInitialized()) {
				return $this->result;
			} elseif ((is_object($value) && !\TYPO3\FLOW3\Utility\TypeHandling::isCollectionType(get_class($value))) && !is_array($value)) {
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
	 * Check if $value is valid. If it is not valid, needs to add an error
	 * to Result.
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

?>