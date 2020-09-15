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

use Neos\Flow\Validation\Validator\UuidValidator;
use Neos\Flow\Validation;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the UUID validator
 *
 */
class UuidValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = UuidValidator::class;

    /**
     * @test
     */
    public function validatorAcceptsCorrectUUIDs()
    {
        $this->assertFalse($this->validator->validate('e104e469-9030-4b98-babf-3990f07dd3f1')->hasErrors());
        $this->assertFalse($this->validator->validate('533548ca-8914-4a19-9404-ef390a6ce387')->hasErrors());
    }

    /**
     * @test
     */
    public function tooShortUUIDIsRejected()
    {
        $this->assertTrue($this->validator->validate('e104e469-9030-4b98-babf-3990f07')->hasErrors());
    }

    /**
     * @test
     */
    public function tooLongButValidUUIDIsRejected()
    {
        $this->assertTrue($this->validator->validate('e104e469-9030-4b98-babf-3990f07dd3f1-3990f07dd3f1')->hasErrors());
        $this->assertTrue($this->validator->validate('abcde-533548ca-8914-4a19-9404-ef390a6ce387-xyz')->hasErrors());
    }

    /**
     * @test
     */
    public function UUIDWithOtherThanHexValuesIsRejected()
    {
        $this->assertTrue($this->validator->validate('e104e469-9030-4g98-babf-3990f07dd3f1')->hasErrors());
    }

    /**
     * @test
     */
    public function UUIDValidatorCreatesTheCorrectErrorIfTheSubjectIsInvalid()
    {
        $expected = [new Validation\Error('The given subject was not a valid UUID.', 1221565853)];
        $this->assertEquals($expected, $this->validator->validate('e104e469-9030-4b98-babf-3990f07')->getErrors());
    }
}
