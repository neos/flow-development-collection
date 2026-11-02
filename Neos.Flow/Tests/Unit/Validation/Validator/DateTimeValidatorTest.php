<?php

declare(strict_types=1);

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
use Neos\Flow\I18n\Cldr\Reader\DatesReader;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Parser\DatetimeParser;
use Neos\Flow\Validation\Validator\DateTimeValidator;
use PHPUnit\Framework\Attributes\Test;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the DateTime validator
 */
final class DateTimeValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = DateTimeValidator::class;

    protected $mockDatetimeParser;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $sampleLocale = new Locale('en_GB');
        $mockObjectManagerReturnValues[Locale::class] = $sampleLocale;

        $this->mockDatetimeParser = $this->createMock(DatetimeParser::class);
    }

    #[Test]
    public function validateReturnsNoErrorIfTheGivenValueIsNull()
    {
        $this->validatorOptions([]);
        $this->inject($this->validator, 'datetimeParser', $this->mockDatetimeParser);
        self::assertFalse($this->validator->validate(null)->hasErrors());
    }

    #[Test]
    public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString()
    {
        $this->validatorOptions([]);
        $this->inject($this->validator, 'datetimeParser', $this->mockDatetimeParser);
        self::assertFalse($this->validator->validate('')->hasErrors());
    }

    #[Test]
    public function validateReturnsNoErrorIfTheGivenValueIsOfTypeDateTime()
    {
        $this->validatorOptions([]);
        $this->inject($this->validator, 'datetimeParser', $this->mockDatetimeParser);
        self::assertFalse($this->validator->validate(new \DateTime())->hasErrors());
    }

    #[Test]
    public function returnsErrorsOnIncorrectValues()
    {
        $sampleInvalidTime = 'this is not a time string';

        $this->mockDatetimeParser->expects($this->once())->method('parseTime', $sampleInvalidTime)->willReturn((false));
        $this->validatorOptions(['locale' => 'en_GB', 'formatLength' => DatesReader::FORMAT_LENGTH_DEFAULT, 'formatType' => DatesReader::FORMAT_TYPE_TIME]);
        $this->inject($this->validator, 'datetimeParser', $this->mockDatetimeParser);

        self::assertTrue($this->validator->validate($sampleInvalidTime)->hasErrors());
    }

    #[Test]
    public function returnsTrueForCorrectValues()
    {
        $sampleValidDateTime = '10.08.2010, 18:00 CEST';

        $this->mockDatetimeParser->expects($this->once())->method('parseDateAndTime', $sampleValidDateTime)->willReturn((['parsed datetime']));
        $this->validatorOptions(['locale' => 'en_GB', 'formatLength' => DatesReader::FORMAT_LENGTH_FULL, 'formatType' => DatesReader::FORMAT_TYPE_DATETIME]);
        $this->inject($this->validator, 'datetimeParser', $this->mockDatetimeParser);

        self::assertFalse($this->validator->validate($sampleValidDateTime)->hasErrors());
    }
}
