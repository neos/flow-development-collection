<?php
namespace Neos\Flow\Tests\Unit\Validation\Validator;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\I18n\Locale;
use Neos\Flow\Validation\Validator\DateTimeValidator;
use Neos\Flow\I18n;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the DateTime validator
 */
class DateTimeValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = DateTimeValidator::class;

    /**
     * @var Locale
     */
    protected $sampleLocale;

    protected $mockDatetimeParser;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->sampleLocale = new Locale('en_GB');
        $this->mockObjectManagerReturnValues[Locale::class] = $this->sampleLocale;

        $this->mockDatetimeParser = $this->createMock(I18n\Parser\DatetimeParser::class);
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsNull()
    {
        $this->validatorOptions([]);
        $this->inject($this->validator, 'datetimeParser', $this->mockDatetimeParser);
        self::assertFalse($this->validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString()
    {
        $this->validatorOptions([]);
        $this->inject($this->validator, 'datetimeParser', $this->mockDatetimeParser);
        self::assertFalse($this->validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsOfTypeDateTime()
    {
        $this->validatorOptions([]);
        $this->inject($this->validator, 'datetimeParser', $this->mockDatetimeParser);
        self::assertFalse($this->validator->validate(new \DateTime())->hasErrors());
    }

    /**
     * @test
     */
    public function returnsErrorsOnIncorrectValues()
    {
        $sampleInvalidTime = 'this is not a time string';

        $this->mockDatetimeParser->expects(self::once())->method('parseTime', $sampleInvalidTime)->will(self::returnValue(false));
        $this->validatorOptions(['locale' => 'en_GB', 'formatLength' => I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_DEFAULT, 'formatType' => I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_TIME]);
        $this->inject($this->validator, 'datetimeParser', $this->mockDatetimeParser);

        self::assertTrue($this->validator->validate($sampleInvalidTime)->hasErrors());
    }

    /**
     * @test
     */
    public function returnsTrueForCorrectValues()
    {
        $sampleValidDateTime = '10.08.2010, 18:00 CEST';

        $this->mockDatetimeParser->expects(self::once())->method('parseDateAndTime', $sampleValidDateTime)->will(self::returnValue(['parsed datetime']));
        $this->validatorOptions(['locale' => 'en_GB', 'formatLength' => I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_FULL, 'formatType' => I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATETIME]);
        $this->inject($this->validator, 'datetimeParser', $this->mockDatetimeParser);

        self::assertFalse($this->validator->validate($sampleValidDateTime)->hasErrors());
    }
}
