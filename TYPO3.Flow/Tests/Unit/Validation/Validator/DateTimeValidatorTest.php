<?php
namespace TYPO3\Flow\Tests\Unit\Validation\Validator;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the DateTime validator
 *
 */
class DateTimeValidatorTest extends \TYPO3\Flow\Tests\Unit\Validation\Validator\AbstractValidatorTestcase
{
    protected $validatorClassName = 'TYPO3\Flow\Validation\Validator\DateTimeValidator';

    /**
     * @var \TYPO3\Flow\I18n\Locale
     */
    protected $sampleLocale;

    protected $mockDatetimeParser;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->sampleLocale = new \TYPO3\Flow\I18n\Locale('en_GB');
        $this->mockObjectManagerReturnValues['TYPO3\Flow\I18n\Locale'] = $this->sampleLocale;

        $this->mockDatetimeParser = $this->createMock('TYPO3\Flow\I18n\Parser\DatetimeParser');
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsNull()
    {
        $this->validatorOptions(array());
        $this->inject($this->validator, 'datetimeParser', $this->mockDatetimeParser);
        $this->assertFalse($this->validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString()
    {
        $this->validatorOptions(array());
        $this->inject($this->validator, 'datetimeParser', $this->mockDatetimeParser);
        $this->assertFalse($this->validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsOfTypeDateTime()
    {
        $this->validatorOptions(array());
        $this->inject($this->validator, 'datetimeParser', $this->mockDatetimeParser);
        $this->assertFalse($this->validator->validate(new \DateTime())->hasErrors());
    }

    /**
     * @test
     */
    public function returnsErrorsOnIncorrectValues()
    {
        $sampleInvalidTime = 'this is not a time string';

        $this->mockDatetimeParser->expects($this->once())->method('parseTime', $sampleInvalidTime)->will($this->returnValue(false));
        $this->validatorOptions(array('locale' => 'en_GB', 'formatLength' => \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_DEFAULT, 'formatType' => \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_TIME));
        $this->inject($this->validator, 'datetimeParser', $this->mockDatetimeParser);

        $this->assertTrue($this->validator->validate($sampleInvalidTime)->hasErrors());
    }

    /**
     * @test
     */
    public function returnsTrueForCorrectValues()
    {
        $sampleValidDateTime = '10.08.2010, 18:00 CEST';

        $this->mockDatetimeParser->expects($this->once())->method('parseDateAndTime', $sampleValidDateTime)->will($this->returnValue(array('parsed datetime')));
        $this->validatorOptions(array('locale' => 'en_GB', 'formatLength' => \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_FULL, 'formatType' => \TYPO3\Flow\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATETIME));
        $this->inject($this->validator, 'datetimeParser', $this->mockDatetimeParser);

        $this->assertFalse($this->validator->validate($sampleValidDateTime)->hasErrors());
    }
}
