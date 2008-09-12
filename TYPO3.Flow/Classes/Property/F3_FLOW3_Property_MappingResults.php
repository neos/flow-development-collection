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
 * @version $Id:F3::FLOW3::Property::MappingResults.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * Description
 *
 * @package FLOW3
 * @subpackage Property
 * @version $Id:F3::FLOW3::Property::MappingResults.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
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
	 * Adds an error to the mapping results. This might be for example a validation or mapping error
	 *
	 * @param F3::FLOW3::Error::Error The occured error
	 * @param string The name of the property which caused the error
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addError(F3::FLOW3::Error::Error $error, $propertyName) {
		$this->errors[$propertyName] = $error;
	}

	/**
	 * Returns all errors that occured so far
	 *
	 * @return array Array of F3::FLOW3::Error::Error objects
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
	 * Adds a warning to the mapping results. This might be for example a property that could not be mapped but wasn't marked as required.
	 *
	 * @param F3::FLOW3::Error::Warning The occured warning
	 * @param string The name of the property which caused the error
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addWarning(F3::FLOW3::Error::Warning $warning, $propertyName) {
		$this->warnings[$propertyName] = $warning;
	}

	/**
	 * Returns all warnings that occured so far
	 *
	 * @return array Array of F3::FLOW3::Error::Warning objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getWarnings() {
		return $this->warnings;
	}

	/**
	 * Returns true if any warning was recognized
	 *
	 * @return boolean True if a warning occured
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function hasWarnings() {
		return (count($this->warnings) > 0);
	}
}

?>