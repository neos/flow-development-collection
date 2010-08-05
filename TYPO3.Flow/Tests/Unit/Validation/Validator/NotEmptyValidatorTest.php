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
 * Testcase for the not empty validator
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NotEmptyValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function notEmptyValidatorReturnsTrueForASimpleString() {
		$notEmptyValidator = new \F3\FLOW3\Validation\Validator\NotEmptyValidator();
		$this->assertTrue($notEmptyValidator->isValid('a not empty string'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function notEmptyValidatorReturnsFalseForAnEmptyString() {
		$notEmptyValidator = $this->getMock('F3\FLOW3\Validation\Validator\NotEmptyValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($notEmptyValidator->isValid(''));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function notEmptyValidatorReturnsFalseForANullValue() {
		$notEmptyValidator = $this->getMock('F3\FLOW3\Validation\Validator\NotEmptyValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($notEmptyValidator->isValid(NULL));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function notEmptyValidatorCreatesTheCorrectErrorForAnEmptySubject() {
		$notEmptyValidator = $this->getMock('F3\FLOW3\Validation\Validator\NotEmptyValidator', array('addError'), array(), '', FALSE);
		$notEmptyValidator->expects($this->once())->method('addError');
		$notEmptyValidator->isValid('');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function notEmptyValidatorCreatesTheCorrectErrorForANullValue() {
		$notEmptyValidator = $this->getMock('F3\FLOW3\Validation\Validator\NotEmptyValidator', array('addError'), array(), '', FALSE);
		$notEmptyValidator->expects($this->once())->method('addError');
		$notEmptyValidator->isValid(NULL);
	}
}

?>