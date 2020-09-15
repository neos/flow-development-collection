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

use Neos\Flow\Validation\Validator\EmailAddressValidator;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the email address validator
 *
 */
class EmailAddressValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = EmailAddressValidator::class;

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsNull()
    {
        $this->assertFalse($this->validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString()
    {
        $this->assertFalse($this->validator->validate('')->hasErrors());
    }

    /**
     * Data provider with valid email addresses
     *
     * @return array
     */
    public function validAddresses()
    {
        return [
            ['andreas.foerthner@netlogix.de'],
            ['user@localhost.localdomain'],
            ['info@guggenheim.museum'],
            ['just@test.invalid'],
            ['just+spam@test.de']
        ];
    }

    /**
     * @test
     * @dataProvider validAddresses
     */
    public function emailAddressValidatorReturnsNoErrorsForAValidEmailAddress($address)
    {
        $this->assertFalse($this->validator->validate($address)->hasErrors());
    }

    /**
     * Data provider with invalid email addresses
     *
     * @return array
     */
    public function invalidAddresses()
    {
        return [
            ['andreas.foerthner@'],
            ['@neos.io'],
            ['someone@neos.'],
            ['local@192.168.2'],
            ['local@192.168.270.1'],
            ['foo@bar.com' . chr(0)],
            ['foo@bar.org' . chr(10)],
            ['andreas@foerthner@example.com'],
            ['some@one.net ']
        ];
    }

    /**
     * @test
     * @dataProvider invalidAddresses
     */
    public function emailAddressValidatorReturnsFalseForAnInvalidEmailAddress($address)
    {
        $this->assertTrue($this->validator->validate($address)->hasErrors());
    }

    /**
     * @test
     */
    public function emailValidatorCreatesTheCorrectErrorForAnInvalidEmailAddress()
    {
        $this->assertEquals(1, count($this->validator->validate('notAValidMail@Address')->getErrors()));
    }
}
