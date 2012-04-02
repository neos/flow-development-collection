<?php
namespace TYPO3\FLOW3\Tests\Unit\Validation\Validator;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the DateTime validator
 *
 */
class DateTimeValidatorTest extends \TYPO3\FLOW3\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\FLOW3\Validation\Validator\DateTimeValidator';

	/**
	 * @var \TYPO3\FLOW3\I18n\Locale
	 */
	protected $sampleLocale;

	protected $mockDatetimeParser;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->sampleLocale = new \TYPO3\FLOW3\I18n\Locale('en_GB');
		$this->mockObjectManagerReturnValues['TYPO3\FLOW3\I18n\Locale'] = $this->sampleLocale;

		$this->mockDatetimeParser = $this->getMock('TYPO3\FLOW3\I18n\Parser\DatetimeParser');
	}

	/**
	 * @test
	 */
	public function validateReturnsNoErrorIfTheGivenValueIsNull() {
		$this->validatorOptions(array());
		$this->validator->injectDatetimeParser($this->mockDatetimeParser);
		$this->assertFalse($this->validator->validate(NULL)->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString() {
		$this->validatorOptions(array());
		$this->validator->injectDatetimeParser($this->mockDatetimeParser);
		$this->assertFalse($this->validator->validate('')->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsNoErrorIfTheGivenValueIsOfTypeDateTime() {
		$this->validatorOptions(array());
		$this->validator->injectDatetimeParser($this->mockDatetimeParser);
		$this->assertFalse($this->validator->validate(new \DateTime())->hasErrors());
	}

	/**
	 * @test
	 */
	public function returnsErrorsOnIncorrectValues() {
		$sampleInvalidTime = 'this is not a time string';

		$this->mockDatetimeParser->expects($this->once())->method('parseTime', $sampleInvalidTime)->will($this->returnValue(FALSE));
		$this->validatorOptions(array('locale' => 'en_GB', 'formatLength' => \TYPO3\FLOW3\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_DEFAULT, 'formatType' => \TYPO3\FLOW3\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_TIME));
		$this->validator->injectDatetimeParser($this->mockDatetimeParser);

		$this->assertTrue($this->validator->validate($sampleInvalidTime)->hasErrors());
	}

	/**
	 * @test
	 */
	public function returnsTrueForCorrectValues() {
		$sampleValidDateTime = '10.08.2010, 18:00 CEST';

		$this->mockDatetimeParser->expects($this->once())->method('parseDateAndTime', $sampleValidDateTime)->will($this->returnValue(array('parsed datetime')));
		$this->validatorOptions(array('locale' => 'en_GB', 'formatLength' => \TYPO3\FLOW3\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_FULL, 'formatType' => \TYPO3\FLOW3\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATETIME));
		$this->validator->injectDatetimeParser($this->mockDatetimeParser);

		$this->assertFalse($this->validator->validate($sampleValidDateTime)->hasErrors());
	}
}
?>