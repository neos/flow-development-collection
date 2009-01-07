<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Property\Converter;

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
 * Base (abstract) class for an extensible Property Converter. It does not implement any converter functionality.
 * It is meant to be extended to build extensible converters.
 *
 * @package FLOW3
 * @subpackage Property
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
abstract class AbstractCompositeConverter implements \F3\FLOW3\Property\ConverterInterface {

	/**
	 * var array The registered extension converters
	 */
	protected $propertyConverters = array();

	/**
	 * var object The property
	 */
	protected $property = NULL;

	/**
	 * Register a new format, the converter will support in the future
	 *
	 * @param string The name of the format
	 * @param \F3\FLOW3\Property\ConverterInterface The property Converter that can do the conversion to and from the given format.
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo this should be just a setter used by the configuration
	 */
	public function registerNewFormat($name, \F3\FLOW3\Property\ConverterInterface $propertyConverter) {
		$this->propertyConverters[$name] = $propertyConverter;
	}

	/**
	 * Remove a previously registered format from the converter. Note: Built in formats can't be removed.
	 *
	 * @param string The name of the format that should be removed
	 * @return void
	 * @throws \F3\FLOW3\Property\Exception\InvalidFormat if the given format can't be removed
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function removeFormat($name) {
		if (!isset($this->propertyConverters[$name])) throw new \F3\FLOW3\Property\Exception\InvalidFormat('Format cannot be removed.', 1210858932);
		unset($this->propertyConverters[$name]);
	}

	/**
	 * Sets the property using the given format.
	 *
	 * @param string The format the property currently has.
	 * @param object The property to be set.
	 * @return void
	 * @throws \F3\FLOW3\Property\Exception\InvalidFormat if the property converter does not support the given format
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setAsFormat($format, $property) {
		if (!isset($this->propertyConverters[$format])) throw new \F3\FLOW3\Property\Exception\InvalidFormat('Format not supported.', 1210858950);

		$this->propertyConverters[$format]->setAsFormat($format, $property);
	}

	/**
	 * Get the property in the given format.
	 *
	 * @param string The format in which the property should be returned.
	 * @return object The property in the given format.
	 * @throws \F3\FLOW3\Property\Exception\InvalidFormat if the property converter does not support the given format
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getAsFormat($format) {
		if (!isset($this->propertyConverters[$format])) throw new \F3\FLOW3\Property\Exception\InvalidFormat('Format not supported.', 1210858967);

		return $this->propertyConverters[$format]->getAsFormat($format);
	}
}

?>