<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Property;

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
 * @subpackage Property
 * @version $Id$
 */

/**
 * Contract for a Property Editor.
 * 
 * Property Editors are used to convert from a native representation of some data to various other formats, and back.
 *
 * Many Property Editors can handle strings as format, and some can handle arrays.
 * 
 * @package FLOW3
 * @subpackage Property
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
interface EditorInterface {

	/**
	 * Sets the native representation of a property.
	 *
	 * @param  object $property: The property
	 * @return void
	 * @throws \F3\FLOW3\Property\Exception\InvalidFormat if the value of this property object type can't be edited by this editor
	 */
	public function setProperty($property);

	/**
	 * Get the native representation of a property.
	 * 
	 * @return object The edited property
	 * @throws \F3\FLOW3\Property\Exception\InvalidProperty if no property has been set yet
	 */
	public function getProperty();

	/**
	 * Sets the property using the given format.
	 *
	 * @param string The format the property currently has. Must be in the array which is returned by getSupportedFormats().
	 * @param object The property to be set.
	 * @return void
	 * @throws \F3\FLOW3\Property\Exception\InvalidFormat if the property editor does not support the given format
	 */
	public function setAsFormat($format, $property);

	/**
	 * Get the property in the given format.
	 *
	 * @param string The format in which the property should be returned. Must be in the array which is returned by getSupportedFormats().
	 * @return object The property in the given format.
	 * @throws \F3\FLOW3\Property\Exception\InvalidFormat if the property editor does not support the given format
	 */
	public function getAsFormat($format);

	/**
	 * Return all supported formats as an array.
	 *
	 * @return array All supported formats
	 */
	public function getSupportedFormats();
}

?>