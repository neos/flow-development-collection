<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Property;

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
 * @subpackage Property
 * @version $Id:\F3\FLOW3\Property\Mapper.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * The Property Mapper maps properties onto a given target object, often a (domain-) model.
 * Which properties are bound, required and how they should be filtered can be customized.
 * During the mapping process, the property values are validated and the result of this
 * validation can be queried.
 *
 * The following code would map the property of the source array to the target:
 *
 * $target = new ArrayObject();
 * $source = new ArrayObject(
 *    array(
 *       'someProperty' => 'SomeValue'
 *    )
 * );
 * $mapper = $objectFactory->create('F3\FLOW3\Property\Mapper');
 * $mapper->setTarget($target);
 * $mapper->map($source);
 *
 * Now the target object equals the source object.
 *
 * @package FLOW3
 * @subpackage Property
 * @version $Id:\F3\FLOW3\Property\Mapper.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Mapper {

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface The object factory
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Validation\ValidatorResolver The validator resolver
	 */
	protected $validatorResolver;

	/**
	 * @var object Target object
	 */
	protected $target;

	/**
	 * @var object Original target object to to work on a copy and be able to restore it when mapping in "only write on no errors" mode
	 */
	protected $originalTarget;

	/**
	 * @var boolean If TRUE the mapper only writes the target object, if no error occured
	 */
	protected $onlyWriteOnNoErrors = FALSE;

	/**
	 * @var \F3\FLOW3\Property\MappingResult Result of the last data mapping
	 */
	protected $mappingResults = NULL;

	/**
	 * @var array An array of allowed property names (or regular expressions matching those)
	 */
	protected $allowedProperties = array('.*');

	/**
	 * @var array An array of names of the required properties (or regular expressions matching those)
	 */
	protected $requiredProperties = array();

	/**
	 * @var \F3\FLOW3\Validation\ValidatorInterface A validator to validate the target object
	 */
	protected $validator = NULL;

	/**
	 * @var array An array which stores the registered property editors
	 */
	protected $propertyEditors = array();

	/**
	 * @var array An array which stores the registered filters
	 */
	protected $filters = array();

	/**
	 * Constructor
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory A object factory implementation
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * This is a factory method to get the validator resolver
	 *
	 * @return \F3\FLOW3\Validation\ValidatorResolver The validator resolver
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectValidatorResolver(\F3\FLOW3\Validation\ValidatorResolver $validatorResolver) {
		$this->validatorResolver = $validatorResolver;
	}

	/**
	 * Sets the target object for this Property Mapper
	 *
	 * @param  object $target: The target object the Property Values are bound to
	 * @throws \F3\FLOW3\Property\Exception\InvalidTargetObject if the $target is no valid object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setTarget($target) {
		if (!is_object($target)) throw new \F3\FLOW3\Property\Exception\InvalidTargetObject('The target object must be a valid object, ' . gettype($target) . ' given.', 1187807099);
		$this->target = $target;
	}

	/**
	 * Returns the target of this Property Mapper
	 *
	 * @return object The target object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTarget() {
		if (!is_object($this->target)) throw new \F3\FLOW3\Property\Exception\InvalidTargetObject('No target object has been defined yet.', 1187977962); //'
		return $this->target;
	}

	/**
	 * Set the mapping mode, wether the target object should be touched on mapping errors
	 *
	 * @param  string $doNotMapOnErrors: If set to TRUE, the target object will be untouched, if there are any errors. Default is FALSE.
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setOnlyWriteOnNoErrors($onlyWriteOnNoErrors) {
		$this->onlyWriteOnNoErrors = $onlyWriteOnNoErrors;
	}

	/**
	 * Returns the current mapping mode
	 *
	 * @return boolean TRUE when the mapper only writes the target if no error occures
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @see setMappingMode
	 */
	public function getOnlyWriteOnNoErrors() {
		return $this->onlyWriteOnNoErrors;
	}

	/**
	 * Defines the property names which are allowed for mapping.
	 *
	 * @param  array $allowedProperties: An array of allowed property names. Each entry in this array may be a regular expression.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setAllowedProperties(array $allowedProperties) {
		$this->allowedProperties = $allowedProperties;
	}

	/**
	 * Returns an array of strings defining the allowed properties for mapping.
	 *
	 * @return array The allowed properties
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAllowedProperties() {
		return $this->allowedProperties;
	}

	/**
	 * Defines the property names which are required.
	 *
	 * @param  array $requiredProperties: An array of names of the requires properties. Each entry in this array may be a regular expression.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setRequiredProperties(array $requiredProperties) {
		$this->requiredProperties = $requiredProperties;
	}

	/**
	 * Returns an array of strings defining the required properties.
	 *
	 * @return array The required properties
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRequiredProperties() {
		return $this->requiredProperties;
	}

	/**
	 * Registers the given Property Editor for use with the specified property and with the given source format.
	 * If no property is specified, it will be used for all. If no format is specified the default format will be used.
	 * Note: You can only use one editor that is not set for a specific property. Use a composite editor, if you need more.
	 *
	 * @param  \F3\FLOW3\Property\EditorInterface $propertyEditor The property editor
	 * @param  string $property The editor should only be used for this property
	 * @param  string $format The source format the editor should be used with.
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function registerPropertyEditor(\F3\FLOW3\Property\EditorInterface $propertyEditor, $property = 'all', $format = 'default') {
		$this->propertyEditors[$property] = array(
			'format' => $format,
			'propertyEditor' => $propertyEditor
		);
	}

	/**
	 * Registers the given Filter for use with the specified property. If no property is specified, it will be used for all.
	 * Note: You can only use one filter that is not set for a specific property. Use a filterChain, if you need more.
	 *
	 * @param  \F3\FLOW3\Validation\FilterInterface $filter: The filter
	 * @param  string $property: The filter should only be used for this property
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function registerFilter(\F3\FLOW3\Validation\FilterInterface $filter, $property = 'all') {
		$this->filters[$property] = $filter;
	}

	/**
	 * Registers the given Validator to validate the target object.
	 * Note: You can only use one validator. Use a validatorChain, if you need more.
	 *
	 * @param  \F3\FLOW3\Validation\ObjectValidatorInterface $validator: The validator
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function registerValidator(\F3\FLOW3\Validation\ObjectValidatorInterface $validator) {
		$this->validator = $validator;
	}

	/**
	 * Maps the given properties to the target object.
	 * After mapping the results can be retrieved with getMappingResult.
	 *
	 * @param  object $properties: Properties to map to the target object
	 * @param  object $target: Optional. The target object. Will be used instead of this->setTarget(), if it is set.
	 * @param array $allowedProperties: Optional. An array of allowed property names. Each entry in this array may be a regular expression. Will be used instead of this->setAllowedProperties, if it is set.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function map($properties, $target = NULL, $allowedProperties = NULL) {
		$this->mappingResults = $this->createNewMappingResults();
		
		if ($target) {
			$this->setTarget($target);
			unset($target);
		}
		
		if ($allowedProperties) {
			$this->setAllowedProperties($allowedProperties);
			unset($allowedProperties);
		}
		
		if (!$properties instanceof \ArrayAccess) {
			$properties = new \ArrayObject(\F3\FLOW3\Reflection\ObjectAccess::getAllProperties($properties));
		}
		
		if (!is_object($this->target)) throw new \F3\FLOW3\Property\Exception\InvalidTargetObject('No target object has been defined yet.', 1187978014); //'
		if ($this->onlyWriteOnNoErrors) $this->originalTarget = clone $this->target;
		if ($this->validator === NULL) $this->resolveValidator();

		foreach ($properties as $propertyName => $propertyValue) {

			if ($this->isAllowedProperty($propertyName)) {

				$propertyValue = $this->invokeFilter($propertyName, $propertyValue);
				$propertyValue = $this->invokePropertyEditor($propertyName, $propertyValue);

				if (!$this->setPropertyValue($propertyName, $propertyValue)) {
					if ($this->isRequiredProperty($propertyName)) {
						$this->mappingResults->addError($this->createNewMappingErrorObject('The property could not be set in the target object, but was marked as required.', 1210367835), $propertyName);
					} else {
						$this->mappingResults->addWarning($this->createNewMappingWarningObject('The property could not be set in the target object.', 1210367858), $propertyName);
					}
				}
			} else {
				$this->mappingResults->addWarning($this->createNewMappingWarningObject('The property was available but not defined as an available property for the target object.', 1210367894), $propertyName);
			}
		}

		$this->validateTarget();

		if ($this->onlyWriteOnNoErrors && $this->mappingResults->hasErrors()) $this->target = $this->originalTarget;
	}

	/**
	 * Returns an object containing the results of a mapping. Note that map() must be called
	 * before mapping results are available.
	 *
	 * @return \F3\FLOW3\Property\MappingResults Results of the last mapping
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMappingResults() {
		if (!is_object($this->target)) throw new \F3\FLOW3\Property\Exception\InvalidTargetObject('No target object has been defined yet, so no mapping result exists.', 1187978053);//'
		return $this->mappingResults;
	}

	/**
	 * Checks if the give property is among the allowed properties.
	 *
	 * @param string $propertyName: Property name to check for
	 * @return boolean TRUE if the property is allowed, else FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @see map()
	 */
	protected function isAllowedProperty($propertyName) {
		$isAllowed = FALSE;
		foreach ($this->allowedProperties as $allowedProperty) {
			if (preg_match('/^' . $allowedProperty . '$/', $propertyName) === 1) {
				$isAllowed = TRUE;
				break;
			}
		}
		return $isAllowed;
	}

	/**
	 * Checks if the give property is among the required properties.
	 *
	 * @param string $propertyName: Property name to check for
	 * @return boolean TRUE if the property is required, else FALSE
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @see map()
	 */
	protected function isRequiredProperty($propertyName) {
		$isRequired = FALSE;
		foreach ($this->requiredProperties as $requiredProperty) {
			if (preg_match('/^' . $requiredProperty . '$/', $propertyName) === 1) {
				$isRequired = TRUE;
				break;
			}
		}
		return $isRequired;
	}

	/**
	 * Sets the given value of the specified property at the target object.
	 * 
	 * Uses \F3\FLOW3\Reflection\ObjectAccess::setProperty(). See its documentation for details.
	 *
	 * @param  string $propertyName: Name of the property to set
	 * @param  string $propertyValue: Value of the property
	 * @return boolean Returns TRUE, if the setting was successfull
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @see    map()
	 * @todo   Implement error logging into MappingResult
	 */
	protected function setPropertyValue($propertyName, $propertyValue) {

		if ($this->validator !== NULL) {
			$objectValidatorErrors = $this->createNewValidationErrorsObject();
			if (!$this->validator->isValidProperty('F3\FLOW3\MVC\Controller\Arguments', $propertyName, $propertyValue, $objectValidatorErrors)) {
				foreach ($objectValidatorErrors as $propertyName => $objectValidatorError) {
					if ($objectValidatorError instanceof \F3\FLOW3\Validation\Errors) {
						foreach ($objectValidatorError as $error) {
							$this->mappingResults->addError($error, $propertyName);
						}
					} else {
						$this->mappingResults->addError($objectValidatorError, $propertyName);
					}
				}
				return FALSE;
			}
		}

		try {
			\F3\FLOW3\Reflection\ObjectAccess::setProperty($this->target, $propertyName, $propertyValue);
		} catch (\Exception $exception) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Invokes all registered filters for a given property.
	 *
	 * @param string $propertyName: Property name to invoke the filter for
	 * @param object $propertyValue: The property value to filter
	 * @return object the filtered value
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function invokeFilter($propertyName, $propertyValue) {
		$errors = $this->createNewValidationErrorsObject();

		if (isset($this->filters[$propertyName])) $propertyValue = $this->filters[$propertyName]->filter($propertyValue, $errors);
		if (isset($this->filters['all'])) $propertyValue = $this->filters['all']->filter($propertyValue, $errors);

		if (count($errors) > 0) {
			foreach ($errors as $error) $this->mappingResults->addError($error, $propertyName);
		}

		return $propertyValue;
	}

	/**
	 * Invokes all registered property editors for a given property.
	 *
	 * @param string $propertyName: Property name to invoke the editors for
	 * @param object $propertyValue: The property value to set for the editor
	 * @return object the edited value
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function invokePropertyEditor($propertyName, $propertyValue) {
		try {
			if (isset($this->propertyEditors[$propertyName])) {
				$this->propertyEditors[$propertyName]['propertyEditor']->setAsFormat($this->propertyEditors[$propertyName]['format'], $propertyValue);
				$propertyValue = $this->propertyEditors[$propertyName]['propertyEditor']->getProperty();
			} elseif (isset($this->propertyEditors['all'])) {
				$this->propertyEditors['all']['propertyEditor']->setAsFormat($this->propertyEditors['all']['format'], $propertyValue);
				$propertyValue = $this->propertyEditors['all']['propertyEditor']->getProperty();
			}
		} catch (\F3\FLOW3\Property\Exception $exception) {
			$this->mappingResults->addError($this->createNewValidationErrorObject('The property editor could not handle the given value in the given format.', 1210368164), $propertyName);
		}

		return $propertyValue;
	}

	/**
	 * Call the validator resolver to find a validator if none is found the target won't be validated
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function resolveValidator() {
		try {
			$this->validator = $this->validatorResolver->resolveValidator(get_class($this->target));
		} catch (\F3\FLOW3\Validation\Exception\NoValidatorFound $exception) {
			$this->validator = NULL;
		}
	}

	/**
	 * Validates the whole target object against the set validator (if there is one)
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function validateTarget() {
		if ($this->validator != NULL) {
			$objectValidatorErrors = $this->createNewValidationErrorsObject();

			if (!$this->validator->validate($this->target, $objectValidatorErrors)) {
				foreach ($objectValidatorErrors as $propertyName => $objectValidatorError) {
					if ($objectValidatorError instanceof \F3\FLOW3\Validation\Errors) {
						foreach ($objectValidatorError as $error) {
							$this->mappingResults->addError($error, $propertyName);
						}
					} else {
						$this->mappingResults->addError($objectValidatorError, $propertyName);
					}
				}
			}
		}
	}

	/**
	 * This is a factory method to get a fresh mapping results object
	 *
	 * @return \F3\FLOW3\Property\MappingResults A Mapping Results object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function createNewMappingResults() {
		return $this->objectFactory->create('F3\FLOW3\Property\MappingResults');
	}

	/**
	 * This is a factory method to get a clean validation errors object
	 *
	 * @return \F3\FLOW3\Validation\Errors An empty errors object
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function createNewValidationErrorsObject() {
		return $this->objectFactory->create('F3\FLOW3\Validation\Errors');
	}

	/**
	 * This is a factory method to get a clean validation error object
	 *
	 * @param string The error message
	 * @param integer The error code
	 * @return \F3\FLOW3\Validation\Error An empty error object
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function createNewValidationErrorObject($message, $code) {
		return $this->objectFactory->create('F3\FLOW3\Validation\Error', $message, $code);
	}

	/**
	 * This is a factory method to get a clean mapping error object
	 *
	 * @param string The error message
	 * @param integer The error code
	 * @return \F3\FLOW3\Property\MappingError An empty error object
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function createNewMappingErrorObject($message, $code) {
		return $this->objectFactory->create('F3\FLOW3\Property\MappingError', $message, $code);
	}

	/**
	 * This is a factory method to get a clean mapping error object
	 *
	 * @param string The error message
	 * @param integer The error code
	 * @return \F3\FLOW3\Property\MappingWarning An empty error object
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function createNewMappingWarningObject($message, $code) {
		return $this->objectFactory->create('F3\FLOW3\Property\MappingWarning', $message, $code);
	}

}

?>