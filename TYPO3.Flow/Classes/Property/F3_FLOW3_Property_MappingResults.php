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
 * @version $Id:F3_FLOW3_Property_MappingResults.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * Description
 *
 * @package FLOW3
 * @subpackage Property
 * @version $Id:F3_FLOW3_Property_MappingResults.php 467 2008-02-06 19:34:56Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 *
 * @scope prototype
 */
class F3_FLOW3_Property_MappingResults {

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
	 * @param F3_FLOW3_Error_Error The occured error
	 * @param string The name of the property which caused the error
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addError(F3_FLOW3_Error_Error $error, $propertyName) {
		$this->errors[$propertyName] = $error;
	}

	/**
	 * Returns all errors that occured so far
	 *
	 * @return array Array of F3_FLOW3_Error_Error objects
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
	 * @param F3_FLOW3_Error_Warning The occured warning
	 * @param string The name of the property which caused the error
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addWarning(F3_FLOW3_Error_Warning $warning, $propertyName) {
		$this->warnings[$propertyName] = $warning;
	}

	/**
	 * Returns all warnings that occured so far
	 *
	 * @return array Array of F3_FLOW3_Error_Warning objects
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
