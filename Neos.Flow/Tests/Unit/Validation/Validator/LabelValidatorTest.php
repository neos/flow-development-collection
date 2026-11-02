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
use Neos\Flow\Validation\Validator\LabelValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the label validator
 *
 */
final class LabelValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = LabelValidator::class;

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

    /**
     * Data provider with valid labels
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function validLabels(): \Iterator
    {
        yield [''];
        yield ['The quick brown fox drinks no coffee'];
        yield ['Kasper Skårhøj doesn\'t like his iPad'];
        yield ['老 时态等的曲折变化 年代出生的人都会书写常用的繁体汉字事实'];
        yield ['Где только языках насколько бы, найденных'];
        yield ['I hope, that the above doesn\'t mean anything harmful'];
        yield ['Punctuation marks like ,.:;?!%§&"\'/+-_=()# are all allowed'];
        yield ['Nothing speaks against numbers 0123456789'];
        yield ['Currencies like £₱௹€$¥ could be important'];
    }

    /**
     * Data provider with invalid labels
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function invalidLabels(): \Iterator
    {
        yield ['<tags> are not allowed'];
        yield ["\t tabs are not allowed either"];
        yield ["\n new line? no!"];
        yield ['☔☃☕ are funny signs, but we don\'t want them in labels'];
    }

    #[DataProvider('validLabels')]
    #[Test]
    public function labelValidatorReturnsNoErrorForValidLabels($label)
    {
        self::assertFalse($this->validator->validate($label)->hasErrors());
    }

    #[DataProvider('invalidLabels')]
    #[Test]
    public function labelValidatorReturnsErrorsForInvalidLabels($label)
    {
        self::assertTrue($this->validator->validate($label)->hasErrors());
    }
}
