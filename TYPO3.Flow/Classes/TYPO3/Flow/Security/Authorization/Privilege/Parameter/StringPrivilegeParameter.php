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
 * A privilege parameter of type string
 */
class StringPrivilegeParameter extends AbstractPrivilegeParameter {

	/**
	 * @return array
	 */
	public function getPossibleValues() {
		return NULL;
	}

	/**
	 * @param mixed $value
	 * @return boolean
	 */
	public function validate($value) {
		return is_string($value);
	}

	/**
	 * @return string
	 */
	public function getType() {
		return 'String';
	}
}