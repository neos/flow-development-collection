<?php
namespace TYPO3\FLOW3\Tests\Unit\Validation\Validator;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the UUID validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class UuidValidatorTest extends \TYPO3\FLOW3\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\FLOW3\Validation\Validator\UuidValidator';

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function validatorAcceptsCorrectUUIDs() {
		$this->assertFalse($this->validator->validate('e104e469-9030-4b98-babf-3990f07dd3f1')->hasErrors());
		$this->assertFalse($this->validator->validate('533548ca-8914-4a19-9404-ef390a6ce387')->hasErrors());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function tooShortUUIDIsRejected() {
		$this->assertTrue($this->validator->validate('e104e469-9030-4b98-babf-3990f07')->hasErrors());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function UUIDWithOtherThanHexValuesIsRejected() {
		$this->assertTrue($this->validator->validate('e104e469-9030-4g98-babf-3990f07dd3f1')->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function UUIDValidatorCreatesTheCorrectErrorIfTheSubjectIsInvalid() {
		$expected = array(new \TYPO3\FLOW3\Validation\Error('The given subject was not a valid UUID.', 1221565853));
		$this->assertEquals($expected, $this->validator->validate('e104e469-9030-4b98-babf-3990f07')->getErrors());
	}
}

?>