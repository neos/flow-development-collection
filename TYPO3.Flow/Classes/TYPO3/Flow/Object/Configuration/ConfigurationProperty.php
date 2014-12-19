<?php
namespace TYPO3\Flow\Object\Configuration;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Injection property as used in a Object Configuration
 *
 * @Flow\Proxy(false)
 */
class ConfigurationProperty {

	const PROPERTY_TYPES_STRAIGHTVALUE = 0;
	const PROPERTY_TYPES_OBJECT = 1;
	const PROPERTY_TYPES_CONFIGURATION = 2;

	/**
	 * @var string Name of the property
	 */
	protected $name;

	/**
	 * @var mixed Value of the property
	 */
	protected $value;

	/**
	 * @var integer Type of the property - one of the PROPERTY_TYPE_* constants
	 */
	protected $type;

	/**
	 * If specified, this configuration is used for instantiating / retrieving an property of type object
	 * @var \TYPO3\Flow\Object\Configuration\Configuration
	 */
	protected $objectConfiguration = NULL;

	/**
	 * @var integer
	 */
	protected $autowiring = \TYPO3\Flow\Object\Configuration\Configuration::AUTOWIRING_MODE_ON;

	/**
	 * Should this property be lazy loaded
	 *
	 * @var boolean
	 */
	protected $lazyLoading = TRUE;

	/**
	 * Constructor - sets the name, type and value of the property
	 *
	 * @param string $name Name of the property
	 * @param mixed $value Value of the property
	 * @param integer $type Type of the property - one of the PROPERTY_TYPE_* constants
	 * @param \TYPO3\Flow\Object\Configuration\Configuration $objectConfiguration If $type is OBJECT, a custom object configuration may be specified
	 * @param boolean $lazyLoading
	 */
	public function __construct($name, $value, $type = self::PROPERTY_TYPES_STRAIGHTVALUE, $objectConfiguration = NULL, $lazyLoading = TRUE) {
		$this->set($name, $value, $type, $objectConfiguration, $lazyLoading);
	}

	/**
	 * Sets the name, type and value of the property
	 *
	 * @param string $name Name of the property
	 * @param mixed $value Value of the property
	 * @param integer $type Type of the property - one of the PROPERTY_TYPE_* constants
	 * @param \TYPO3\Flow\Object\Configuration\Configuration $objectConfiguration If $type is OBJECT, a custom object configuration may be specified
	 * @param boolean $lazyLoading
	 * @return void
	 */
	public function set($name, $value, $type = self::PROPERTY_TYPES_STRAIGHTVALUE, $objectConfiguration = NULL, $lazyLoading = TRUE) {
		$this->name = $name;
		$this->value = $value;
		$this->type = $type;
		$this->objectConfiguration = $objectConfiguration;
		$this->lazyLoading = $lazyLoading;
	}

	/**
	 * Returns the name of the property
	 *
	 * @return string Name of the property
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns the value of the property
	 *
	 * @return mixed Value of the property
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Returns the type of the property
	 *
	 * @return integer Type of the property
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Returns the (optional) object configuration which may be defined for properties of type OBJECT
	 *
	 * @return \TYPO3\Flow\Object\Configuration\Configuration The object configuration or NULL
	 */
	public function getObjectConfiguration() {
		return $this->objectConfiguration;
	}

	/**
	 * Sets autowiring for this property
	 *
	 * @param integer $autowiring One of the \TYPO3\Flow\Object\Configuration\Configuration::AUTOWIRING_MODE_* constants
	 * @return void
	 */
	public function setAutowiring($autowiring) {
		$this->autowiring = $autowiring;
	}

	/**
	 * Returns the autowiring mode for this property
	 *
	 * @return integer Value of one of the \TYPO3\Flow\Object\Configuration\Configuration::AUTOWIRING_MODE_* constants
	 */
	public function getAutowiring() {
		return $this->autowiring;
	}

	/**
	 * If this property can be lazy loaded if the dependency injection mechanism offers that.
	 *
	 * @return boolean
	 */
	public function isLazyLoading() {
		return $this->lazyLoading;
	}

}
