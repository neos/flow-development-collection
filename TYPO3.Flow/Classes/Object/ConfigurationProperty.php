<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object;

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
 * @subpackage Object
 * @version $Id$
 */

/**
 * Injection property as used in a Object Configuration
 *
 * @package FLOW3
 * @subpackage Object
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
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
	 * @var \F3\FLOW3\Object\Configuration
	 */
	protected $objectConfiguration = NULL;

	/**
	 * Constructor - sets the name, type and value of the property
	 *
	 * @param  string $name Name of the property
	 * @param  mixed $value Value of the property
	 * @param  integer $type Type of the property - one of the PROPERTY_TYPE_* constants
	 * @param \F3\FLOW3\Object\Configuration $objectConfiguration If $type is OBJECT, a custom object configuration may be specified
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
	 * @param \F3\FLOW3\Object\Configuration $objectConfiguration If $type is OBJECT, a custom object configuration may be specified
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function set($name, $value, $type = self::PROPERTY_TYPES_STRAIGHTVALUE, $objectConfiguration = NULL) {
		if (!is_string($name)) throw new \InvalidArgumentException('$name must be of type string', 1168003690);
		if (!is_integer($type) || $type < 0 || $type > 2) throw new \InvalidArgumentException('$type is not valid', 1168003691);
		if ($objectConfiguration !== NULL && $type !== self::PROPERTY_TYPES_OBJECT) throw new InvalidArgumentException('A custom object configuration has been specified for property "' . $name . '" but the proeprty type is not object.', 1230549771);
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
	 * @return \F3\FLOW3\Object\Configuration The object configuration or NULL
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectConfiguration() {
		return $this->objectConfiguration;
	}
}

?>