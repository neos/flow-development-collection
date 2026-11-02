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
use Neos\Flow\Validation\Exception\InvalidValidationOptionsException;
use Neos\Flow\Validation\Validator\StringLengthValidator;
use PHPUnit\Framework\Attributes\Test;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the string length validator
 *
 */
final class StringLengthValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = StringLengthValidator::class;

    /**
     * @var StringLengthValidator
     */
    protected $validator;

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

    #[Test]
    public function stringLengthValidatorReturnsNoErrorForAStringShorterThanMaxLengthAndLongerThanMinLength()
    {
        $this->validatorOptions(['minimum' => 0, 'maximum' => 50]);
        self::assertFalse($this->validator->validate('this is a very simple string')->hasErrors());
    }

    #[Test]
    public function stringLengthValidatorReturnsErrorForAStringShorterThanThanMinLength()
    {
        $this->validatorOptions(['minimum' => 50, 'maximum' => 100]);
        self::assertTrue($this->validator->validate('this is a very short string')->hasErrors());
    }

    #[Test]
    public function stringLengthValidatorReturnsErrorsForAStringLongerThanThanMaxLength()
    {
        $this->validatorOptions(['minimum' => 5, 'maximum' => 10]);
        self::assertTrue($this->validator->validate('this is a very short string')->hasErrors());
    }

    #[Test]
    public function stringLengthValidatorReturnsNoErrorsForAStringLongerThanThanMinLengthAndMaxLengthNotSpecified()
    {
        $this->validatorOptions(['minimum' => 5]);
        self::assertFalse($this->validator->validate('this is a very short string')->hasErrors());
    }

    #[Test]
    public function stringLengthValidatorReturnsNoErrorsForAStringShorterThanThanMaxLengthAndMinLengthNotSpecified()
    {
        $this->validatorOptions(['maximum' => 100]);
        self::assertFalse($this->validator->validate('this is a very short string')->hasErrors());
    }

    #[Test]
    public function stringLengthValidatorReturnsNoErrorsForAStringLengthEqualToMaxLengthAndMinLengthNotSpecified()
    {
        $this->validatorOptions(['maximum' => 10]);
        self::assertFalse($this->validator->validate('1234567890')->hasErrors());
    }

    #[Test]
    public function stringLengthValidatorReturnsNoErrorForAStringLengthEqualToMinLengthAndMaxLengthNotSpecified()
    {
        $this->validatorOptions(['minimum' => 10]);
        self::assertFalse($this->validator->validate('1234567890')->hasErrors());
    }

    #[Test]
    public function stringLengthValidatorReturnsNoErrorIfMinLengthAndMaxLengthAreEqualAndTheGivenStringMatchesThisValue()
    {
        $this->validatorOptions(['minimum' => 10, 'maximum' => 10]);
        self::assertFalse($this->validator->validate('1234567890')->hasErrors());
    }

    #[Test]
    public function stringLengthValidatorReturnsNoErrorsfTheStringLengthIsEqualToMaxLength()
    {
        $this->validatorOptions(['minimum' => 1, 'maximum' => 10]);
        self::assertFalse($this->validator->validate('1234567890')->hasErrors());
    }

    #[Test]
    public function stringLengthValidatorReturnsNoErrorIfTheStringLengthIsEqualToMinLength()
    {
        $this->validatorOptions(['minimum' => 10, 'maximum' => 100]);
        self::assertFalse($this->validator->validate('1234567890')->hasErrors());
    }

    #[Test]
    public function stringLengthValidatorThrowsAnExceptionIfMinLengthIsGreaterThanMaxLength()
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->validator = $this->getMockBuilder(StringLengthValidator::class)->disableOriginalConstructor()->onlyMethods(['addError'])->getMock();
        $this->validatorOptions(['minimum' => 101, 'maximum' => 100]);
        $this->validator->validate('1234567890');
    }

    #[Test]
    public function stringLengthValidatorInsertsAnErrorObjectIfValidationFails()
    {
        $this->validatorOptions(['minimum' => 50, 'maximum' => 100]);

        self::assertCount(1, $this->validator->validate('this is a very short string')->getErrors());
    }

    #[Test]
    public function stringLengthValidatorCanHandleAnObjectWithAToStringMethod()
    {
        $this->validator = $this->getMockBuilder(StringLengthValidator::class)->disableOriginalConstructor()->onlyMethods(['addError'])->getMock();
        $this->validatorOptions(['minimum' => 5, 'maximum' => 100]);

        $className = 'TestClass' . md5(uniqid((string)mt_rand(), true));

        eval('
			class ' . $className . ' {
				public function __toString() {
					return \'some string\';
				}
			}
		');

        $object = new $className();
        self::assertFalse($this->validator->validate($object)->hasErrors());
    }

    #[Test]
    public function validateReturnsErrorsIfTheGivenObjectCanNotBeConvertedToAString()
    {
        $this->validator = $this->getMockBuilder(StringLengthValidator::class)->disableOriginalConstructor()->onlyMethods(['addError'])->getMock();
        $this->validatorOptions(['minimum' => 5, 'maximum' => 100]);

        $className = 'TestClass' . md5(uniqid((string)mt_rand(), true));

        eval('
			class ' . $className . ' {
				protected $someProperty;
			}
		');

        $object = new $className();
        self::assertTrue($this->validator->validate($object)->hasErrors());
    }

    #[Test]
    public function validateRegardsMultibyteStringsCorrectly()
    {
        $this->validatorOptions(['maximum' => 8]);
        self::assertFalse($this->validator->validate('überlang')->hasErrors());
    }

    #[Test]
    public function validateCountsHtmlTagsByDefault()
    {
        $this->validatorOptions(['maximum' => 14]);
        $this->assertTrue($this->validator->validate('Some <b>bold</b> text')->hasErrors());
    }

    #[Test]
    public function validateStripsHtmlTagsIfIgnoreHtmlOptionIsSet()
    {
        $this->validatorOptions(['maximum' => 14, 'ignoreHtml' => true]);
        $this->assertFalse($this->validator->validate('Some <b>bold</b> text')->hasErrors());
    }
}
