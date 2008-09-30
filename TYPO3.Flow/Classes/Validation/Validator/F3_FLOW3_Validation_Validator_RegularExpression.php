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
 * @version $Id$
 */

/**
 * Validator for regular expressions
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class RegularExpression extends F3::FLOW3::Validation::AbstractValidator {

	/**
	 * @var string The regular expression
	 */
	protected $regularExpression;

	/**
	 * Creates a RegularExpression validator with the given expression
	 *
	 * @param string The regular expression, must be ready to use with preg_match()
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct($regularExpression) {
		$this->regularExpression = $regularExpression;
	}

	/**
	 * Returns TRUE, if the given propterty ($proptertyValue) matches the given regular expression.
	 * Any errors will be stored in the given errors object.
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $propertyValue The value that should be validated
	 * @param F3::FLOW3::Validation::Errors $errors Any occured Error will be stored here
	 * @return boolean TRUE if the value could be validated. FALSE if an error occured
	 * @throws F3::FLOW3::Validation::Exception::InvalidSubject if this validator cannot validate the given subject or the subject is not an object.
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isValidProperty($propertyValue, F3::FLOW3::Validation::Errors &$errors) {

		if (!is_string($propertyValue) || preg_match($this->regularExpression, $propertyValue) === 0) {
			$errors->append($this->componentFactory->getComponent('F3::FLOW3::Validation::Error', 'The given subject did not match the pattern. Got: "' . $propertyValue . '"', 1221565130));
			return FALSE;
		}

		return TRUE;
	}
}

?>