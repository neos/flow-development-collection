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
 * @version $Id: F3::FLOW3::Validation::Validator::AlphanumericTest.php 688 2008-04-03 09:35:36Z andi $
 */

/**
 * Testcase for the alphanumeric validator
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id: F3::FLOW3::Validation::Validator::AlphanumericTest.php 688 2008-04-03 09:35:36Z andi $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class AlphanumericTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function alphanumericValidatorReturnsTrueForAAlphanumericString() {
		$alphanumericValidator = new F3::FLOW3::Validation::Validator::Alphanumeric();
		$alphanumericValidator->injectComponentFactory($this->componentFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$this->assertTrue($alphanumericValidator->isValidProperty('12ssDF34daweidf', $validationErrors));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function integerValidatorReturnsFalseForAStringWithSpecialCharacters() {
		$alphanumericValidator = new F3::FLOW3::Validation::Validator::Alphanumeric();
		$alphanumericValidator->injectComponentFactory($this->componentFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$this->assertFalse($alphanumericValidator->isValidProperty('adsf%&/$jklsfdö', $validationErrors));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function integerValidatorCreatesTheCorrectErrorObjectForAnInvalidSubject() {
		$alphanumericValidator = new F3::FLOW3::Validation::Validator::Alphanumeric();
		$alphanumericValidator->injectComponentFactory($this->componentFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$alphanumericValidator->isValidProperty('adsf%&/$jklsfdö', $validationErrors);

		$this->assertType('F3::FLOW3::Validation::Error', $validationErrors[0]);
		$this->assertEquals(1221551320, $validationErrors[0]->getErrorCode());
	}
}

?>