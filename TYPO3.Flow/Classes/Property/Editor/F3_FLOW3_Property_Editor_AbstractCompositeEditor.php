<?php
declare(ENCODING = 'utf-8');

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
 * @version $Id: F3_FLOW3_Property_Editor_F3_FLOW3_Property_Editor_CompositeEditorAbstractCompositeEditor.php 661 2008-03-25 14:03:49Z robert $
 */

/**
 * Base (abstract) class for an extensible Property Editor. It does not implement any editor functionality.
 * It is meant to be extended to build extensible editors.
 *
 * @package FLOW3
 * @subpackage Property
 * @version $Id: F3_FLOW3_Property_Editor_F3_FLOW3_Property_Editor_CompositeEditorAbstractCompositeEditor.php 661 2008-03-25 14:03:49Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
abstract class F3_FLOW3_Property_Editor_AbstractCompositeEditor implements F3_FLOW3_Property_EditorInterface {

	/**
	 * var array The registered extension editors
	 */
	protected $propertyEditors = array();

	/**
	 * var object The property
	 */
	protected $property = NULL;

	/**
	 * Register a new format, the editor will support in the future
	 *
	 * @param string The name of the format
	 * @param F3_FLOW3_Property_EditorInterface The property Editor that can do the editing to and from the given format.
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo this should be just a setter used by the configuration
	 */
	public function registerNewFormat($name, F3_FLOW3_Property_EditorInterface &$propertyEditor) {
		$this->propertyEditors[$name] = $propertyEditor;
	}

	/**
	 * Remove a previously registered format from the editor. Note: Built in formats can't be removed.
	 *
	 * @param string The name of the format that should be removed
	 * @return void
	 * @throws F3_FLOW3_Property_Exception_InvalidFormat if the given format can't be removed
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function removeFormat($name) {
		if (!isset($this->propertyEditors[$name])) throw new F3_FLOW3_Property_Exception_InvalidFormat('Format cannot be removed.', 1210858932);
		unset($this->propertyEditors[$name]);
	}

	/**
	 * Sets the property using the given format.
	 *
	 * @param string The format the property currently has.
	 * @param object The property to be set.
	 * @return void
	 * @throws F3_FLOW3_Property_Exception_InvalidFormat if the property editor does not support the given format
	 */
	public function setAs($format, $property) {
		if (!isset($this->propertyEditors[$format])) throw new F3_FLOW3_Property_Exception_InvalidFormat('Format not supported.', 1210858950);

		$this->propertyEditors[$format]->setAs($format, $property);
	}

	/**
	 * Get the property in the given format.
	 *
	 * @param string The format in which the property should be returned.
	 * @return object The property in the given format.
	 * @throws F3_FLOW3_Property_Exception_InvalidFormat if the property editor does not support the given format
	 */
	public function getAs($format) {
		if (!isset($this->propertyEditors[$format])) throw new F3_FLOW3_Property_Exception_InvalidFormat('Format not supported.', 1210858967);

		return $this->propertyEditors[$format]->getAs($format);
	}
}

?>