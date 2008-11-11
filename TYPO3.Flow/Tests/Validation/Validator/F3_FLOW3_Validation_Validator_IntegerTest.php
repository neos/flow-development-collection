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
 * Testcase for the integer validator
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class IntegerTest extends F3::Testing::BaseTestCase {

	/**
	 * Data provider with valid integers
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function validIntegers() {
		return array(
			array(1029437),
			array('12345'),
			array('+12345'),
			array('-12345')
		);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @dataProvider validIntegers
	 */
	public function integerValidatorReturnsTrueForAValidInteger($integer) {
		$integerValidator = new F3::FLOW3::Validation::Validator::Integer();
		$integerValidator->injectObjectFactory($this->objectFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$this->assertTrue($integerValidator->isValidProperty($integer, $validationErrors));
	}

	/**
	 * Data provider with invalid email addresses
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function invalidIntegers() {
		return array(
			array('not a number'),
			array(3.1415),
			array('12345.987')
		);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @dataProvider invalidIntegers
	 */
	public function integerValidatorReturnsTrueForAnInvalidInteger($integer) {
		$integerValidator = new F3::FLOW3::Validation::Validator::Integer();
		$integerValidator->injectObjectFactory($this->objectFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$this->assertFalse($integerValidator->isValidProperty($integer, $validationErrors));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function integerValidatorCreatesTheCorrectErrorObjectForAnInvalidSubject() {
		$integerValidator = new F3::FLOW3::Validation::Validator::Integer();
		$integerValidator->injectObjectFactory($this->objectFactory);
		$validationErrors = new F3::FLOW3::Validation::Errors();

		$integerValidator->isValidProperty('not a number', $validationErrors);

		$this->assertType('F3::FLOW3::Validation::Error', $validationErrors[0]);
		$this->assertEquals(1221560494, $validationErrors[0]->getErrorCode());
	}

}

?>