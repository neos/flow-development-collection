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
 * Contract for a validator 
 * 
 * @package		FLOW3
 * @subpackage	Validation
 * @version 	$Id:F3_FLOW3_Validation_ValidatorInterface.php 467 2008-02-06 19:34:56Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @author		Robert Lemke <robert@typo3.org>
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface F3_FLOW3_Validation_ValidatorInterface {

	/**
	 * Checks if classes of the given type can be validated with this
	 * validator.
	 * 
	 * @param  string								$className: Specifies the class type which is supposed to be validated. The check succeeds if this validator can handle the specified class or any subclass of it.
	 * @return boolean								TRUE if this validator can validate the class type or FALSE if it can't
	 */
	public function canValidate($className);
	
	/**
	 * Validates the given object. Any errors will be stored in the passed errors
	 * object. If validation succeeds completely, this method returns TRUE. If at 
	 * least one error occurred, the result is FALSE.
	 * 
	 * @param  object								$subject: The object which is supposed to be validated.
	 * @return boolean								TRUE if validation succeeded completely, FALSE if at least one error occurred.
	 * @throws F3_FLOW3_Security_Validation_Exception_InvalidSubject if this validator cannot validate the given subject or the subject is not an object.
	 */
	public function validate($subject, F3_FLOW3_Security_Validation_Errors $errors);
}

?>