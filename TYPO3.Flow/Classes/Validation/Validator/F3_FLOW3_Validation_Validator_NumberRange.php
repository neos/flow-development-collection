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
 * @version $Id: F3::FLOW3::Validation::Validator::NumberRange.php 681 2008-04-02 14:00:27Z andi $
 */

/**
 * Validator for general numbers
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id: F3::FLOW3::Validation::Validator::NumberRange.php 681 2008-04-02 14:00:27Z andi $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class NumberRange implements F3::FLOW3::Validation::ValidatorInterface {

	/**
	 * @var number The start value of the range
	 */
	protected $startRange;

	/**
	 * @var number The end value of the range
	 */
	protected $endRange;

	/**
	 * @var F3::FLOW3::Component::FactoryInterface The component factory
	 */
	protected $componentFactory;

	/**
	 * constructor
	 * @param number The start of the range
	 * @param number The end of the range
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct($startRange, $endRange) {
		if ($startRange > $endRange) {
			$this->endRange = $startRange;
			$this->startRange = $endRange;
		} else {
			$this->endRange = $endRange;
			$this->startRange = $startRange;
		}
	}

	/**
	 * Returns TRUE, if the given propterty ($proptertyValue) is a valid number in the given range.
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

		if (!is_numeric($propertyValue)) $errors->append($this->createNewValidationErrorObject('The given subject was not a valid number. Got: "' . $propertyValue . '"', 1221563685));
		if ($propertyValue < $this->startRange || $propertyValue > $this->endRange) $errors->append($this->createNewValidationErrorObject('The given subject was not in the valid range (' . $this->startRange . ', ' . $this->endRange . '). Got: "' . $propertyValue . '"', 1221561046));

		if (count($errors) > 0) return FALSE;
		return TRUE;
	}

	/**
	 * Injector method for the component factory
	 *
	 * @param F3::FLOW3::Component::FactoryInterface $componentFactory A component factory implementation
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectComponentFactory(F3::FLOW3::Component::FactoryInterface $componentFactory) {
		$this->componentFactory = $componentFactory;
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