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
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the regular expression validator
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class RegularExpressionTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function regularExpressionValidatorMatchesABasicExpressionCorrectly() {
		$regularExpressionValidator = new F3::FLOW3::Validation::Validator::RegularExpression('/^simple[0-9]expression$/');
		$regularExpressionValidator->injectComponentFactory($this->componentFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$this->assertTrue($regularExpressionValidator->isValidProperty('simple1expression', $validationErrors));
		$this->assertFalse($regularExpressionValidator->isValidProperty('simple1expressions', $validationErrors));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function regularExpressionValidatorCreatesTheCorrectErrorObjectIfTheExpressionDidNotMatch() {
		$regularExpressionValidator = new F3::FLOW3::Validation::Validator::RegularExpression('/^simple[0-9]expression$/');
		$regularExpressionValidator->injectComponentFactory($this->componentFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$regularExpressionValidator->isValidProperty('some subject that will not match', $validationErrors);

		$this->assertType('F3::FLOW3::Validation::Error', $validationErrors[0]);
		$this->assertEquals(1221565130, $validationErrors[0]->getErrorCode());
	}
}

?>