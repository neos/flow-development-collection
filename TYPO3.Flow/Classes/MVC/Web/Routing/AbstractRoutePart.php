<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Web\Routing;

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
 * abstract Route Part
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class AbstractRoutePart implements \F3\FLOW3\MVC\Web\Routing\RoutePartInterface {

	/**
	 * Name of the Route Part
	 *
	 * @var string
	 */
	protected $name = NULL;

	/**
	 * Value of the Route Part after decoding.
	 *
	 * @var mixed
	 */
	protected $value = NULL;

	/**
	 * Default value of the Route Part.
	 *
	 * @var mixed
	 */
	protected $defaultValue = NULL;

	/**
	 * Specifies whether this Route Part is optional. Which means it's put in parentheses in the routes URI pattern.
	 *
	 * @var boolean
	 */
	protected $isOptional = FALSE;

	/**
	 * Contains options for this Route Part.
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * Sets name of the Route Part.
	 *
	 * @param string $name
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setName($partName) {
		$this->name = $partName;
	}

	/**
	 * Returns name of the Route Part.
	 *
	 * @return string
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns TRUE if a value is set for this Route Part, otherwise FALSE.
	 *
	 * @return boolean
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function hasValue() {
		return isset($this->value);
	}

	/**
	 * Returns value of the Route Part. Before match() is called this returns NULL.
	 *
	 * @return mixed
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Returns TRUE if a default value is set for this Route Part, otherwise FALSE.
	 *
	 * @return boolean
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function hasDefaultValue() {
		return isset($this->defaultValue);
	}

	/**
	 * Sets default value of the Route Part.
	 *
	 * @param mixed $defaultValue
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setDefaultValue($defaultValue) {
		$this->defaultValue = $defaultValue;
	}

	/**
	 * Gets default value of the Route Part.
	 *
	 * @return mixed $defaultValue
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getDefaultValue() {
		return $this->defaultValue;
	}


	/**
	 * Specifies whether this Route part is optional.
	 *
	 * @param boolean $isOptional TRUE: this Route part is optional. FALSE: this Route part is required.
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setOptional($isOptional) {
		$this->isOptional = $isOptional;
	}

	/**
	 * Getter for $this->isOptional.
	 *
	 * @return boolean TRUE if this Route part is optional, otherwise FALSE.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @see setOptional()
	 */
	public function isOptional() {
		return $this->isOptional;
	}

	/**
	 * Defines options for this Route Part.
	 * Options can be used to enrich a route part with parameters or settings like case sensivitity.
	 *
	 * @param array $options
	 * @return void
	 */
	public function setOptions(array $options) {
		$this->options = $options;
	}

	/**
	 * @return array options of this Route Part.
	 */
	public function getOptions() {
		return $this->options;
	}

}
?>
