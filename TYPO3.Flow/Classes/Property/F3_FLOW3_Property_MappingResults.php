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
 * @version $Id:\F3\FLOW3\Property\MappingResults.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * Description
 *
 * @package FLOW3
 * @subpackage Property
 * @version $Id:\F3\FLOW3\Property\MappingResults.php 467 2008-02-06 19:34:56Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 * @scope prototype
 */
class MappingResults {

	/**
	 * @var array An array of the occured errors
	 */
	protected $errors = array();

	/**
	 * @var array An array of the occured warnings
	 */
	protected $warnings = array();

	/**
	 * A list of found identifiers for each property.
	 *
	 * Key: Name of property
	 * Value: Identifier
	 *
	 * @var array
	 */
	protected $identifiers = array();

	/**
	 * Adds an error to the mapping results. This might be for example a
	 * validation or mapping error
	 *
	 * @param \F3\FLOW3\Error\Error $error The occured error
	 * @param string $propertyName The name of the property which caused the error
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addError(\F3\FLOW3\Error\Error $error, $propertyName) {
		$this->errors[$propertyName] = $error;
	}

	/**
	 * Returns all errors that occured so far
	 *
	 * @return array Array of \F3\FLOW3\Error\Error objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Returns true if any error was recognized
	 *
	 * @return boolean True if an error occured
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function hasErrors() {
		return (count($this->errors) > 0);
	}

	/**
	 * Adds a warning to the mapping results. This might be for example a
	 * property that could not be mapped but wasn't marked as required.
	 *
	 * @param \F3\FLOW3\Error\Warning $warning The occured warning
	 * @param string $propertyName The name of the property which caused the error
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addWarning(\F3\FLOW3\Error\Warning $warning, $propertyName) {
		$this->warnings[$propertyName] = $warning;
	}

	/**
	 * Returns all warnings that occured so far
	 *
	 * @return array Array of \F3\FLOW3\Error\Warning objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getWarnings() {
		return $this->warnings;
	}

	/**
	 * Add an $identifier for a $property to the mapping results.
	 *
	 * @param string $propertyName Name of the property
	 * @param string $identifier Identifier of the property
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function addIdentifier($propertyName, $identifier) {
		$this->identifiers[$propertyName] = $identifier;
	}

	/**
	 * Get properties which have identifiers assigned, including the identifiers.
	 *
	 * Returns an associative array. The key is the property name, and the
	 * associated value is the identifier for this property.
	 *
	 * @return array Associative identifier array. Key: Property Name; Value: Identifier for the property
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getIdentifiers() {
		return $this->identifiers;
	}

	/**
	 * Returns TRUE if any warning was recognized
	 *
	 * @return boolean TRUE if a warning occured
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function hasWarnings() {
		return (count($this->warnings) > 0);
	}
}

?>