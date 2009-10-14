<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Controller;

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
 * A controller argument
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Argument {

	/**
	 * A preg pattern to match against UUIDs
	 * @var string
	 */
	const PATTERN_MATCH_UUID = '/([a-f0-9]){8}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){12}/';

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Reflection\Service
	 */
	protected $reflectionService;

	/**
	 * @var \F3\FLOW3\Persistence\ManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var \F3\FLOW3\Property\Mapper
	 */
	protected $propertyMapper;

	/**
	 * Name of this argument
	 * @var string
	 */
	protected $name = '';

	/**
	 * Short name of this argument
	 * @var string
	 */
	protected $shortName = NULL;

	/**
	 * Short help message for this argument
	 * @var string Short help message for this argument
	 */
	protected $shortHelpMessage = NULL;

	/**
	 * Data type of this argument's value
	 * @var string
	 */
	protected $dataType = NULL;

	/**
	 * If the data type is an object, the class schema of the data type class is resolved
	 * @var \F3\FLOW3\Reflection\ClassSchema
	 */
	protected $dataTypeClassSchema;

	/**
	 * TRUE if this argument is required
	 * @var boolean
	 */
	protected $isRequired = FALSE;

	/**
	 * Actual value of this argument
	 * @var object
	 */
	protected $value = NULL;

	/**
	 * Default value. Used if argument is optional.
	 * @var mixed
	 */
	protected $defaultValue = NULL;

	/**
	 * A custom validator, used supplementary to the base validation
	 * @var \F3\FLOW3\Validation\Validator\ValidatorInterface
	 */
	protected $validator = NULL;

	/**
	 * A filter for this argument
	 * @var \F3\FLOW3\Validation\FilterInterface
	 */
	protected $filter = NULL;

	/**
	 * The origin of the argument value.
	 * @var integer
	 */
	const ORIGIN_CLIENT = 0;
	const ORIGIN_PERSISTENCE = 1;
	const ORIGIN_PERSISTENCE_AND_MODIFIED = 2;
	const ORIGIN_NEWLY_CREATED = 3;

	/**
	 * The origin of the argument value. This is only meaningful after argument mapping.
	 *
	 * One of the ORIGIN_* constants above
	 * @var integer
	 */
	protected $origin = 0;

	/**
	 * Constructs this controller argument
	 *
	 * @param string $name Name of this argument
	 * @param string $dataType The data type of this argument
	 * @throws \InvalidArgumentException if $name is not a string or empty
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function __construct($name, $dataType) {
		if (!is_string($name)) throw new \InvalidArgumentException('$name must be of type string, ' . gettype($name) . ' given.', 1187951688);
		if (strlen($name) === 0) throw new \InvalidArgumentException('$name must be a non-empty string, ' . strlen($name) . ' characters given.', 1232551853);
		$this->name = $name;
		$this->dataType = $dataType;
	}

	/**
	 * Injects the Object Manager
	 *
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the Object Factory
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Injects the Reflection Service
	 *
	 * @param \F3\FLOW3\Reflection\Service $reflectionService
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects the persistence manager
	 *
	 * @param \F3\FLOW3\Persistence\ManagerInterface $persistenceManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectPersistenceManager(\F3\FLOW3\Persistence\ManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * Injects the Property Mapper
	 *
	 * @param \F3\FLOW3\Property\Mapper $propertyMapper
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPropertyMapper(\F3\FLOW3\Property\Mapper $propertyMapper) {
		$this->propertyMapper = $propertyMapper;
	}

	/**
	 * Initializes this object
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObject() {
		$this->dataTypeClassSchema = $this->reflectionService->getClassSchema($this->dataType);
	}

	/**
	 * Returns the name of this argument
	 *
	 * @return string This argument's name
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets the short name of this argument.
	 *
	 * @param string $shortName A "short name" - a single character
	 * @return \F3\FLOW3\MVC\Controller\Argument $this
	 * @throws \InvalidArgumentException if $shortName is not a character
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setShortName($shortName) {
		if ($shortName !== NULL && (!is_string($shortName) || strlen($shortName) !== 1)) throw new \InvalidArgumentException('$shortName must be a single character or NULL', 1195824959);
		$this->shortName = $shortName;
		return $this;
	}

	/**
	 * Returns the short name of this argument
	 *
	 * @return string This argument's short name
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @api
	 */
	public function getShortName() {
		return $this->shortName;
	}

	/**
	 * Returns the data type of this argument's value
	 *
	 * @return string The data type
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getDataType() {
		return $this->dataType;
	}

	/**
	 * Marks this argument to be required
	 *
	 * @param boolean $required TRUE if this argument should be required
	 * @return \F3\FLOW3\MVC\Controller\Argument $this
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @api
	 */
	public function setRequired($required) {
		$this->isRequired = (boolean)$required;
		return $this;
	}

	/**
	 * Returns TRUE if this argument is required
	 *
	 * @return boolean TRUE if this argument is required
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @api
	 */
	public function isRequired() {
		return $this->isRequired;
	}

	/**
	 * Sets a short help message for this argument. Mainly used at the command line, but maybe
	 * used elsewhere, too.
	 *
	 * @param string $message A short help message
	 * @return \F3\FLOW3\MVC\Controller\Argument $this
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setShortHelpMessage($message) {
		if (!is_string($message)) throw new \InvalidArgumentException('The help message must be of type string, ' . gettype($message) . 'given.', 1187958170);
		$this->shortHelpMessage = $message;
		return $this;
	}

	/**
	 * Returns the short help message
	 *
	 * @return string The short help message
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getShortHelpMessage() {
		return $this->shortHelpMessage;
	}

	/**
	 * Sets the default value of the argument
	 *
	 * @param mixed $defaultValue Default value
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function setDefaultValue($defaultValue) {
		$this->defaultValue = $defaultValue;
	}

	/**
	 * Returns the default value of this argument
	 *
	 * @return mixed The default value
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getDefaultValue() {
		return $this->defaultValue;
	}

	/**
	 * Sets a custom validator which is used supplementary to the base validation
	 *
	 * @param \F3\FLOW3\Validation\Validator\ValidatorInterface $validator The actual validator object
	 * @return \F3\FLOW3\MVC\Controller\Argument Returns $this (used for fluent interface)
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setValidator(\F3\FLOW3\Validation\Validator\ValidatorInterface $validator) {
		$this->validator = $validator;
		return $this;
	}

	/**
	 * Create and set a validator conjunction
	 *
	 * @param array $objectNames Object names of the validators
	 * @return \F3\FLOW3\MVC\Controller\Argument Returns $this (used for fluent interface)
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @api
	 */
	public function setNewValidatorConjunction(array $objectNames) {
		$this->validator = $this->objectFactory->create('F3\FLOW3\Validation\Validator\ConjunctionValidator');
		foreach ($objectNames as $objectName) {
			if (!$this->objectManager->isObjectRegistered($objectName)) $objectName = 'F3\FLOW3\Validation\Validator\\' . $objectName;
			$this->validator->addValidator($this->objectManager->getObject($objectName));
		}
		return $this;
	}

	/**
	 * Returns the set validator
	 *
	 * @return \F3\FLOW3\Validation\Validator\ValidatorInterface The set validator, NULL if none was set
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @api
	 */
	public function getValidator() {
		return $this->validator;
	}

	/**
	 * Set a filter
	 *
	 * @param mixed $filter Object name of a filter or the actual filter object
	 * @return \F3\FLOW3\MVC\Controller\Argument Returns $this (used for fluent interface)
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setFilter($filter) {
		$this->filter = ($filter instanceof \F3\FLOW3\Validation\Filter\FilterInterface) ? $filter : $this->objectManager->getObject($filter);
		return $this;
	}

	/**
	 * Returns the set filter
	 *
	 * @return \F3\FLOW3\Validation\FilterInterface The set filter, NULL if none was set
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getFilter() {
		return $this->filter;
	}

	/**
	 * Get the origin of the argument value. This is only meaningful after argument mapping.
	 *
	 * @return integer one of the ORIGIN_* constants
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getOrigin() {
		return $this->origin;
	}

	/**
	 * Sets the value of this argument.
	 *
	 * @param mixed $value The value of this argument
	 * @return \F3\FLOW3\MVC\Controller\Argument $this
	 * @throws \F3\FLOW3\MVC\Exception\InvalidArgumentValue if the argument is not a valid object of type $dataType
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setValue($value) {
		$this->value = $this->transformValue($value);

		return $this;
	}

	/**
	 * Checks if the value is a UUID or an array but should be an object, i.e.
	 * the argument's data type class schema is set. If that is the case, this
	 * method tries to look up the corresponding object instead.
	 *
	 * Additionally, it maps arrays to objects in case it is a normal object.
	 *
	 * @param mixed $value The value of an argument
	 * @return mixed
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function transformValue($value) {
		if ($value === NULL) {
			return NULL;
		}
		if (!class_exists($this->dataType)) {
			return $value;
		}
		$transformedValue = NULL;
		if ($this->dataTypeClassSchema !== NULL) {
				// The target object is an Entity or ValueObject.
			if (is_string($value) && preg_match(self::PATTERN_MATCH_UUID, $value) === 1) {
				$this->origin = self::ORIGIN_PERSISTENCE;
				$transformedValue = $this->persistenceManager->getBackend()->getObjectByIdentifier($value);
			} elseif (is_array($value)) {
				if (array_keys($value) === array('__identity')) { // If there is only an __identity array _and nothing else_, then the property mapper will not clone the object.
					$this->origin = self::ORIGIN_PERSISTENCE;
				} else {
					$this->origin = self::ORIGIN_PERSISTENCE_AND_MODIFIED;
				}
				$transformedValue = $this->propertyMapper->map(array_keys($value), $value, $this->dataType);
			}
		} else {
			if (!is_array($value)) {
				throw new \F3\FLOW3\MVC\Exception\InvalidArgumentValue('The value was a simple type, so we could not map it to an object. Maybe the @entity or @valueobject annotations are missing?', 1251730701);
			}
			$this->origin = self::ORIGIN_NEWLY_CREATED;
			$transformedValue = $this->propertyMapper->map(array_keys($value), $value, $this->dataType);
		}

		if (!($transformedValue instanceof $this->dataType)) {
			throw new \F3\FLOW3\MVC\Exception\InvalidArgumentValue('The value must be of type "' . $this->dataType . '", but was of type "' . (is_object($transformedValue) ? get_class($transformedValue) : gettype($transformedValue)) . '".', 1251730701);
		}
		return $transformedValue;
	}

	/**
	 * Returns the value of this argument. If the value is NULL, we use the defaultValue.
	 *
	 * @return object The value of this argument - if none was set, the default value is returned
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getValue() {
		return ($this->value === NULL) ? $this->defaultValue : $this->value;
	}

	/**
	 * Returns a string representation of this argument's value
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function __toString() {
		return (string)$this->value;
	}

}
?>