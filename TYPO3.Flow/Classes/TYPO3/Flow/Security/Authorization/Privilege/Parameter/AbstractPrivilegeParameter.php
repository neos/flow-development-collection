<?php
namespace TYPO3\Flow\Security\Authorization\Privilege\Parameter;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A privilege parameter
 */
abstract class AbstractPrivilegeParameter implements PrivilegeParameterInterface {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var mixed
	 */
	protected $value;

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	function __construct($name, $value) {
		$this->name = $name;
		$this->value = $value;
	}

	/**
	 * Name of this parameter
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * The value of this parameter
	 *
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Returns the string representation of this parameter
	 * @return string
	 */
	public function __toString() {
		return (string)$this->getValue();
	}
}