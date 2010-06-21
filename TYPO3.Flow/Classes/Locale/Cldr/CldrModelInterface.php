<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Locale\Cldr;

/* *
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
 * An interface for a model representing data from a CLDR file(s).
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @author Karol Gusak <firstname@lastname.eu>
 * @scope prototype
 */
interface CldrModelInterface {

	/**
	 * Returns multi-dimensional array representing desired node and it's children.
	 *
	 * @param string $path A path to the node to get
	 * @return mixed Array of matching data, or FALSE on failure
	 */
	public function getRawArray($path);

	/**
	 * Returns string element from a path given.
	 *
	 * @param string $path A path to the element to get
	 * @return mixed String with desired element, or FALSE on failure
	 */
	public function getOneElement($path);

	/**
	 * Parses the attributes string and returns a value of desired attribute.
	 *
	 * @param string $attribute An attribute to parse
	 * @param int $attributeNumber Index of attribute to get value for, starting from 1
	 * @return mixed Value of desired attribute, or FALSE if there is no such attribute
	 */
	public function getValueOfAttribute($attribute, $attributeNumber);
}

?>