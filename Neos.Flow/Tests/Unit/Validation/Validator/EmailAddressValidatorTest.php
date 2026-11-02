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
use Egulias\EmailValidator\EmailValidator;
use Neos\Flow\Validation\Validator\EmailAddressValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the email address validator
 *
 */
final class EmailAddressValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = EmailAddressValidator::class;

    /**
     * @param array $options
     * @return \PHPUnit_Framework_MockObject_MockObject|EmailAddressValidator
     */
    protected function getValidator($options = [])
    {
        $validator = $this->getAccessibleMock($this->validatorClassName, [], [$options], '', true);

        $emailValidator = new EmailValidator();
        $this->inject($validator, 'emailValidator', $emailValidator);

        return $validator;
    }

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
     * Data provider with valid email addresses
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function validAddresses(): \Iterator
    {
        yield ['simple@example.com'];
        yield ['very.common@example.com'];
        yield ['disposable.style.email.with+symbol@example.com'];
        yield ['other.email-with-hyphen@example.com'];
        yield ['fully-qualified-domain@example.com'];
        yield ['user.name+tag+sorting@example.com'];
        // (may go to user.name@example.com inbox depending on mail server)
        yield ['x@example.com'];
        // (one-letter local-part)
        yield ['example-indeed@strange-example.com'];
        yield ['admin@mailserver1'];
        // (local domain name with no TLD, although ICANN highly discourages dotless email addresses[13])
        yield ['example@s.example'];
        // (see the List of Internet top-level domains)
        yield ['"Full Name"@example.org'];
        // (space between the quotes)
        yield ['"john..doe"@example.org'];
        // (quoted double dot)
        yield ['mailhost!username@example.org'];
        // (bangified host route used for uucp mailers)
        yield ['user%example.com@example.org'];
        // (% escaped mail route to user@example.com via example.org)
        yield ['hellö@neos.io'];
        // umlaut in local part
        yield ['1500111@профи-инвест.рф'];
        // unicode
        yield ['user@localhost.localdomain'];
        // "new" domain name
        yield ['info@guggenheim.museum'];
        // "new" domain name
        yield ['just@test.invalid'];
        // "new" domain name
        yield ['test@[192.168.230.1]'];
        // IPv4 address literal
        yield ['test@[2001:db8:85a3:8d3:1319:8a2e:370:7348]'];
    }

    #[DataProvider('validAddresses')]
    #[Test]
    public function emailAddressValidatorHasNoErrorsForAValidEmailAddress($address)
    {
        self::assertFalse($this->validator->validate($address)->hasErrors());
    }

    /**
     * Data provider with invalid email addresses
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function invalidAddresses(): \Iterator
    {
        yield ['Abc.example.com'];
        // (no @ character)
        yield ['A@b@c@example.com'];
        // (only one @ is allowed outside quotation marks)
        yield ['a"b(c)d,e:f;g<h>i[j\k]l@example.com'];
        // (none of the special characters in this local-part are allowed outside quotation marks)
        yield ['just"not"right@example.com'];
        // (quoted strings must be dot separated or the only element making up the local-part)
        yield ['this is"not\allowed@example.com'];
        // (spaces, quotes, and backslashes may only exist when within quoted strings and preceded by a backslash)
        yield ['this\ still\"not\\allowed@example.com'];
        // (even if escaped (preceded by a backslash), spaces, quotes, and backslashes must still be contained by quotes)
        yield ['andreas.foerthner@'];
        // no domain part
        yield ['@neos.io'];
        // no local part
        yield ['someone@neos.'];
        // invalid domain part
        yield ['[2001:db8:85a3:8d3:1319:8a2e:370]'];
        // incomplete IPv6 address
        yield ['[2001:db8:85a3:8d3:1319:8a2e:bar:7348]'];
        // invalid IPv6 address
        yield ['foo@bar.org' . chr(10)];
    }

    /**
     * Data provider with invalid email addresses
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function addressesWithWarnings(): \Iterator
    {
        yield ['1234567890123456789012345678901234567890123456789012345678901234xyz@example.com'];
        // (local part is longer than 64 characters)
        yield ['local@[192.168.2]'];
        // incomplete IPv4 address
        yield ['local@[192.168.270.1]'];
        // invalid IPv4 address
        yield ['some@one.net '];
    }

    #[DataProvider('invalidAddresses')]
    #[Test]
    public function emailAddressValidatorHasErrorsForAnInvalidEmailAddress($address)
    {
        self::assertTrue($this->validator->validate($address)->hasErrors());
    }

    #[DataProvider('addressesWithWarnings')]
    #[Test]
    public function emailAddressValidatorUsingStrictHasErrorsForAnEmailAddressWithWarnings($address)
    {
        $this->validatorOptions(['strict' => true]);
        self::assertTrue($this->validator->validate($address)->hasErrors());
    }

    #[Test]
    public function emailValidatorCreatesOneErrorForAnInvalidEmailAddress()
    {
        self::assertCount(1, $this->validator->validate('notAValidMailAddress')->getErrors());
    }
}
