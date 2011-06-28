<?php
namespace TYPO3\FLOW3\Security\Authorization\Resource;

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
 * Special configuration like access restrictions for persistent resources
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 * @entity
 */
class SecurityPublishingConfiguration implements \TYPO3\FLOW3\Resource\Publishing\PublishingConfigurationInterface {

	/**
	 * @var array
	 */
	protected $allowedRoles = array();

	/**
	 * Sets the roles that are allowed to see the corresponding resource
	 *
	 * @param array<\TYPO3\FLOW3\Security\Policy\Role> $allowedRoles An array of roles
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setAllowedRoles(array $allowedRoles) {
		$this->allowedRoles = $allowedRoles;
	}

	/**
	 * Returns the roles that are allowed to see the corresponding resource
	 *
	 * @return array An array of roles
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getAllowedRoles() {
		return $this->allowedRoles;
	}
}

?>
