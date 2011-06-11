<?php
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

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the label validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class LabelValidatorTest extends \F3\FLOW3\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'F3\FLOW3\Validation\Validator\LabelValidator';

	/**
	 * Data provider with valid labels
	 *
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function validLabels() {
		return array(
			array(''),
			array('The quick brown fox drinks no coffee'),
			array('Kasper Skårhøj doesn\'t like his iPad'),
			array('老 时态等的曲折变化 年代出生的人都会书写常用的繁体汉字事实'),
			array('Где только языках насколько бы, найденных'),
			array('I hope, that the above doesn\'t mean anything harmful'),
			array('Punctuation marks like ,.:;?!%§&"\'/+-_=()# are all allowed'),
			array('Nothing speaks against numbers 0123456789'),
			array('Currencies like £₱௹€$¥ could be important')
		);
	}

	/**
	 * Data provider with invalid labels
	 *
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invalidLabels() {
		return array(
			array('<tags> are not allowed'),
			array("\t tabs are not allowed either"),
			array("\n new line? no!"),
			array('☔☃☕ are funny signs, but we don\'t want them in labels'),
		);
	}

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 * @dataProvider validLabels
	 */
	public function labelValidatorReturnsNoErrorForValidLabels($label) {
		 $this->assertFalse($this->validator->validate($label)->hasErrors());
	}

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 * @dataProvider invalidLabels
	 */
	public function labelValidatorReturnsErrorsForInvalidLabels($label) {
		$this->assertTrue($this->validator->validate($label)->hasErrors());
	}
}

?>