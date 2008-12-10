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
 * Contract for an object validator
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 * @author Robert Lemke <robert@typo3.org>
 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface ObjectValidatorInterface {

	/**
	 * Checks if classes of the given type can be validated with this
	 * validator.
	 *
	 * @param  string $className: Specifies the class type which is supposed to be validated. The check succeeds if this validator can handle the specified class or any subclass of it.
	 * @return boolean TRUE if this validator can validate the class type or FALSE if it can't
	 */
	public function canValidate($className);

	/**
	 * Validates the given object. Any errors will be stored in the passed errors
	 * object. If validation succeeds completely, this method returns TRUE. If at
	 * least one error occurred, the result is FALSE.
	 *
	 * @param  object $object: The object which is supposed to be validated.
	 * @param  \F3\FLOW3\Validation\Errors $errors: Here any occured validation error is stored
	 * @return boolean TRUE if validation succeeded completely, FALSE if at least one error occurred.
	 * @throws \F3\FLOW3\Validation\Exception\InvalidSubject if this validator cannot validate the given subject or the subject is not an object.
	 */
	public function validate($object, \F3\FLOW3\Validation\Errors &$errors);

	/**
	 * Validates a specific property ($propertyName) of the given object. Any errors will be stored
	 * in the given errors object. If validation succeeds, this method returns TRUE, else it will return FALSE.
	 *
	 * @param  object $object: The object of which the property should be validated
	 * @param  string $propertyName: The name of the property that should be validated
	 * @param  \F3\FLOW3\Validation\Errors $errors: Here any occured validation error is stored
	 * @return boolean TRUE if the property could be validated, FALSE if an error occured
	 * @throws \F3\FLOW3\Validation\Exception\InvalidSubject if this validator cannot validate the given subject or the subject is not an object.
	 */
	public function validateProperty($object, $propertyName, \F3\FLOW3\Validation\Errors &$errors);

	/**
	 * Returns TRUE, if the given propterty ($proptertyValue) is a valid value for the property ($propertyName) of the class ($className).
	 * Any errors will be stored in the given errors object. If at least one error occurred, the result is FALSE.
	 *
	 * @param  string $className: The propterty's class name
	 * @param  string $propertyName: The name of the property for wich the value should be validated
	 * @param  object $propertyValue: The value that should be validated
	 * @return boolean TRUE if the value could be validated for the given property, FALSE if an error occured
	 * @throws \F3\FLOW3\Validation\Exception\InvalidSubject if this validator cannot validate the given subject or the subject is not an object.
	 */
	public function isValidProperty($className, $propertyName, $propertyValue, \F3\FLOW3\Validation\Errors &$errors);
}

?>