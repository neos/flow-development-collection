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

use Egulias\EmailValidator\EmailValidator;
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
     * @param array $options
     * @return \PHPUnit_Framework_MockObject_MockObject|EmailAddressValidator
     */
    protected function getValidator($options = [])
    {
        $validator = $this->getAccessibleMock($this->validatorClassName, ['dummy'], [$options], '', true);

        $emailValidator = new EmailValidator();
        $this->inject($validator, 'emailValidator', $emailValidator);

        return $validator;
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsNull()
    {
        self::assertFalse($this->validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString()
    {
        self::assertFalse($this->validator->validate('')->hasErrors());
    }

    /**
     * Data provider with valid email addresses
     *
     * @return array
     */
    public function validAddresses()
    {
        return [
            ['simple@example.com'],
            ['very.common@example.com'],
            ['disposable.style.email.with+symbol@example.com'],
            ['other.email-with-hyphen@example.com'],
            ['fully-qualified-domain@example.com'],
            ['user.name+tag+sorting@example.com'], // (may go to user.name@example.com inbox depending on mail server)
            ['x@example.com'], // (one-letter local-part)
            ['example-indeed@strange-example.com'],
            ['admin@mailserver1'], // (local domain name with no TLD, although ICANN highly discourages dotless email addresses[13])
            ['example@s.example'], // (see the List of Internet top-level domains)
            ['"Full Name"@example.org'], // (space between the quotes)
            ['"john..doe"@example.org'], // (quoted double dot)
            ['mailhost!username@example.org'], // (bangified host route used for uucp mailers)
            ['user%example.com@example.org'], // (% escaped mail route to user@example.com via example.org)
            ['hellö@neos.io'], // umlaut in local part
            ['1500111@профи-инвест.рф'], // unicode
            ['user@localhost.localdomain'], // "new" domain name
            ['info@guggenheim.museum'], // "new" domain name
            ['just@test.invalid'], // "new" domain name
            ['test@[192.168.230.1]'], // IPv4 address literal
            ['test@[2001:db8:85a3:8d3:1319:8a2e:370:7348]'], // IPv6 address literal
        ];
    }

    /**
     * @test
     * @dataProvider validAddresses
     */
    public function emailAddressValidatorHasNoErrorsForAValidEmailAddress($address)
    {
        self::assertFalse($this->validator->validate($address)->hasErrors());
    }

    /**
     * Data provider with invalid email addresses
     *
     * @return array
     */
    public function invalidAddresses()
    {
        return [
            ['Abc.example.com'], // (no @ character)
            ['A@b@c@example.com'], // (only one @ is allowed outside quotation marks)
            ['a"b(c)d,e:f;g<h>i[j\k]l@example.com'], // (none of the special characters in this local-part are allowed outside quotation marks)
            ['just"not"right@example.com'], // (quoted strings must be dot separated or the only element making up the local-part)
            ['this is"not\allowed@example.com'], // (spaces, quotes, and backslashes may only exist when within quoted strings and preceded by a backslash)
            ['this\ still\"not\\allowed@example.com'], // (even if escaped (preceded by a backslash), spaces, quotes, and backslashes must still be contained by quotes)

            ['andreas.foerthner@'], // no domain part
            ['@neos.io'], // no local part
            ['someone@neos.'], // invalid domain part
            ['[2001:db8:85a3:8d3:1319:8a2e:370]'], // incomplete IPv6 address
            ['[2001:db8:85a3:8d3:1319:8a2e:bar:7348]'], // invalid IPv6 address
            ['foo@bar.org' . chr(10)], // ends with a NL char

            // This is RFC 5322 compliant however it contains domain characters that are not allowed by DNS.
            // So basically it is "valid" because of the specification but it is not valid according to DNS specification.
            // ['i_like_underscore@but_its_not_allow_in_this_part.example.com'], // (Underscore is not allowed in domain part)

            // this is considered valid, real-world use would apply a trim() anyway?
            // ['foo@bar.com' . chr(0)], // ends with a 0 char
        ];
    }

    /**
     * Data provider with invalid email addresses
     *
     * @return array
     */
    public function addressesWithWarnings()
    {
        return [
            ['1234567890123456789012345678901234567890123456789012345678901234xyz@example.com'], // (local part is longer than 64 characters)
            ['local@[192.168.2]'], // incomplete IPv4 address
            ['local@[192.168.270.1]'], // invalid IPv4 address
            ['some@one.net '], // ends with space char
        ];
    }

    /**
     * @test
     * @dataProvider invalidAddresses
     */
    public function emailAddressValidatorHasErrorsForAnInvalidEmailAddress($address)
    {
        self::assertTrue($this->validator->validate($address)->hasErrors());
    }

    /**
     * @test
     * @dataProvider addressesWithWarnings
     */
    public function emailAddressValidatorUsingStrictHasErrorsForAnEmailAddressWithWarnings($address)
    {
        $this->validatorOptions(['strict' => true]);
        self::assertTrue($this->validator->validate($address)->hasErrors());
    }

    /**
     * @test
     */
    public function emailValidatorCreatesOneErrorForAnInvalidEmailAddress()
    {
        self::assertCount(1, $this->validator->validate('notAValidMailAddress')->getErrors());
    }
}
