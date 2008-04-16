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

/*
 * @package FLOW3
 * @subpackage Validation
 * @version $Id: F3_FLOW3_Validation_Validator_Integer.php 681 2008-04-02 14:00:27Z andi $
 */

/**
 * Validator for integers
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id: F3_FLOW3_Validation_Validator_Integer.php 681 2008-04-02 14:00:27Z andi $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Validation_Validator_Integer implements F3_FLOW3_Validation_ValidatorInterface {

	/**
	 * Returns TRUE, if the given propterty ($proptertyValue) is a valid integer.
	 * Any errors will be stored in the given errors object.
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param  object $propertyValue: The value that should be validated
	 * @return boolean TRUE if the value could be validated. FALSE if an error occured
	 * @throws F3_FLOW3_Validation_Exception_InvalidSubject if this validator cannot validate the given subject or the subject is not an object.
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isValidProperty($propertyValue, F3_FLOW3_Validation_Errors &$errors) {

		return is_integer($propertyValue);
	}
}

?>