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
use Neos\Flow\Validation\Validator\LocaleIdentifierValidator;
use PHPUnit\Framework\Attributes\Test;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the locale identifier validator
 *
 */
final class LocaleIdentifierValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = LocaleIdentifierValidator::class;

    #[Test]
    public function localeIdentifierReturnsNoErrorIfLocaleIsEmpty()
    {
        self::assertFalse($this->validator->validate('')->hasErrors());
        self::assertFalse($this->validator->validate(null)->hasErrors());
    }

    #[Test]
    public function localeIdentifierReturnsNoErrorIfLocaleIsValid()
    {
        self::assertFalse($this->validator->validate('de_DE')->hasErrors());
        self::assertFalse($this->validator->validate('en_Latn_US')->hasErrors());
        self::assertFalse($this->validator->validate('de')->hasErrors());
        self::assertFalse($this->validator->validate('AR-arab_ae')->hasErrors());
    }

    #[Test]
    public function localeIdentifierReturnsErrorIfLocaleIsInvalid()
    {
        self::assertTrue($this->validator->validate('ThisIsOfCourseNoValidLocaleIdentifier')->hasErrors());
    }
}
