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

use Neos\Flow\Validation\Validator\NotEmptyValidator;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the not empty validator
 *
 */
class NotEmptyValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = NotEmptyValidator::class;

    /**
     * @test
     */
    public function notEmptyValidatorReturnsNoErrorForASimpleString()
    {
        $this->assertFalse($this->validator->validate('a not empty string')->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorReturnsErrorForAnEmptyString()
    {
        $this->assertTrue($this->validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorReturnsErrorForANullValue()
    {
        $this->assertTrue($this->validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorReturnsErrorForAnEmptyArray()
    {
        $this->assertTrue($this->validator->validate([])->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorReturnsErrorForAnEmptyCountableObject()
    {
        $this->assertTrue($this->validator->validate(new \SplObjectStorage())->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorCreatesTheCorrectErrorForAnEmptySubject()
    {
        $this->assertEquals(1, count($this->validator->validate('')->getErrors()));
    }

    /**
     * @test
     */
    public function notEmptyValidatorCreatesTheCorrectErrorForANullValue()
    {
        $this->assertEquals(1, count($this->validator->validate(null)->getErrors()));
    }
}
