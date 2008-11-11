<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Object;

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
 * @version $Id:F3::FLOW3::Object::Configuration.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Injection (constructor-) argument as used in a Object Configuration
 *
 * @package FLOW3
 * @subpackage Object
 * @version $Id:F3::FLOW3::Object::Configuration.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class ConfigurationArgument {

	const ARGUMENT_TYPES_STRAIGHTVALUE = 0;
	const ARGUMENT_TYPES_REFERENCE = 1;

	/**
	 * @var integer	The position of the constructor argument. Counting starts at "1".
	 */
	protected $index;

	/**
	 * @var mixed The argument's value
	 */
	protected $value;

	/**
	 * @var integer Argument type, one of the ARGUMENT_TYPES_* constants
	 */
	protected $type;

	/**
	 * Constructor - sets the index, type and value of the argument
	 *
	 * @param string $index: Index of the argument
	 * @param mixed $value: Value of the argument
	 * @param integer $type: Type of the argument - one of the argument_TYPE_* constants
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($index, $value, $type = self::ARGUMENT_TYPES_STRAIGHTVALUE) {
		$this->set($index, $value, $type);
	}

	/**
	 * Sets the index, type and value of the argument
	 *
	 * @param integer $index: Index of the argument (counting starts at "1")
	 * @param mixed $value: Value of the argument
	 * @param integer $type: Type of the argument - one of the ARGUMENT_TYPE_* constants
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function set($index, $value, $type = self::ARGUMENT_TYPES_STRAIGHTVALUE) {
		if (!is_integer($index)) throw new RuntimeException('$index must be of type integer', 1168003692);
		if (!is_integer($type) || $type < 0 || $type > 1) throw new RuntimeException('$type is not valid', 1168003693);
		$this->index = $index;
		$this->value = $value;
		$this->type = $type;
	}

	/**
	 * Returns the index (position) of the argument
	 *
	 * @return string Index of the argument
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getIndex() {
		return $this->index;
	}

	/**
	 * Returns the value of the argument
	 *
	 * @return mixed Value of the argument
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Returns the type of the argument
	 *
	 * @return integer Type of the argument - one of the ARGUMENT_TYPES_* constants
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getType() {
		return $this->type;
	}
}
?>