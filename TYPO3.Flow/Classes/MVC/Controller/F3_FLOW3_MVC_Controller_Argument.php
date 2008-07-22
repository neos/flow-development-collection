<?php
declare(ENCODING = 'utf-8');

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
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_Controller_Argument.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * A controller argument
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_Controller_Argument.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_FLOW3_MVC_Controller_Argument {

	/**
	 * @var F3_FLOW3_Component_FactoryInterface
	 */
	protected $componentFactory;

	/**
	 * @var string Name of this argument
	 */
	protected $name = '';

	/**
	 * @var string Short name of this argument
	 */
	protected $shortName = NULL;

	/**
	 * @var string Data type of this argument's value
	 */
	protected $dataType = 'Text';

	/**
	 * @var object Actual value of this argument
	 */
	protected $value = NULL;

	/**
	 * @var string Short help message for this argument
	 */
	protected $shortHelpMessage = NULL;

	/**
	 * @var boolean The argument is valid
	 */
	protected $isValid = TRUE;

	/**
	 * @var array Any error (F3_FLOW3_Error_Error) that occured while initializing this argument (e.g. a mapping error or warning)
	 */
	protected $errors = array();

	/**
	 * @var F3_FLOW3_Validation_ValidatorInterface The property validator for this argument
	 */
	protected $validator = NULL;

	/**
	 * @var F3_FLOW3_Validation_ValidatorInterface The property validator for this arguments datatype
	 */
	protected $datatypeValidator;

	/**
	 * @var F3_FLOW3_Validation_FilterInterface The filter for this argument
	 */
	protected $filter = NULL;

	/**
	 * @var F3_FLOW3_Property_EditorInterface The property editor for this argument
	 */
	protected $propertyEditor = NULL;

	/**
	 * @var string The property editor's input format for this argument
	 */
	protected $propertyEditorInputFormat = 'string';

	/**
	 * Constructs this controller argument
	 *
	 * @param string $name Name of this argument
	 * @param string $dataType The data type of this argument
	 * @param F3_FLOW3_Component_FactoryInterface The component factory
	 * @throws InvalidArgumentException if $name is not a string or empty
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($name, $dataType = 'Text', F3_FLOW3_Component_FactoryInterface $componentFactory) {
		if (!is_string($name) || F3_PHP6_Functions::strlen($name) < 1) throw new InvalidArgumentException('$name must be of type string, ' . gettype($name) . ' given.', 1187951688);
		$this->componentFactory = $componentFactory;
		$this->name = $name;
		$this->setDataType($dataType);
		if ($dataType != '') $this->datatypeValidator = $this->componentFactory->getComponent('F3_FLOW3_Validation_Validator_' . $dataType);
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
	 * @return F3_FLOW3_MVC_Controller_Argument $this
	 * @throws InvalidArgumentException if $shortName is not a character
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setShortName($shortName) {
		if ($shortName !== NULL && (!is_string($shortName) || F3_PHP6_Functions::strlen($shortName) != 1)) throw new InvalidArgumentException('$shortName must be a single character or NULL', 1195824959);
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
	 * @return F3_FLOW3_MVC_Controller_Argument $this
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setDataType($dataType) {
		$this->dataType = $dataType;
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
	 * Sets the value of this argument.
	 *
	 * @param mixed $value: The value of this argument
	 * @return F3_FLOW3_MVC_Controller_Argument $this
	 * @throws F3_FLOW3_MVC_Exception_InvalidArgumentValue if the argument is not a valid object of type $dataType
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setValue($value) {
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
	 * @return F3_FLOW3_MVC_Controller_Argument		$this
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setShortHelpMessage($message) {
		if (!is_string($message)) throw new InvalidArgumentException('The help message must be of type string, ' . gettype($message) . 'given.', 1187958170);
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
	 * Add an initialization error (e.g. a mapping error or warning)
	 *
	 * @param F3_FLOW3_Error_Error An error object
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addError(F3_FLOW3_Error_Error $error) {
		$this->errors[] = $error;
	}

	/**
	 * Get all initialization errors
	 *
	 * @param F3_FLOW3_Error_Error An error object
	 * @return array An array containing F3_FLOW3_Error_Error object
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @see addError(F3_FLOW3_Error_Error $error)
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Set an additional validator
	 *
	 * @param string Class name of a validator
	 * @return F3_FLOW3_MVC_Controller_Argument Returns $this (used for fluent interface)
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setValidator($className) {
		$this->validator = $this->componentFactory->getComponent($className);
		return $this;
	}

	/**
	 * Returns the set validator
	 *
	 * @return F3_FLOW3_Validation_ValidatorInterface The set validator, NULL if none was set
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getValidator() {
		return $this->validator;
	}

	/**
	 * Returns the set datatype validator
	 *
	 * @return F3_FLOW3_Validation_ValidatorInterface The set datatype validator
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getDatatypeValidator() {
		return $this->datatypeValidator;
	}

	/**
	 * Set a filter
	 *
	 * @param string Class name of a filter
	 * @return F3_FLOW3_MVC_Controller_Argument Returns $this (used for fluent interface)
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setFilter($className) {
		$this->filter = $this->componentFactory->getComponent($className);
		return $this;
	}

	/**
	 * Create and set a filter chain
	 *
	 * @param array Class names of the filters
	 * @return F3_FLOW3_MVC_Controller_Argument Returns $this (used for fluent interface)
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewFilterChain(array $classNames) {
		$this->filter = $this->createNewFilterChainObject();

		foreach ($classNames as $className) {
			$this->filter->addFilter($this->componentFactory->getComponent($className));
		}

		return $this;
	}

	/**
	 * Create and set a validator chain
	 *
	 * @param array Class names of the validators
	 * @return F3_FLOW3_MVC_Controller_Argument Returns $this (used for fluent interface)
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewValidatorChain(array $classNames) {
		$this->validator = $this->createNewValidatorChainObject();

		foreach ($classNames as $className) {
			$this->validator->addValidator($this->componentFactory->getComponent($className));
		}

		return $this;
	}

	/**
	 * Returns the set filter
	 *
	 * @return F3_FLOW3_Validation_FilterInterface The set filter, NULL if none was set
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getFilter() {
		return $this->filter;
	}

	/**
	 * Set a property editor
	 *
	 * @param string Class name of a property editor
	 * @return F3_FLOW3_MVC_Controller_Argument Returns $this (used for fluent interface)
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setPropertyEditor($className) {
		$this->propertyEditor = $this->componentFactory->getComponent($className);
		return $this;
	}

	/**
	 * Returns the set property editor
	 *
	 * @return F3_FLOW3_Property_EditorInterface The set property editor, NULL if none was set
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPropertyEditor() {
		return $this->propertyEditor;
	}

	/**
	 * Set a property editor input format
	 *
	 * @param string Input format the property editor should use
	 * @return F3_FLOW3_MVC_Controller_Argument Returns $this (used for fluent interface)
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setPropertyEditorInputFormat($format) {
		$this->propertyEditorInputFormat = $format;
		return $this;
	}

	/**
	 * Returns the set property editor input format
	 *
	 * @return string The set property editor input format
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPropertyEditorInputFormat() {
		return $this->propertyEditorInputFormat;
	}

	/**
	 * Factory method that creates a new filter chain
	 *
	 * @return F3_FLOW3_Validation_Filter_Chain A new filter chain
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function createNewFilterChainObject() {
		return $this->componentFactory->getComponent('F3_FLOW3_Validation_Filter_Chain');
	}

	/**
	 * Factory method that creates a new validator chain
	 *
	 * @return F3_FLOW3_Validation_Validator_Chain A new validator chain
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function createNewValidatorChainObject() {
		return $this->componentFactory->getComponent('F3_FLOW3_Validation_Validator_Chain');
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