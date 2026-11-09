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
use Neos\Flow\Validation\Validator\AlphanumericValidator;
use PHPUnit\Framework\Attributes\Test;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the alphanumeric validator
 *
 */
final class AlphanumericValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = AlphanumericValidator::class;

    #[Test]
    public function alphanumericValidatorShouldReturnNoErrorsIfTheGivenValueIsNull()
    {
        self::assertFalse($this->validator->validate(null)->hasErrors());
    }

    #[Test]
    public function alphanumericValidatorShouldReturnNoErrorsIfTheGivenStringIsEmpty()
    {
        self::assertFalse($this->validator->validate('')->hasErrors());
    }

    #[Test]
    public function alphanumericValidatorShouldReturnNoErrorsForAnAlphanumericString()
    {
        self::assertFalse($this->validator->validate('12ssDF34daweidf')->hasErrors());
    }

    #[Test]
    public function alphanumericValidatorShouldReturnNoErrorsForAnAlphanumericStringWithUmlauts()
    {
        self::assertFalse($this->validator->validate('12ssDF34daweidfäøüößØLīgaestevimīlojuņščļœøÅ')->hasErrors());
    }

    #[Test]
    public function alphanumericValidatorReturnsErrorsForAStringWithSpecialCharacters()
    {
        self::assertTrue($this->validator->validate('adsf%&/$jklsfdö')->hasErrors());
    }

    #[Test]
    public function alphanumericValidatorCreatesTheCorrectErrorForAnInvalidSubject()
    {
        self::assertCount(1, $this->validator->validate('adsf%&/$jklsfdö')->getErrors());
    }
}
