<?php
namespace TYPO3\Flow\Security\Authorization\Resource;

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
 * Special configuration like access restrictions for persistent resources
 *
 * @Flow\Entity
 */
class SecurityPublishingConfiguration extends \TYPO3\Flow\Resource\Publishing\AbstractPublishingConfiguration {

	/**
	 * @var array
	 */
	protected $allowedRoles = array();

	/**
	 * Sets the roles that are allowed to see the corresponding resource
	 *
	 * @param array<\TYPO3\Flow\Security\Policy\Role> $allowedRoles An array of roles
	 * @return void
	 */
	public function setAllowedRoles(array $allowedRoles) {
		$this->allowedRoles = $allowedRoles;
	}

	/**
	 * Returns the roles that are allowed to see the corresponding resource
	 *
	 * @return array An array of roles
	 */
	public function getAllowedRoles() {
		return $this->allowedRoles;
	}
}
