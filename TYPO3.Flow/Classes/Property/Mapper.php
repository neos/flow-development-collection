<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Property;

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
 * The Property Mapper maps properties from a source onto a given target object, often a
 * (domain-) model. Which properties are required and how they should be filtered can
 * be customized.
 *
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
 * $mapper->mapAndValidate(array('someProperty'), $source, $target);
 *
 * Now the target object equals the source object.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Mapper {

	/**
	 * Results of the last mapping operation
	 * @var \F3\FLOW3\Propert\MappingResults
	 */
	protected $mappingResults;

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Validation\ValidatorResolver
	 */
	protected $validatorResolver;

	/**
	 * Injects the object factory
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Injects the validator resolver
	 *
	 * @param \F3\FLOW3\Validation\ValidatorResolver $validatorResolver
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectValidatorResolver(\F3\FLOW3\Validation\ValidatorResolver $validatorResolver) {
		$this->validatorResolver = $validatorResolver;
	}

	/**
	 * Maps the given properties to the target object and validates the properties according to the defined
	 * validators. If the result object is not valid, the operation will be undone (the target object remains
	 * unchanged) and this method returns FALSE.
	 *
	 * If in doubt, always prefer this method over the map() method because skipping validation can easily become
	 * a security issue.
	 *
	 * @param array $propertyNames Names of the properties to map.
	 * @param mixed $source Source containing the properties to map to the target object. Must either be an array, ArrayObject or any other object.
	 * @param object $target The target object
	 * @param \F3\FLOW3\Validation\Validator\ObjectValidatorInterface $targetObjectValidator A validator used for validating the target object
	 * @param array $optionalPropertyNames Names of optional properties. If a property is specified here and it doesn't exist in the source, no error is issued.
	 * @return boolean TRUE if the mapped properties are valid, otherwise FALSE
	 * @see getMappingResults()
	 * @see map()
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	public function mapAndValidate(array $propertyNames, $source, $target, $optionalPropertyNames = array(), \F3\FLOW3\Validation\Validator\ObjectValidatorInterface $targetObjectValidator) {
		$backupProperties = array();

		$this->map($propertyNames, $source, $backupProperties, $optionalPropertyNames);
		if ($this->mappingResults->hasErrors()) return FALSE;

		$this->map($propertyNames, $source, $target, $optionalPropertyNames);
		if ($this->mappingResults->hasErrors()) return FALSE;

		if ($targetObjectValidator->isValid($target) !== TRUE) {
			$this->addErrorsFromObjectValidator($targetObjectValidator->getErrors());
			$backupMappingResult = $this->mappingResults;
			$this->map($propertyNames, $backupProperties, $source, $optionalPropertyNames);
			$this->mappingResults = $backupMappingResult;
		}
		return (!$this->mappingResults->hasErrors());
	}

	/**
	 * Add errors to the mapping result from an object validator (property errors).
	 *
	 * @param array Array of \F3\FLOW3\Validation\PropertyError
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @return void
	 */
	protected function addErrorsFromObjectValidator($errors) {
		foreach ($errors as $error) {
			if ($error instanceof \F3\FLOW3\Validation\PropertyError) {
				$propertyName = $error->getPropertyName();
				$this->mappingResults->addError($error, $propertyName);
			}
		}
	}

	/**
	 * Maps the given properties to the target object WITHOUT VALIDATING THE RESULT.
	 * If the properties could be set, this method returns TRUE, otherwise FALSE.
	 * Returning TRUE does not mean that the target object is valid and secure!
	 *
	 * Only use this method if you're sure that you don't need validation!
	 *
	 * @param array $propertyNames Names of the properties to map.
	 * @param mixed $source Source containing the properties to map to the target object. Must either be an array, ArrayObject or any other object.
	 * @param object $target The target object
	 * @param array $optionalPropertyNames Names of optional properties. If a property is specified here and it doesn't exist in the source, no error is issued.
	 * @return boolean TRUE if the properties could be mapped, otherwise FALSE
	 * @see mapAndValidate()
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function map(array $propertyNames, $source, $target, $optionalPropertyNames = array()) {
		if (!is_object($source) && !is_array($source)) throw new \F3\FLOW3\Property\Exception\InvalidSource('The source object must be a valid object or array, ' . gettype($target) . ' given.', 1187807099);
		if (!is_object($target) && !is_array($target)) throw new \F3\FLOW3\Property\Exception\InvalidTarget('The target object must be a valid object or array, ' . gettype($target) . ' given.', 1187807099);

		$this->mappingResults = $this->objectFactory->create('F3\FLOW3\Property\MappingResults');
		$propertyValues = array();

		foreach ($propertyNames as $propertyName) {
			if (is_array($source) || $source instanceof \ArrayAccess) {
				if (isset($source[$propertyName])) $propertyValues[$propertyName] = $source[$propertyName];
			} else {
				$propertyValues[$propertyName] = \F3\FLOW3\Reflection\ObjectAccess::getProperty($source, $propertyName);
			}
		}
		foreach ($propertyNames as $propertyName) {
			if (isset($propertyValues[$propertyName])) {
				if (is_array($target)) {
					$target[$propertyName] = $source[$propertyName];
				} elseif (\F3\FLOW3\Reflection\ObjectAccess::setProperty($target, $propertyName, $propertyValues[$propertyName]) === FALSE) {
					$this->mappingResults->addError($this->objectFactory->create('F3\FLOW3\Error\Error', "Property '$propertyName' could not be set." , 1236783102), $propertyName);
				}
			} elseif (!in_array($propertyName, $optionalPropertyNames)) {
				$this->mappingResults->addError($this->objectFactory->create('F3\FLOW3\Error\Error', "Required property '$propertyName' does not exist." , 1236785359), $propertyName);
			}
		}
		return (!$this->mappingResults->hasErrors() && !$this->mappingResults->hasWarnings());
	}

	/**
	 * Returns the results of the last mapping operation.
	 *
	 * @return \F3\FLOW3\Propert\MappingResults The mapping results (or NULL if no mapping has been carried out yet)
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getMappingResults() {
		return $this->mappingResults;
	}
}

?>