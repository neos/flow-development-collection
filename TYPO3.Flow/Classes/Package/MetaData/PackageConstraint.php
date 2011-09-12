<?php
namespace TYPO3\FLOW3\Package\MetaData;

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
 * Package constraint meta model
 *
 * @scope prototype
 */
class PackageConstraint extends \TYPO3\FLOW3\Package\MetaData\AbstractConstraint {

	/**
	 * @return string The constraint scope
	 * @see \TYPO3\FLOW3\Package\MetaData\Constraint::getConstraintScope()
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getConstraintScope() {
		return \TYPO3\FLOW3\Package\MetaDataInterface::CONSTRAINT_SCOPE_PACKAGE;
	}
}
?>