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
 * A privilege parameter definition
 */
class PrivilegeParameterDefinition {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $parameterClassName;

	/**
	 * @param string $name
	 * @param string $parameterClassName
	 */
	public function __construct($name, $parameterClassName) {
		$this->name = $name;
		$this->parameterClassName = $parameterClassName;
	}
	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getParameterClassName() {
		return $this->parameterClassName;
	}

}