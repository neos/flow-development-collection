<?php
namespace TYPO3\Flow\Tests\Unit\Validation\Validator;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the email address validator
 *
 */
class EmailAddressValidatorTest extends \TYPO3\Flow\Tests\Unit\Validation\Validator\AbstractValidatorTestcase
{
    protected $validatorClassName = \TYPO3\Flow\Validation\Validator\EmailAddressValidator::class;

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
        return array(
            array('andreas.foerthner@netlogix.de'),
            array('user@localhost.localdomain'),
            array('info@guggenheim.museum'),
            array('just@test.invalid'),
            array('just+spam@test.de')
        );
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
        return array(
            array('andreas.foerthner@'),
            array('@typo3.org'),
            array('someone@typo3.'),
            array('local@192.168.2'),
            array('local@192.168.270.1'),
            array('foo@bar.com' . chr(0)),
            array('foo@bar.org' . chr(10)),
            array('andreas@foerthner@example.com'),
            array('some@one.net ')
        );
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
