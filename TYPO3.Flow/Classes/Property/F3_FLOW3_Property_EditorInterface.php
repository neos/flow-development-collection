<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Property;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Property
 * @version $Id$
 */

/**
 * Contract for a Property Editor
 *
 * @package FLOW3
 * @subpackage Property
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface EditorInterface {

	/**
	 * Sets the property which is going to be edited.
	 *
	 * @param  object $property: The property
	 * @return void
	 * @throws F3::FLOW3::Property::Exception::InvalidFormat if the value of this property object type can't be edited by this editor
	 */
	public function setProperty($property);

	/**
	 * Get the edited property.
	 * @return object The edited property
	 * @throws F3::FLOW3::Property::Exception::InvalidProperty if no property has been set yet
	 */
	public function getProperty();

	/**
	 * Sets the value of this property by using the given text.
	 *
	 * @param  string $text: Text, used for setting the value of this property object
	 * @return void
	 * @throws F3::FLOW3::Property::Exception::InvalidFormat if the value of this property object type can't be set via text
	 */
	public function setAsString($string);

	/**
	 * Returns a text representation of the property
	 *
	 * @return string This property object as text
	 * @throws F3::FLOW3::Property::Exception::InvalidProperty if no property has been set yet
	 */
	public function getAsString();

	/**
	 * Sets the property using the given format.
	 *
	 * @param string The format the property currently has.
	 * @param object The property to be set.
	 * @return void
	 * @throws F3::FLOW3::Property::Exception::InvalidFormat if the property editor does not support the given format
	 */
	public function setAs($format, $property);

	/**
	 * Get the property in the given format.
	 *
	 * @param string The format in which the property should be returned.
	 * @return object The property in the given format.
	 * @throws F3::FLOW3::Property::Exception::InvalidFormat if the property editor does not support the given format
	 */
	public function getAs($format);

	/**
	 * Return all supported formats as an array
	 *
	 * @return array All supported formats
	 */
	public function getSupportedFormats();

	/**
	 * Alias of getAsString()
	 *
	 * @return string String value
	 * @throws F3::FLOW3::Property::Exception::InvalidProperty if no property has been set yet
	 * @see getAsText()
	 */
	public function __toString();
}

?>