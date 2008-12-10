<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation\Validator;

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
 * @version $Id:\F3\FLOW3\Validation\Validator\NumberTest.php 845 2008-05-17 16:04:59Z k-fish $
 */

/**
 * Testcase for the number validator
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:\F3\FLOW3\Validation\Validator\NumberTest.php 845 2008-05-17 16:04:59Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class NumberTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberValidatorReturnsTrueForASimpleInteger() {
		$numberValidator = new \F3\FLOW3\Validation\Validator\Number();
		$numberValidator->injectObjectFactory($this->objectFactory);
		$validationErrors = new \F3\FLOW3\Validation\Errors();

		$this->assertTrue($numberValidator->isValidProperty(1029437, $validationErrors));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberValidatorReturnsFalseForAString() {
		$numberValidator = new \F3\FLOW3\Validation\Validator\Number();
		$numberValidator->injectObjectFactory($this->objectFactory);
		$validationErrors = new \F3\FLOW3\Validation\Errors();

		$this->assertFalse($numberValidator->isValidProperty('not a number', $validationErrors));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberValidatorCreatesTheCorrectErrorObjectForAnInvalidSubject() {
		$numberValidator = new \F3\FLOW3\Validation\Validator\Number();
		$numberValidator->injectObjectFactory($this->objectFactory);
		$validationErrors = new \F3\FLOW3\Validation\Errors();

		$numberValidator->isValidProperty('this is not a number', $validationErrors);

		$this->assertType('F3\FLOW3\Validation\Error', $validationErrors[0]);
		$this->assertEquals(1221563685, $validationErrors[0]->getErrorCode());
	}
}

?>