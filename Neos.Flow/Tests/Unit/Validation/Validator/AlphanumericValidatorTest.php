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
        $this->assertFalse($this->validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function alphanumericValidatorShouldReturnNoErrorsIfTheGivenStringIsEmpty()
    {
        $this->assertFalse($this->validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function alphanumericValidatorShouldReturnNoErrorsForAnAlphanumericString()
    {
        $this->assertFalse($this->validator->validate('12ssDF34daweidf')->hasErrors());
    }

    /**
     * @test
     */
    public function alphanumericValidatorShouldReturnNoErrorsForAnAlphanumericStringWithUmlauts()
    {
        $this->assertFalse($this->validator->validate('12ssDF34daweidfäøüößØLīgaestevimīlojuņščļœøÅ')->hasErrors());
    }

    /**
     * @test
     */
    public function alphanumericValidatorReturnsErrorsForAStringWithSpecialCharacters()
    {
        $this->assertTrue($this->validator->validate('adsf%&/$jklsfdö')->hasErrors());
    }

    /**
     * @test
     */
    public function alphanumericValidatorCreatesTheCorrectErrorForAnInvalidSubject()
    {
        $this->assertEquals(1, count($this->validator->validate('adsf%&/$jklsfdö')->getErrors()));
    }
}
