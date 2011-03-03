<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object\Configuration;

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
 * Injection property as used in a Object Configuration
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 * @proxy disable
 */
class ConfigurationProperty {

	const PROPERTY_TYPES_STRAIGHTVALUE = 0;
	const PROPERTY_TYPES_OBJECT = 1;
	const PROPERTY_TYPES_SETTING = 2;

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
	 * @var \F3\FLOW3\Object\Configuration\Configuration
	 */
	protected $objectConfiguration = NULL;

	/**
	 * @var integer
	 */
	protected $autowiring = \F3\FLOW3\Object\Configuration\Configuration::AUTOWIRING_MODE_ON;

	/**
	 * Constructor - sets the name, type and value of the property
	 *
	 * @param string $name Name of the property
	 * @param mixed $value Value of the property
	 * @param integer $type Type of the property - one of the PROPERTY_TYPE_* constants
	 * @param \F3\FLOW3\Object\Configuration\Configuration $objectConfiguration If $type is OBJECT, a custom object configuration may be specified
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($name, $value, $type = self::PROPERTY_TYPES_STRAIGHTVALUE, $objectConfiguration = NULL) {
		$this->set($name, $value, $type, $objectConfiguration);
	}

	/**
	 * Sets the name, type and value of the property
	 *
	 * @param string $name Name of the property
	 * @param mixed $value Value of the property
	 * @param integer $type Type of the property - one of the PROPERTY_TYPE_* constants
	 * @param \F3\FLOW3\Object\Configuration\Configuration $objectConfiguration If $type is OBJECT, a custom object configuration may be specified
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function set($name, $value, $type = self::PROPERTY_TYPES_STRAIGHTVALUE, $objectConfiguration = NULL) {
		$this->name = $name;
		$this->value = $value;
		$this->type = $type;
		$this->objectConfiguration = $objectConfiguration;
	}

	/**
	 * Returns the name of the property
	 *
	 * @return string Name of the property
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns the value of the property
	 *
	 * @return mixed Value of the property
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Returns the type of the property
	 *
	 * @return integer Type of the property
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Returns the (optional) object configuration which may be defined for properties of type OBJECT
	 *
	 * @return \F3\FLOW3\Object\Configuration\Configuration The object configuration or NULL
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectConfiguration() {
		return $this->objectConfiguration;
	}

	/**
	 * Sets autowiring for this property
	 *
	 * @param integer $autowiring One of the \F3\FLOW3\Object\Configuration\Configuration::AUTOWIRING_MODE_* constants
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setAutowiring($autowiring) {
		$this->autowiring = $autowiring;
	}

	/**
	 * Returns the autowiring mode for this property
	 *
	 * @return integer Value of one of the \F3\FLOW3\Object\Configuration\Configuration::AUTOWIRING_MODE_* constants
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAutowiring() {
		return $this->autowiring;
	}

}

?>