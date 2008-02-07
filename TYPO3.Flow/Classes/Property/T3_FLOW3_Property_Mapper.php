<?php
declare(encoding = 'utf-8');

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
 * $mapper = $componentManager->getComponent('T3_FLOW3_Property_Mapper', $target);
 * $mapper->map($source);
 * 
 * Now the target object equals the source object.
 * 
 * @package  FLOW3
 * @subpackage	Property
 * @version 	$Id:T3_FLOW3_Property_Mapper.php 467 2008-02-06 19:34:56Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 *
 * @scope prototype
 */
class T3_FLOW3_Property_Mapper {

	/**
	 * @var object Target object
	 */
	protected $target;
	
	/**
	 * @var T3_FLOW3_Property_MappingResult Result of the last data mapping
	 */
	protected $mappingResult = NULL;

	/**
	 * @var array
	 */
	protected $errors = array();
	
	/**
	 * @var array An array of allowed property names (or regular expressions matching those)
	 */
	protected $allowedProperties = array('.*');
	
	/**
	 * @var array An array of names of the required properties (or regular expressions matching those)
	 */
	protected $requiredProperties = array();
		
	/**
	 * Sets the target object for this Property Mapper
	 * 
	 * @param  object					$target: The target object the Property Values are bound to
	 * @throws T3_FLOW3_Property_Exception_InvalidTargetObject if the $target is no valid object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setTarget($target) {
		if (!is_object($target)) throw new T3_FLOW3_Property_Exception_InvalidTargetObject('The target object must be a valid object, ' . gettype($target) . ' given.', 1187807099);
		$this->target = $target;
	}
	
	/**
	 * Returns the target of this Property Mapper
	 * 
	 * @return object					The target object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTarget() {
		if (!is_object($this->target)) throw new T3_FLOW3_Property_Exception_InvalidTargetObject('No target object has been defined yet.', 1187977962);
		return $this->target;
	}

	/**
	 * Defines the property names which are allowed for mapping.
	 *
	 * @param  array						$allowedProperties: An array of allowed property names. Each entry in this array may be a regular expression.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org> 
	 */
	public function setAllowedProperties(array $allowedProperties) {
		$this->allowedProperties = $allowedProperties;
	}

	/**
	 * Returns an array of strings defining the allowed properties for mapping.
	 * 
	 * @return array						The allowed properties
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAllowedProperties() {
		return $this->allowedProperties;
	}
	
	/**
	 * Defines the property names which are required.
	 *
	 * @param  array						$requiredProperties: An array of names of the requires properties. Each entry in this array may be a regular expression.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org> 
	 */
	public function setRequiredProperties(array $requiredProperties) {
		$this->requiredProperties = $requiredProperties;
	}

	/**
	 * Returns an array of strings defining the required properties.
	 * 
	 * @return array						The required properties
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRequiredProperties() {
		return $this->requiredProperties;
	}
	
	/**
	 * Registers the given Property Editor for use with the specified
	 * property type and (optionally) property name.
	 * 
	 * @param  T3_FLOW3_Property_EditorInterface		$propertyEditor: The property editor
	 * @param  string										$type: Class name of the properties which should be edited by this editor
	 * @param  string										$property: This editor should only be used for this property
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo   Implement
	 */
	public function registerPropertyEditor(T3_FLOW3_Property_EditorInterface $propertyEditor, $type, $property = '') {
		
	}

	/**
	 * Maps the given properties to the target object. 
	 * After mapping the results can be retrieved with getMappingResult.
	 * 
	 * @param  ArrayObject									$properties: Properties to map to the target object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function map(ArrayObject $properties) {
		if (!is_object($this->target)) throw new T3_FLOW3_Property_Exception_InvalidTargetObject('No target object has been defined yet.', 1187978014);
		foreach ($properties as $propertyName => $propertyValue) {
			if ($this->isAllowedProperty($propertyName)) {
				$this->setPropertyValue($propertyName, $propertyValue);			
			} else {
			}
		}
	}
	
	/**
	 * Returns an object containing the results of a mapping. Note that map() must be called 
	 * before mapping results are available.
	 * 
	 * @return T3_FLOW3_Security_MappingResult		Result of the last mapping - NULL if none is available
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMappingResult() {
		if (!is_object($this->target)) throw new T3_FLOW3_Property_Exception_InvalidTargetObject('No target object has been defined yet, so no mapping result exists.', 1187978053);
		return $this->mappingResult;	
	}
	
	/**
	 * Checks if the give property is among the allowed properties.
	 * 
	 * @param  string										$propertyName: Property name to check for
	 * @return boolean										TRUE if the property is allowed, else FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @see    map() 
	 */
	protected function isAllowedProperty($propertyName) {
		$isAllowed = FALSE;
		foreach ($this->allowedProperties as $allowedProperty) {
			if (preg_match('/^' . $allowedProperty . '$/', $propertyName) === 1) {
				$isAllowed = TRUE;
			}
		}
		return $isAllowed;
	}

	/**
	 * Sets the given value of the specified property at the target object.
	 * If the target object is an ArrayObject, the values are set directly
	 * through Array access, else a setter method is called (if one exists).
	 * 
	 * @param  string										$propertyName: Name of the property to set
	 * @param  string										$propertyValue: Value of the property
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see    map()
	 * @todo   Implement error logging into MappingResult
	 */
	protected function setPropertyValue($propertyName, $propertyValue) {
		$setterMethodName = 'set' . ucfirst($propertyName);
		try {
			if (is_callable(array($this->target, $setterMethodName))) {
				$this->target->$setterMethodName($propertyValue);
			} elseif ($this->target instanceof ArrayObject) {
				$this->target[$propertyName] = $propertyValue;
			}
		} catch (Exception $exception) {
			$this->errors[] = $exception;
		}
	}
}

?>