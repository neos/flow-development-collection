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
 * Testcase for the alphanumeric validator
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AlphanumericValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function alphanumericValidatorReturnsTrueForAnAlphanumericString() {
		$alphanumericValidator = new \F3\FLOW3\Validation\Validator\AlphanumericValidator();
		$this->assertTrue($alphanumericValidator->isValid('12ssDF34daweidf'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function alphanumericValidatorReturnsFalseForAStringWithSpecialCharacters() {
	$alphanumericValidator = $this->getMock('F3\FLOW3\Validation\Validator\AlphanumericValidator', array('addError'), array(), '', FALSE);
	$this->assertFalse($alphanumericValidator->isValid('adsf%&/$jklsfdö'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function alphanumericValidatorCreatesTheCorrectErrorForAnInvalidSubject() {
		$alphanumericValidator = $this->getMock('F3\FLOW3\Validation\Validator\AlphanumericValidator', array('addError'), array(), '', FALSE);
		$alphanumericValidator->expects($this->once())->method('addError');
		$alphanumericValidator->isValid('adsf%&/$jklsfdö');
	}
}

?>