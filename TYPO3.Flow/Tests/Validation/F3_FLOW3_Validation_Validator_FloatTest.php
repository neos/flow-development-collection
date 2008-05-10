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

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id: F3_FLOW3_Validation_Validator_FloatTest.php 688 2008-04-03 09:35:36Z andi $
 */

/**
 * Testcase for the float validator
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id: F3_FLOW3_Validation_Validator_FloatTest.php 688 2008-04-03 09:35:36Z andi $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Validation_Validator_FloatTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function floatValidatorReturnsTrueForASimpleFloat() {
		$floatValidator = new F3_FLOW3_Validation_Validator_Float();
		$validationErrors = new F3_FLOW3_Validation_Errors();

		$this->assertTrue($floatValidator->isValidProperty(1029437.234726, $validationErrors));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function floatValidatorReturnsFalseForASimpleInteger() {
		$floatValidator = new F3_FLOW3_Validation_Validator_Float();
		$validationErrors = new F3_FLOW3_Validation_Errors();

		$this->assertFalse($floatValidator->isValidProperty(1029437, $validationErrors));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function floatValidatorReturnsFalseForAString() {
		$floatValidator = new F3_FLOW3_Validation_Validator_Float();
		$validationErrors = new F3_FLOW3_Validation_Errors();

		$this->assertFalse($floatValidator->isValidProperty('not a number', $validationErrors));
	}
}

?>