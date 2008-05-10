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
 * @version $Id: F3_FLOW3_Validation_Validator_RegularExpressionTest.php 688 2008-04-03 09:35:36Z andi $
 */

/**
 * Testcase for the regular expression validator
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id: F3_FLOW3_Validation_Validator_RegularExpressionTest.php 688 2008-04-03 09:35:36Z andi $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Validation_Validator_RegularExpressionTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function regularExpressionValidatorMatchesABasicExpressionCorrectly() {
		$regularExpressionValidator = new F3_FLOW3_Validation_Validator_RegularExpression('/^simple[0-9]expression$/');
		$validationErrors = new F3_FLOW3_Validation_Errors();

		$this->assertTrue($regularExpressionValidator->isValidProperty('simple1expression', $validationErrors));
		$this->assertFalse($regularExpressionValidator->isValidProperty('simple1expressions', $validationErrors));
	}
}

?>