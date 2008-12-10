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
 * @subpackage Validation
 * @version $Id$
 */

/**
 * Testcase for the UUID validator
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class UUIDTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function validatorAcceptsCorrectUUIDs() {
		$errors = new \F3\FLOW3\Validation\Errors();
		$validator = new \F3\FLOW3\Validation\Validator\UUID();
		$validator->injectObjectFactory($this->objectFactory);

		$this->assertTrue($validator->isValidProperty('e104e469-9030-4b98-babf-3990f07dd3f1', $errors));
		$this->assertTrue($validator->isValidProperty('533548ca-8914-4a19-9404-ef390a6ce387', $errors));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function tooShortUUIDIsRejected() {
		$errors = new \F3\FLOW3\Validation\Errors();
		$validator = new \F3\FLOW3\Validation\Validator\UUID();
		$validator->injectObjectFactory($this->objectFactory);

		$this->assertFalse($validator->isValidProperty('e104e469-9030-4b98-babf-3990f07', $errors));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function UUIDWithOtherThanHexValuesIsRejected() {
		$errors = new \F3\FLOW3\Validation\Errors();
		$validator = new \F3\FLOW3\Validation\Validator\UUID();
		$validator->injectObjectFactory($this->objectFactory);

		$this->assertFalse($validator->isValidProperty('e104e469-9030-4g98-babf-3990f07dd3f1', $errors));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function UUIDValidatorCreatesTheCorrectErrorObjectIfTheSubjectIsInvalid() {
		$errors = new \F3\FLOW3\Validation\Errors();
		$validator = new \F3\FLOW3\Validation\Validator\UUID();
		$validator->injectObjectFactory($this->objectFactory);

		$validator->isValidProperty('e104e469-9030-4b98-babf-3990f07', $errors);

		$this->assertType('F3\FLOW3\Validation\Error', $errors[0]);
		$this->assertEquals(1221565853, $errors[0]->getErrorCode());
	}
}

?>