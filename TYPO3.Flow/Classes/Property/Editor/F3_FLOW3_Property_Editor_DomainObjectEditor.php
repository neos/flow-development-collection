<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Property\Editor;

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
 * @version $Id:$
 */
/**
 * Domain Object editor. Used to automatically bind arrays to objects when validating input arguments.
 *
 * @scope prototype
 */
class DomainObjectEditor implements \F3\FLOW3\Property\EditorInterface {

	/**
	 * The domain object which is processed.
	 * @var object
	 */
	protected $domainObject;
	
	/**
	 * Name of the domain object.
	 * @var string
	 */
	protected $domainObjectName;
	
	/**
	 * Property Mapper used to map this complex object.
	 * @var F3\FLOW3\Property\Mapper
	 */
	protected $propertyMapper;
	
	/**
	 * Object factory.
	 * @var F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;
	
	/**
	 * Constructor.
	 * 
	 * @param string $objectName: Object name which is the native property for this Domain Object editor.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function __construct($objectName) {
		$this->domainObjectName = $objectName;
	}
	
	/**
	 * Inject the property mapper.
	 * 
	 * @param F3\FLOW3\Property\Mapper $propertyMapper
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function injectPropertyMapper(\F3\FLOW3\Property\Mapper $propertyMapper) {
		$this->propertyMapper = $propertyMapper;
	}
	
	/**
	 * Inject an object factory.
	 * 
	 * @param F3\FLOW3\Object\FactoryInterface $objectFactory
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}
	
	/**
	 * Sets the domain object, which is the native representation of a property.
	 *
	 * @param  object $property: The property
	 * @return void
	 * @throws \F3\FLOW3\Property\Exception\InvalidFormat if the value of this property object type can't be edited by this editor
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setProperty($property) {
		$this->domainObject = $property;
	}

	/**
	 * Get the domain object, which is the native representation of a property.
	 * 
	 * @return object The edited property
	 * @throws \F3\FLOW3\Property\Exception\InvalidProperty if no property has been set yet
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getProperty() {
		return $this->domainObject;
	}

	/**
	 * Sets the property using the given format.
	 *
	 * @param string The format the property currently has. Must be in the array which is returned by getSupportedFormats().
	 * @param object The property to be set.
	 * @return void
	 * @throws \F3\FLOW3\Property\Exception\InvalidFormat if the property editor does not support the given format
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @todo Use custom factory which creates the instance of the object.
	 */
	public function setAsFormat($format, $property) {
		if ($format !== 'array') {
			throw new \F3\FLOW3\Property\Exception\InvalidFormat('Only array format expected, ' . $format . ' given.', 1231017916);
		}
		
		$targetObject = $this->retrieveTargetObject();
		$this->propertyMapper->setTarget($targetObject);
		$this->propertyMapper->map(new \ArrayObject($property));
		$this->domainObject = $targetObject;
	
	}

	/**
	 * Get the property in the given format.
	 *
	 * @param string The format in which the property should be returned. Must be in the array which is returned by getSupportedFormats().
	 * @return object The property in the given format.
	 * @throws \F3\FLOW3\Property\Exception\InvalidFormat if the property editor does not support the given format
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getAsFormat($format) {
		throw new \F3\FLOW3\Property\Exception\InvalidFormat('This property editor currently does not support bidirectional conversions.', 1231017919);
	}

	/**
	 * Return all supported formats as an array.
	 *
	 * @return array All supported formats
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getSupportedFormats() {
		return array('array');
	}
	
	/**
	 * Retrieve a new target object. Can use a repository or some special logic to retrieve/create the target object.
	 * 
	 * @return object The target object.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function retrieveTargetObject() {
		return $this->objectFactory->create($this->domainObjectName);
	}
}
?>