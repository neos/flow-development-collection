<?php
namespace F3\FLOW3\Package\MetaData;

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
 * System constraint meta model
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class SystemConstraint extends \F3\FLOW3\Package\MetaData\AbstractConstraint {

	/**
	 * The type for a system scope constraint (e.g. "Memory")
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Meta data system constraint constructor
	 *
	 * @param string $constraintType
	 * @param string $type
	 * @param string $value
	 * @param string $minVersion
	 * @param string $maxVersion
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function __construct($constraintType, $type, $value = NULL, $minVersion = NULL, $maxVersion = NULL) {
		if (!strlen($value)) $value = NULL;
		parent::__construct($constraintType, $value, $minVersion, $maxVersion);
		$this->type = $type;
	}

	/**
	 * @return string The system constraint type
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @return string The constraint scope
	 * @see \F3\FLOW3\Package\MetaData\Constraint\getConstraintScope()
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getConstraintScope() {
		return \F3\FLOW3\Package\MetaDataInterface::CONSTRAINT_SCOPE_SYSTEM;
	}
}
?>