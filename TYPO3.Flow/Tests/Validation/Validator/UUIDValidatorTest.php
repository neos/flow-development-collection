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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class UUIDValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function validatorAcceptsCorrectUUIDs() {
		$validator = new \F3\FLOW3\Validation\Validator\UUIDValidator();

		$this->assertTrue($validator->isValid('e104e469-9030-4b98-babf-3990f07dd3f1'));
		$this->assertTrue($validator->isValid('533548ca-8914-4a19-9404-ef390a6ce387'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function tooShortUUIDIsRejected() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');

		$validator = new \F3\FLOW3\Validation\Validator\UUIDValidator();
		$validator->injectObjectFactory($mockObjectFactory);

		$this->assertFalse($validator->isValid('e104e469-9030-4b98-babf-3990f07'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function UUIDWithOtherThanHexValuesIsRejected() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');

		$validator = new \F3\FLOW3\Validation\Validator\UUIDValidator();
		$validator->injectObjectFactory($mockObjectFactory);

		$this->assertFalse($validator->isValid('e104e469-9030-4g98-babf-3990f07dd3f1'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function UUIDValidatorCreatesTheCorrectErrorObjectIfTheSubjectIsInvalid() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\Validation\Error', 'The given subject was not a valid UUID. Got: "e104e469-9030-4b98-babf-3990f07"', 1221565853);

		$validator = new \F3\FLOW3\Validation\Validator\UUIDValidator();
		$validator->injectObjectFactory($mockObjectFactory);

		$validator->isValid('e104e469-9030-4b98-babf-3990f07');
	}
}

?>