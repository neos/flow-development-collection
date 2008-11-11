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
 * Testcase for the not empty validator
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class NotEmptyTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function notEmptyValidatorReturnsTrueForASimpleString() {
		$notEmptyValidator = new F3::FLOW3::Validation::Validator::NotEmpty();
		$notEmptyValidator->injectObjectFactory($this->objectFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$this->assertTrue($notEmptyValidator->isValidProperty('a not empty string', $validationErrors));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function notEmptyValidatorReturnsFalseForAnEmptyString() {
		$notEmptyValidator = new F3::FLOW3::Validation::Validator::NotEmpty();
		$notEmptyValidator->injectObjectFactory($this->objectFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$this->assertFalse($notEmptyValidator->isValidProperty('', $validationErrors));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function notEmptyValidatorReturnsFalseForANullValue() {
		$notEmptyValidator = new F3::FLOW3::Validation::Validator::NotEmpty();
		$notEmptyValidator->injectObjectFactory($this->objectFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$this->assertFalse($notEmptyValidator->isValidProperty(NULL, $validationErrors));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function notEmptyValidatorCreatesTheCorrectErrorObjectForAnEmptySubject() {
		$notEmptyValidator = new F3::FLOW3::Validation::Validator::NotEmpty();
		$notEmptyValidator->injectObjectFactory($this->objectFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$notEmptyValidator->isValidProperty('', $validationErrors);

		$this->assertType('F3::FLOW3::Validation::Error', $validationErrors[0]);
		$this->assertEquals(1221560718, $validationErrors[0]->getErrorCode());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function notEmptyValidatorCreatesTheCorrectErrorObjectForANullValue() {
		$notEmptyValidator = new F3::FLOW3::Validation::Validator::NotEmpty();
		$notEmptyValidator->injectObjectFactory($this->objectFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$notEmptyValidator->isValidProperty(NULL, $validationErrors);

		$this->assertType('F3::FLOW3::Validation::Error', $validationErrors[0]);
		$this->assertEquals(1221560910, $validationErrors[0]->getErrorCode());
	}
}

?>