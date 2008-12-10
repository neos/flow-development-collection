<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation;

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
 * @subpackage Validation
 * @version $Id$
 */

/**
 * Contract for a validator
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 * @author Robert Lemke <robert@typo3.org>
 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface ValidatorInterface {

	/**
	 * Returns TRUE, if the given propterty ($proptertyValue) is a valid.
	 * Any errors will be stored in the given errors object.
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param  object $propertyValue: The value that should be validated
	 * @return boolean TRUE if the value could be validated. FALSE if an error occured
	 * @throws \F3\FLOW3\Validation\Exception\InvalidSubject if this validator cannot validate the given subject or the subject is not an object.
	 */
	public function isValidProperty($propertyValue, \F3\FLOW3\Validation\Errors &$errors);
}

?>