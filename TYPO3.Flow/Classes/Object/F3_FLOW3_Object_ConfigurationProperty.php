<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object;

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
 * @subpackage Object
 * @version $Id:\F3\FLOW3\Object\ConfigurationProperty.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Injection property as used in a Object Configuration
 *
 * @package FLOW3
 * @subpackage Object
 * @version $Id:\F3\FLOW3\Object\ConfigurationProperty.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ConfigurationProperty {

	const PROPERTY_TYPES_STRAIGHTVALUE = 0;
	const PROPERTY_TYPES_REFERENCE = 1;

	/**
	 * @var string Name of the property
	 */
	private $name;

	/**
	 * @var mixed Value of the property
	 */
	private $value;

	/**
	 * @var integer Type of the property - one of the PROPERTY_TYPE_* constants
	 */
	private $type;

	/**
	 * Constructor - sets the name, type and value of the property
	 *
	 * @param  string $name Name of the property
	 * @param  mixed $value Value of the property
	 * @param  integer $type Type of the property - one of the PROPERTY_TYPE_* constants
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($name, $value, $type = self::PROPERTY_TYPES_STRAIGHTVALUE) {
		$this->set($name, $value, $type);
	}

	/**
	 * Sets the name, type and value of the property
	 *
	 * @param  string $name Name of the property
	 * @param  mixed $value Value of the property
	 * @param  integer $type Type of the property - one of the PROPERTY_TYPE_* constants
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function set($name, $value, $type = self::PROPERTY_TYPES_STRAIGHTVALUE) {
		if (!is_string($name)) throw new \RuntimeException('$name must be of type string', 1168003690);
		if (!is_integer($type) || $type < 0 || $type > 1) throw new \RuntimeException('$type is not valid', 1168003691);
		$this->name = $name;
		$this->value = $value;
		$this->type = $type;
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
}

?>