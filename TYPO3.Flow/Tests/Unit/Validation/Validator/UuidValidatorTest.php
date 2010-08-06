<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation\Validator;

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

/**
 * Testcase for the UUID validator
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class UuidValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function internalErrorsArrayIsResetOnIsValidCall() {
		$validator = $this->getAccessibleMock('F3\FLOW3\Validation\Validator\UuidValidator', array('dummy'), array(), '', FALSE);
		$validator->_set('errors', array('existingError'));
		$validator->isValid('e104e469-9030-4b98-babf-3990f07dd3f1');
		$this->assertSame(array(), $validator->getErrors());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function validatorAcceptsCorrectUUIDs() {
		$validator = new \F3\FLOW3\Validation\Validator\UuidValidator();

		$this->assertTrue($validator->isValid('e104e469-9030-4b98-babf-3990f07dd3f1'));
		$this->assertTrue($validator->isValid('533548ca-8914-4a19-9404-ef390a6ce387'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function tooShortUUIDIsRejected() {
		$validator = $this->getMock('F3\FLOW3\Validation\Validator\UuidValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($validator->isValid('e104e469-9030-4b98-babf-3990f07'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function UUIDWithOtherThanHexValuesIsRejected() {
		$validator = $this->getMock('F3\FLOW3\Validation\Validator\UuidValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($validator->isValid('e104e469-9030-4g98-babf-3990f07dd3f1'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function UUIDValidatorCreatesTheCorrectErrorIfTheSubjectIsInvalid() {
		$validator = $this->getMock('F3\FLOW3\Validation\Validator\UuidValidator', array('addError'), array(), '', FALSE);
		$validator->expects($this->once())->method('addError');
		$validator->isValid('e104e469-9030-4b98-babf-3990f07');
	}
}

?>