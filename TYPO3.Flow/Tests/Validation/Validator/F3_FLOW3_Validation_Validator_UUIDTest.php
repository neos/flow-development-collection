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
 * Testcase for the UUID validator
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Validation_Validator_UUIDTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function validatorAcceptsCorrectUUIDs() {
		$errors = new F3_FLOW3_Validation_Errors();
		$validator = new F3_FLOW3_Validation_Validator_UUID();

		$this->assertTrue($validator->isValidProperty('e104e469-9030-4b98-babf-3990f07dd3f1', $errors));
		$this->assertTrue($validator->isValidProperty('533548ca-8914-4a19-9404-ef390a6ce387', $errors));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function tooShortUUIDIsRejected() {
		$errors = new F3_FLOW3_Validation_Errors();
		$validator = new F3_FLOW3_Validation_Validator_UUID();
		$this->assertFalse($validator->isValidProperty('e104e469-9030-4b98-babf-3990f07', $errors));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function UUIDWithOtherThanHexValuesIsRejected() {
		$errors = new F3_FLOW3_Validation_Errors();
		$validator = new F3_FLOW3_Validation_Validator_UUID();
		$this->assertFalse($validator->isValidProperty('e104e469-9030-4g98-babf-3990f07dd3f1', $errors));
	}
}

?>