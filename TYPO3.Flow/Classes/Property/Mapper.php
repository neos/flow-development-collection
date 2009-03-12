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
 * @package FLOW3
 * @subpackage Property
 * @version $Id$
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
 * $mapper->map(array('someProperty'), $source, $target);
 *
 * Now the target object equals the source object.
 *
 * @package FLOW3
 * @subpackage Property
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
	 * Maps the given properties to the target object and validates it. If the result is not valid, the operation
	 * will be undone, the target object remains unchanged and the method returns FALSE.
	 *
	 * @param array $propertyNames Names of the properties to map.
	 * @param object $array Source object containing the properties to map to the target object
	 * @param object $target The target object
	 * @return \F3\FLOW3\Property\MappingResults
	 * @author Robert Lemke <robert@typo3.org>
	 *
	 * mapAndValidate(array $propertyNames, $source, $target, $optionalPropertyNames = array(), $validator = NULL, $propertyValidators = array(), $propertyFilters = array);
	 */
	public function mapAndValidate(array $propertyNames, $source, $target, $optionalPropertyNames = array(), $validator = NULL, array $propertyValidators = array(), array $propertyFilters = array()) {
		$this->mappingResults = $this->objectFactory->create('F3\FLOW3\Property\MappingResults');

		if (!$source instanceof \ArrayAccess) $source = new \ArrayObject(\F3\FLOW3\Reflection\ObjectAccess::getAccessibleProperties($source));
		if (!is_object($target)) throw new \F3\FLOW3\Property\Exception\InvalidTargetObject('The target object must be a valid object, ' . gettype($target) . ' given.', 1187807099);
		foreach ($propertyNames as $propertyName) {
			if (isset($source[$propertyName])) {
				if (\F3\FLOW3\Reflection\ObjectAccess::setProperty($target, $propertyName, $source[$propertyName]) === FALSE) {
					$this->mappingResults->addError($this->objectFactory->create('F3\FLOW3\Error\Error', "Property '$propertyName' could not be set on target object." , 1236783102), $propertyName);
				}
			} elseif (!in_array($propertyName, $optionalPropertyNames)) {
				$this->mappingResults->addError($this->objectFactory->create('F3\FLOW3\Error\Error', "Required property '$propertyName' did not exist in the source." , 1236785359), $propertyName);
			}
		}
		return (!$this->mappingResults->hasErrors() && !$this->mappingResults->hasWarnings());
	}

	/**
	 * Returns the results of the last mapping operation.
	 *
	 * @return \F3\FLOW3\Propert\MappingResults The mapping results (or NULL if no mapping has been carried out yet)
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMappingResults() {
		return $this->mappingResults;
	}
}

?>