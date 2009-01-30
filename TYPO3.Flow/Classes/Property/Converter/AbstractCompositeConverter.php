<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Property\Converter;

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
 * Base (abstract) class for an extensible Property Converter. It does not implement any converter functionality.
 * It is meant to be extended to build extensible converters.
 *
 * @package FLOW3
 * @subpackage Property
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class AbstractCompositeConverter implements \F3\FLOW3\Property\Converter\ConverterInterface {

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
	 * @param string $name The name of the format
	 * @param \F3\FLOW3\Property\Converter\ConverterInterface $propertyConverter The property Converter that can do the conversion to and from the given format.
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo this should be just a setter used by the configuration
	 */
	public function registerNewFormat($name, \F3\FLOW3\Property\Converter\ConverterInterface $propertyConverter) {
		$this->propertyConverters[$name] = $propertyConverter;
	}

	/**
	 * Remove a previously registered format from the converter. Note: Built in formats can't be removed.
	 *
	 * @param string $name The name of the format that should be removed
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
	 * @param string $format The format the property currently has.
	 * @param object $property The property to be set.
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
	 * @param string $format The format in which the property should be returned.
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