<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Package;

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
 * @package FLOW3
 * @subpackage Package
 * @version $Id$
 */

/**
 * Interface for TYPO3 Package MetaData information
 *
 * @package FLOW3
 * @subpackage Package
 * @version $Id$
 * @author Christopher Hlubek <hlubek@networkteam.com>
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface MetaDataInterface {

	/**
	 * @return string The package key
	 */
	public function getPackageKey();

	/**
	 * @return string The package title
	 */
	public function getTitle();

	/**
	 * @return string The package version
	 */
	public function getVersion();

	/**
	 * @return string The package description
	 */
	public function getDescription();

	/**
	 * @return string The package state
	 */
	public function getState();

	/**
	 * @return Array of string The package categories
	 */
	public function getCategories();

	/**
	 * @return Array of F3\FLOW3\Package\MetaData\Party The package parties
	 */
	public function getParties();

	/**
	 * @param string $constraintType: Type of the constraints to get: depends, conflicts, suggests
	 * @return Array of F3\FLOW3\Package\MetaData\Constraint Package constraints
	 */
	public function getConstraintsByType($constraintType);

	/**
	 * Get all constraints
	 *
	 * @return array An array of array of \F3\FLOW3\Package\MetaData\Constraint Package constraints
	 */
	public function getConstraints();
}
?>