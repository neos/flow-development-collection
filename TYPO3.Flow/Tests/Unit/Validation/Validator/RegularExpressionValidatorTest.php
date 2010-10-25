<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Validation\Validator;

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
 * Testcase for the regular expression validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RegularExpressionValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function internalErrorsArrayIsResetOnIsValidCall() {
		$validator = $this->getAccessibleMock('F3\FLOW3\Validation\Validator\RegularExpressionValidator', array('dummy'), array(), '', FALSE);
		$validator->setOptions(array('regularExpression' => '/^.+$/'));
		$validator->_set('errors', array('existingError'));
		$validator->isValid('foo');
		$this->assertSame(array(), $validator->getErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function regularExpressionValidatorMatchesABasicExpressionCorrectly() {
		$regularExpressionValidator = $this->getMock('F3\FLOW3\Validation\Validator\RegularExpressionValidator', array('addError'), array(), '', FALSE);
		$regularExpressionValidator->setOptions(array('regularExpression' => '/^simple[0-9]expression$/'));

		$this->assertTrue($regularExpressionValidator->isValid('simple1expression'));
		$this->assertFalse($regularExpressionValidator->isValid('simple1expressions'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function regularExpressionValidatorCreatesTheCorrectErrorIfTheExpressionDidNotMatch() {
		$regularExpressionValidator = $this->getMock('F3\FLOW3\Validation\Validator\RegularExpressionValidator', array('addError'), array(), '', FALSE);
		$regularExpressionValidator->expects($this->once())->method('addError')->with('The given subject did not match the pattern. Got: "some subject that will not match"', 1221565130);
		$regularExpressionValidator->setOptions(array('regularExpression' => '/^simple[0-9]expression$/'));
		$regularExpressionValidator->isValid('some subject that will not match');
	}
}

?>