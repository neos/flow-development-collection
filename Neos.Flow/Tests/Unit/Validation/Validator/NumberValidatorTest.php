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
use Neos\Flow\I18n\Cldr\Reader\NumbersReader;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Parser\NumberParser;
use Neos\Flow\Validation\Validator\NumberValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the number validator
 */
final class NumberValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = NumberValidator::class;

    /**
     * @var Locale
     */
    protected $sampleLocale;

    protected NumberParser|MockObject $mockNumberParser;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->sampleLocale = new Locale('en_GB');

        $this->mockNumberParser = $this->createMock(NumberParser::class);
    }

    #[Test]
    public function validateReturnsNoErrorIfTheGivenValueIsNull()
    {
        self::assertFalse($this->validator->validate(null)->hasErrors());
    }

    #[Test]
    public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString()
    {
        self::assertFalse($this->validator->validate('')->hasErrors());
    }

    #[Test]
    public function numberValidatorCreatesTheCorrectErrorForAnInvalidSubject()
    {
        $sampleInvalidNumber = 'this is not a number';

        $this->mockNumberParser->expects($this->once())->method('parseDecimalNumber', $sampleInvalidNumber)->willReturn((false));

        $this->validatorOptions(['locale' => $this->sampleLocale]);
        $this->inject($this->validator, 'numberParser', $this->mockNumberParser);

        self::assertCount(1, $this->validator->validate($sampleInvalidNumber)->getErrors());
    }

    #[Test]
    public function returnsFalseForIncorrectValues()
    {
        $sampleInvalidNumber = 'this is not a number';

        $this->mockNumberParser->expects($this->once())->method('parsePercentNumber', $sampleInvalidNumber)->willReturn((false));

        $this->validatorOptions(['locale' => 'en_GB', 'formatLength' => NumbersReader::FORMAT_LENGTH_DEFAULT, 'formatType' => NumbersReader::FORMAT_TYPE_PERCENT]);
        $this->inject($this->validator, 'numberParser', $this->mockNumberParser);

        self::assertCount(1, $this->validator->validate($sampleInvalidNumber)->getErrors());
    }
}
