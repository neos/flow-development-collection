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
 * Testcase for the DateTime validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DateTimeValidatorTest extends \F3\FLOW3\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'F3\FLOW3\Validation\Validator\DateTimeValidator';

	/**
	 * @var \F3\FLOW3\I18n\Locale
	 */
	protected $sampleLocale;

	protected $mockDatetimeParser;

	/**
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		parent::setUp();
		$this->sampleLocale = new \F3\FLOW3\I18n\Locale('en_GB');
		$this->mockObjectManagerReturnValues['F3\FLOW3\I18n\Locale'] = $this->sampleLocale;

		$this->mockDatetimeParser = $this->getMock('F3\FLOW3\I18n\Parser\DatetimeParser');
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function returnsErrorsOnIncorrectValues() {
		$sampleInvalidTime = 'this is not a time string';

		$this->mockDatetimeParser->expects($this->once())->method('parseTime', $sampleInvalidTime)->will($this->returnValue(FALSE));
		$this->validatorOptions(array('locale' => 'en_GB', 'formatLength' => \F3\FLOW3\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_DEFAULT, 'formatType' => \F3\FLOW3\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_TIME));
		$this->validator->injectDatetimeParser($this->mockDatetimeParser);

		$this->assertTrue($this->validator->validate($sampleInvalidTime)->hasErrors());
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function returnsTrueForCorrectValues() {
		$sampleValidDateTime = '10.08.2010, 18:00 CEST';

		$this->mockDatetimeParser->expects($this->once())->method('parseDateAndTime', $sampleValidDateTime)->will($this->returnValue(array('parsed datetime')));
		$this->validatorOptions(array('locale' => 'en_GB', 'formatLength' => \F3\FLOW3\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_FULL, 'formatType' => \F3\FLOW3\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATETIME));
		$this->validator->injectDatetimeParser($this->mockDatetimeParser);

		$this->assertFalse($this->validator->validate($sampleValidDateTime)->hasErrors());
	}
}
?>