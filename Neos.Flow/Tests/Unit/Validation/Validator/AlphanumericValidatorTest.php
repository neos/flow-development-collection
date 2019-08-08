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

use Neos\Flow\Validation\Validator\AlphanumericValidator;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the alphanumeric validator
 *
 */
class AlphanumericValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = AlphanumericValidator::class;

    /**
     * @test
     */
    public function alphanumericValidatorShouldReturnNoErrorsIfTheGivenValueIsNull()
    {
        self::assertFalse($this->validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function alphanumericValidatorShouldReturnNoErrorsIfTheGivenStringIsEmpty()
    {
        self::assertFalse($this->validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function alphanumericValidatorShouldReturnNoErrorsForAnAlphanumericString()
    {
        self::assertFalse($this->validator->validate('12ssDF34daweidf')->hasErrors());
    }

    /**
     * @test
     */
    public function alphanumericValidatorShouldReturnNoErrorsForAnAlphanumericStringWithUmlauts()
    {
        self::assertFalse($this->validator->validate('12ssDF34daweidfäøüößØLīgaestevimīlojuņščļœøÅ')->hasErrors());
    }

    /**
     * @test
     */
    public function alphanumericValidatorReturnsErrorsForAStringWithSpecialCharacters()
    {
        self::assertTrue($this->validator->validate('adsf%&/$jklsfdö')->hasErrors());
    }

    /**
     * @test
     */
    public function alphanumericValidatorCreatesTheCorrectErrorForAnInvalidSubject()
    {
        self::assertEquals(1, count($this->validator->validate('adsf%&/$jklsfdö')->getErrors()));
    }
}
