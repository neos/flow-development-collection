<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Validation::Validator;

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
 * @version $Id: F3::FLOW3::Validation::Validator::EmailAddress.php 681 2008-04-02 14:00:27Z andi $
 */

/**
 * Validator for email addresses
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id: F3::FLOW3::Validation::Validator::EmailAddress.php 681 2008-04-02 14:00:27Z andi $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class EmailAddress implements F3::FLOW3::Validation::ValidatorInterface {

	/**
	 * @var F3::FLOW3::Component::FactoryInterface The component factory
	 */
	protected $componentFactory;

	/**
	 * Constructor
	 *
	 * @param F3::FLOW3::Component::FactoryInterface $componentFactory A component factory implementation
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(F3::FLOW3::Component::FactoryInterface $componentFactory) {
		$this->componentFactory = $componentFactory;
	}

	/**
	 * Returns TRUE, if the given property ($proptertyValue) is a valid email address.
	 * Any errors will be stored in the given errors object.
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param  object $propertyValue: The value that should be validated
	 * @param F3::FLOW3::Validation::Errors $errors Any occured Error will be stored here
	 * @return boolean TRUE if the value could be validated. FALSE if an error occured
	 * @throws F3::FLOW3::Validation::Exception::InvalidSubject if this validator cannot validate the given subject or the subject is not an object.
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isValidProperty($propertyValue, F3::FLOW3::Validation::Errors &$errors) {

		if(preg_match('/\b[A-Z0-9._%+-]+@(?:[A-Z0-9-]+\.)+[A-Z]{2,4}\b/i', $propertyValue)) return TRUE;

		$errors->append($this->createNewValidationErrorObject('The given subject was not a valid email address. Got: "' . $propertyValue . '"', 1221559976));
		return FALSE;
	}

	/**
	 * This is a factory method to get a clean validation error object
	 *
	 * @param string The error message
	 * @param integer The error code
	 * @return F3::FLOW3::Validation::Error An empty error object
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function createNewValidationErrorObject($message, $code) {
		return $this->componentFactory->getComponent('F3::FLOW3::Validation::Error', $message, $code);
	}
}

?>