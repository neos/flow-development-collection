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
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 */

/**
 * A controller argument
 *
 * @package FLOW3
 * @subpackage MVC
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
	 * @var \F3\FLOW3\Persistence\ManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var \F3\FLOW3\Persistence\QueryFactoryInterface
	 */
	protected $queryFactory;

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
	 * Data type of this argument's value
	 * @var string
	 */
	protected $dataType = 'Text';

	/**
	 * If the data type is an object, the class schema of the data type class is resolved
	 * @var \F3\FLOW3\Persistence\ClassSchema
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
	 * Short help message for this argument
	 * @var string Short help message for this argument
	 */
	protected $shortHelpMessage = NULL;

	/**
	 * The argument is valid
	 * @var boolean
	 */
	protected $isValid = TRUE;

	/**
	 * Any error (\F3\FLOW3\Error\Error) that occured while initializing this argument (e.g. a mapping error)
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Any warning (\F3\FLOW3\Error\Warning) that occured while initializing this argument (e.g. a mapping warning)
	 * @var array
	 */
	protected $warnings = array();

	/**
	 * The property validator for this argument
	 * @var \F3\FLOW3\Validation\Validator\ValidatorInterface
	 */
	protected $validator = NULL;

	/**
	 * The property validator for this arguments data type
	 * @var \F3\FLOW3\Validation\Validator\ValidatorInterface
	 */
	protected $dataTypeValidator = NULL;

	/**
	 * The filter for this argument
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
		$this->objectFactory = $this->objectManager->getObjectFactory();
	}

	/**
	 * Injects the Persistence Manager
	 *
	 * @param \F3\FLOW3\Persistence\ManagerInterface
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPersistenceManager(\F3\FLOW3\Persistence\ManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
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
	 * Initializes this object
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObject() {
		$this->setDataType($this->dataType);
	}

	/**
	 * Returns the name of this argument
	 *
	 * @return string This argument's name
	 * @author Robert Lemke <robert@typo3.org>
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
	 */
	public function setShortName($shortName) {
		if ($shortName !== NULL && (!is_string($shortName) || strlen($shortName) != 1)) throw new \InvalidArgumentException('$shortName must be a single character or NULL', 1195824959);
		$this->shortName = $shortName;
		return $this;
	}

	/**
	 * Returns the short name of this argument
	 *
	 * @return string This argument's short name
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getShortName() {
		return $this->shortName;
	}

	/**
	 * Sets the data type of this argument's value
	 *
	 * @param string $dataType: Name of the data type
	 * @return \F3\FLOW3\MVC\Controller\Argument $this
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setDataType($dataType) {
		$this->dataType = ($dataType != '' ? $dataType : 'Text');
		$dataTypeValidatorObjectName = (strpos($this->dataType, '\\') === FALSE) ? ('F3\FLOW3\Validation\Validator\\' . $this->dataType . 'Validator') : $this->dataType;
		$this->dataTypeValidator = $this->objectManager->isObjectRegistered($dataTypeValidatorObjectName) ? $this->objectManager->getObject($dataTypeValidatorObjectName) : NULL;
		$this->dataTypeClassSchema = $this->persistenceManager->getClassSchema($this->dataType);
		return $this;
	}

	/**
	 * Returns the data type of this argument's value
	 *
	 * @return string The data type
	 * @author Robert Lemke <robert@typo3.org>
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
	 */
	public function setRequired($required) {
		$this->isRequired = $required;
		return $this;
	}

	/**
	 * Returns TRUE if this argument is required
	 *
	 * @return boolean TRUE if this argument is required
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isRequired() {
		return $this->isRequired;
	}

	/**
	 * Sets the value of this argument.
	 *
	 * @param mixed $value: The value of this argument
	 * @return \F3\FLOW3\MVC\Controller\Argument $this
	 * @throws \F3\FLOW3\MVC\Exception\InvalidArgumentValue if the argument is not a valid object of type $dataType
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setValue($value) {
		if ($this->dataTypeClassSchema !== NULL && $this->dataTypeClassSchema->isAggregateRoot()) {
			if (isset($value['__identity']) && is_array($value['__identity'])) {
				$query = $this->queryFactory->create($this->dataType);
				foreach ($this->dataTypeClassSchema->getIdentityProperties() as $propertyName => $propertyType) {
					# TODO build query for multiple properties
					break;
				}
				$query->matching($query->equals($propertyName, $value['__identity'][$propertyName]));
				$objects = $query->execute();
				if (count($objects) === 1 ) {
					$value = current($objects);
				} else {
					# TODO add error (not found) and set validity to FALSE
				}
			} elseif (isset($value['__uuid'])) {
				$query = $this->queryFactory->create($this->dataType);
				$query->matching($query->equals('uuid', $value['__uuid']));
				$objects = $query->execute();
				if (count($objects) === 1 ) {
					$value = current($objects);
				} else {
					# TODO add error (not found) and set validity to FALSE
				}
			}
		}
		$this->value = $value;
		return $this;
	}

	/**
	 * Returns the value of this argument
	 *
	 * @return object The value of this argument - if none was set, NULL is returned
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Checks if this argument has a value set.
	 *
	 * @return boolean TRUE if a value was set, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isValue() {
		return $this->value !== NULL;
	}

	/**
	 * Sets a short help message for this argument. Mainly used at the command line, but maybe
	 * used elsewhere, too.
	 *
	 * @param string $message: A short help message
	 * @return \F3\FLOW3\MVC\Controller\Argument		$this
	 * @author Robert Lemke <robert@typo3.org>
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
	 */
	public function getShortHelpMessage() {
		return $this->shortHelpMessage;
	}

	/**
	 * Set the validity status of the argument
	 *
	 * @param boolean TRUE if the argument is valid, FALSE otherwise
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setValidity($isValid) {
		$this->isValid = $isValid;
	}

	/**
	 * Returns TRUE when the argument is valid
	 *
	 * @return boolean TRUE if the argument is valid
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isValid() {
		return $this->isValid;
	}

	/**
	 * Add an initialization error (e.g. a mapping error)
	 *
	 * @param \F3\FLOW3\Error\Error An error object
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addError(\F3\FLOW3\Error\Error $error) {
		$this->errors[] = $error;
	}

	/**
	 * Get all initialization errors
	 *
	 * @return array An array containing \F3\FLOW3\Error\Error objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @see addError(\F3\FLOW3\Error\Error $error)
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Add an initialization warning (e.g. a mapping warning)
	 *
	 * @param \F3\FLOW3\Error\Warning A warning object
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addWarning(\F3\FLOW3\Error\Warning $warning) {
		$this->warnings[] = $warning;
	}

	/**
	 * Get all initialization warnings
	 *
	 * @return array An array containing \F3\FLOW3\Error\Warning objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @see addWarning(\F3\FLOW3\Error\Warning $warning)
	 */
	public function getWarnings() {
		return $this->warnings;
	}

	/**
	 * Set an additional validator
	 *
	 * @param string Object name of a validator
	 * @return \F3\FLOW3\MVC\Controller\Argument Returns $this (used for fluent interface)
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setValidator($objectName) {
		$this->validator = $this->objectManager->getObject($objectName);
		return $this;
	}

	/**
	 * Returns the set validator
	 *
	 * @return \F3\FLOW3\Validation\Validator\ValidatorInterface The set validator, NULL if none was set
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getValidator() {
		return $this->validator;
	}

	/**
	 * Returns the set datatype validator
	 *
	 * @return \F3\FLOW3\Validation\Validator\ValidatorInterface The set datatype validator
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getDataTypeValidator() {
		return $this->dataTypeValidator;
	}

	/**
	 * Set a filter
	 *
	 * @param string Object name of a filter
	 * @return \F3\FLOW3\MVC\Controller\Argument Returns $this (used for fluent interface)
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setFilter($objectName) {
		$this->filter = $this->objectManager->getObject($objectName);
		return $this;
	}

	/**
	 * Create and set a filter chain
	 *
	 * @param array Object names of the filters
	 * @return \F3\FLOW3\MVC\Controller\Argument Returns $this (used for fluent interface)
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewFilterChain(array $objectNames) {
		$this->filter = $this->objectFactory->create('F3\FLOW3\Validation\Filter\Chain');
		foreach ($objectNames as $objectName) {
			if (!$this->objectManager->isObjectRegistered($objectName)) $objectName = 'F3\FLOW3\Validation\Filter\\' . $objectName;
			$this->filter->addFilter($this->objectManager->getObject($objectName));
		}

		return $this;
	}

	/**
	 * Create and set a validator chain
	 *
	 * @param array Object names of the validators
	 * @return \F3\FLOW3\MVC\Controller\Argument Returns $this (used for fluent interface)
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewValidatorChain(array $objectNames) {
		$this->validator = $this->objectFactory->create('F3\FLOW3\Validation\Validator\ChainValidator');

		foreach ($objectNames as $objectName) {
			if (!$this->objectManager->isObjectRegistered($objectName)) $objectName = 'F3\FLOW3\Validation\Validator\\' . $objectName;
			$this->validator->addValidator($this->objectManager->getObject($objectName));
		}

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
	 * Returns a string representation of this argument's value
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __toString() {
		return (string)$this->value;
	}
}
?>