<?php
declare(encoding = 'utf-8');

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
 * Contract for a Property Editor
 * 
 * @package		FLOW3
 * @subpackage	Property
 * @version 	$Id$
 * @copyright	Copyright belongs to the respective authors
 * @author Robert Lemke <robert@typo3.org>
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface T3_FLOW3_Property_EditorInterface {

	/**
	 * Sets the property which is going to be edited.
	 * 
	 * @param  object		$property: The property
	 * @return void
	 */
	public function setProperty($property);

	/**
	 * 
	 */
	public function getProperty();
	
	/**
	 * Returns a text representation of the property
	 *
	 * @return string			This property object as text
	 * @throws T3_FLOW3_Property_Exception_InvalidProperty if no property has been set yet
	 */
	public function getAsText();
	
	/**
	 * Sets the value of this property by using the given text. 
	 * 
	 * @param  string			$text: Text, used for setting the value of this property object
	 * @return void
	 * @throws T3_FLOW3_Property_Exception_InvalidFormat if the value of this property object type can't be set via text
	 * @throws T3_FLOW3_Property_Exception_InvalidProperty if no property has been set yet
	 */
	public function setAsText($text);
	
	/**
	 * Alias of getAsText()
	 *
	 * @return string			String value
	 * @see getAsText()
	 */
	public function __toString();
}

?>