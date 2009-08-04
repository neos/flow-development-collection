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
	 * @var \F3\FLOW3\Persistence\QueryFactoryInterface
	 */
	protected $queryFactory;

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
	protected $dataType = 'Text';

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
	 * Constructs this controller argument
	 *
	 * @param string $name Name of this argument
	 * @param string $dataType The data type of this argument
	 * @throws \InvalidArgumentException if $name is not a string or empty
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function __construct($name, $dataType = 'Text') {
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
	 * @param \F3\FLOW3\Reflection\Service
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects a QueryFactory instance
	 *
	 * @param \F3\FLOW3\Persistence\QueryFactoryInterface $queryFactory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectQueryFactory(\F3\FLOW3\Persistence\QueryFactoryInterface $queryFactory) {
		$this->queryFactory = $queryFactory;
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
	 * @param array Object names of the validators
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
	 * Sets the value of this argument.
	 *
	 * Checks if the value is a UUID or an array but should be an object, i.e.
	 * the argument's data type class schema is set. If that is the case, this
	 * method tries to look up the corresponding object instead.
	 *
	 * @param mixed $value The value of this argument
	 * @return \F3\FLOW3\MVC\Controller\Argument $this
	 * @throws \F3\FLOW3\MVC\Exception\InvalidArgumentValue if the argument is not a valid object of type $dataType
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setValue($value) {
		if (is_string($value) && $this->dataTypeClassSchema !== NULL && preg_match('/([a-f0-9]){8}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){12}/', $value) === 1) {
			$value = array('__identity' => $value);
		}

		if (is_array($value) && $this->dataTypeClassSchema !== NULL) {
			if (isset($value['__identity'])) {
				$existingObject = (is_array($value['__identity'])) ? $this->findObjectByIdentityProperties($value['__identity']) : $this->findObjectByIdentityUUID($value['__identity']);
				if ($existingObject === FALSE) throw new \F3\FLOW3\MVC\Exception\InvalidArgumentValue('Argument "' . $this->name . '": Querying the repository for the specified object was not successful.', 1237305720);
				unset($value['__identity']);
				if (count($value) === 0) {
					$value = $existingObject;
				} elseif ($existingObject !== NULL) {
					$newObject = clone $existingObject;
					if ($this->propertyMapper->map(array_keys($value), $value, $newObject)) {
						$value = $newObject;
					}
				}
			} else {
				$newObject = $this->objectFactory->create($this->dataType);
				if ($this->propertyMapper->map(array_keys($value), $value, $newObject)) {
					$value = $newObject;
				}
			}
		}

		$this->value = $value;
		return $this;
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

	/**
	 * Finds an object from the repository by searching for its identity properties.
	 *
	 * @param array $identityProperties Property names and values to search for
	 * @return mixed Either the object matching the identity or, if none or more than one object was found, FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function findObjectByIdentityProperties(array $identityProperties) {
		$query = $this->queryFactory->create($this->dataType);
		$equals = array();
		foreach ($this->dataTypeClassSchema->getIdentityProperties() as $propertyName => $propertyType) {
			if (isset($identityProperties[$propertyName])) {
				if ($propertyType === 'string') {
					$equals[] = $query->equals($propertyName, $identityProperties[$propertyName], FALSE);
				} else {
					$equals[] = $query->equals($propertyName, $identityProperties[$propertyName]);
				}
			}
		}
		if (count($equals) === 1) {
			$constraint = current($equals);
		} else {
			$constraint = $query->logicalAnd(current($equals), next($equals));
			while (($equal = next($equals)) !== FALSE) {
				$constraint = $query->logicalAnd($constraint, $equal);
			}
		}
		$query->matching($constraint);
		$objects = $query->execute();
		if (count($objects) === 1 ) return current($objects);
		throw new \F3\FLOW3\MVC\Exception\InvalidArgumentValue('Argument "' . $this->name . '": Querying the repository for object by properties (' . implode(', ', array_keys($identityProperties)) . ') resulted in ' . count($objects) . ' objects instead of one.', 1237305719);
	}

	/**
	 * Finds an object from the repository by searching for its technical UUID.
	 *
	 * @param string $uuid The object's uuid
	 * @return mixed Either the object matching the uuid or, if none or more than one object was found, FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function findObjectByIdentityUUID($uuid) {
		$query = $this->queryFactory->create($this->dataType);
		$objects = $query->matching($query->withUUID($uuid))->execute();
		if (count($objects) === 1 ) return current($objects);
		return FALSE;
	}
}
?>