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
 * @subpackage Validation
 * @version $Id$
 */

/**
 * Testcase for the raw validator
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Validation_Validator_RawTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theRawValidatorAlwaysReturnsTRUE() {
		$rawValidator = new F3_FLOW3_Validation_Validator_Raw();
		$validationErrors = new F3_FLOW3_Validation_Errors();

		$this->assertTrue($rawValidator->isValidProperty('simple1expression', $validationErrors));
		$this->assertTrue($rawValidator->isValidProperty('', $validationErrors));
		$this->assertTrue($rawValidator->isValidProperty(NULL, $validationErrors));
		$this->assertTrue($rawValidator->isValidProperty(FALSE, $validationErrors));
		$this->assertTrue($rawValidator->isValidProperty(new ArrayObject(), $validationErrors));
	}
}

?>