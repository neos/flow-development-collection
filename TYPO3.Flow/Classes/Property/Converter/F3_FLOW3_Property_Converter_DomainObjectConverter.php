<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Property\Converter;

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
 * Domain Object converter. Used to automatically bind arrays to objects when validating input arguments.
 *
 * @package FLOW3
 * @subpackage Property
 * @version $Id: F3_FLOW3_Property_ConverterInterface.php 1711 2009-01-07 21:51:23Z sebastian $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 * @scope prototype
 */
class DomainObjectConverter implements \F3\FLOW3\Property\ConverterInterface, \F3\FLOW3\Property\Converter\IdentifierAwareInterface {

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
	 * Identifier for current domain object, if set
	 * @var string
	 */
	protected $identifier = NULL;

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
	 * @param string $objectName Object name which is the native property for this Domain Object converter.
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
	 * @param  object $property The property
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
	 * @return object The native property
	 * @throws \F3\FLOW3\Property\Exception\InvalidProperty if no property has been set yet
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getProperty() {
		return $this->domainObject;
	}

	/**
	 * Sets the property using the given format.
	 *
	 * @param string $format The format the property currently has. Must be in the array which is returned by getSupportedFormats().
	 * @param object $property The property to be set.
	 * @return void
	 * @throws \F3\FLOW3\Property\Exception\InvalidFormat if the property editor does not support the given format
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setAsFormat($format, $property) {
		if ($format !== 'array') {
			throw new \F3\FLOW3\Property\Exception\InvalidFormat('Only array format expected, ' . $format . ' given.', 1231017916);
		}

		$this->domainObject = $this->objectFactory->create($this->domainObjectName);
		$this->propertyMapper->map(new \ArrayObject($property), $this->domainObject);
		if (array_key_exists('identifier', $property)) {
			$this->identifier = $property['identifier'];
		}
	}

	/**
	 * Get the property in the given format.
	 *
	 * @param string $format The format in which the property should be returned. Must be in the array which is returned by getSupportedFormats().
	 * @return object The property in the given format.
	 * @throws \F3\FLOW3\Property\Exception\InvalidFormat if the property editor does not support the given format
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getAsFormat($format) {
		throw new \F3\FLOW3\Property\Exception\InvalidFormat('This property editor currently does not support bidirectional conversions.', 1231017919);
	}

	/**
	 * Get identifier of the last converted object, if it has one.
	 *
	 * @return string The string representation of the identifier of the last converted object.
	 */
	public function getIdentifier() {
		return $this->identifier;
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

}
?>