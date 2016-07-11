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
 * Testcase for the string length validator
 *
 */
class StringLengthValidatorTest extends \TYPO3\Flow\Tests\Unit\Validation\Validator\AbstractValidatorTestcase
{
    protected $validatorClassName = \TYPO3\Flow\Validation\Validator\StringLengthValidator::class;

    /**
     * @var \TYPO3\Flow\Validation\Validator\StringLengthValidator
     */
    protected $validator;

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
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorForAStringShorterThanMaxLengthAndLongerThanMinLength()
    {
        $this->validatorOptions(array('minimum' => 0, 'maximum' => 50));
        $this->assertFalse($this->validator->validate('this is a very simple string')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsErrorForAStringShorterThanThanMinLength()
    {
        $this->validatorOptions(array('minimum' => 50, 'maximum' => 100));
        $this->assertTrue($this->validator->validate('this is a very short string')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsErrorsForAStringLongerThanThanMaxLength()
    {
        $this->validatorOptions(array('minimum' => 5, 'maximum' => 10));
        $this->assertTrue($this->validator->validate('this is a very short string')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorsForAStringLongerThanThanMinLengthAndMaxLengthNotSpecified()
    {
        $this->validatorOptions(array('minimum' => 5));
        $this->assertFalse($this->validator->validate('this is a very short string')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorsForAStringShorterThanThanMaxLengthAndMinLengthNotSpecified()
    {
        $this->validatorOptions(array('maximum' => 100));
        $this->assertFalse($this->validator->validate('this is a very short string')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorsForAStringLengthEqualToMaxLengthAndMinLengthNotSpecified()
    {
        $this->validatorOptions(array('maximum' => 10));
        $this->assertFalse($this->validator->validate('1234567890')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorForAStringLengthEqualToMinLengthAndMaxLengthNotSpecified()
    {
        $this->validatorOptions(array('minimum' => 10));
        $this->assertFalse($this->validator->validate('1234567890')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorIfMinLengthAndMaxLengthAreEqualAndTheGivenStringMatchesThisValue()
    {
        $this->validatorOptions(array('minimum' => 10, 'maximum' => 10));
        $this->assertFalse($this->validator->validate('1234567890')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorsfTheStringLengthIsEqualToMaxLength()
    {
        $this->validatorOptions(array('minimum' => 1, 'maximum' => 10));
        $this->assertFalse($this->validator->validate('1234567890')->hasErrors());
    }

    /**
     * @test
     */
    public function stringLengthValidatorReturnsNoErrorIfTheStringLengthIsEqualToMinLength()
    {
        $this->validatorOptions(array('minimum' => 10, 'maximum' => 100));
        $this->assertFalse($this->validator->validate('1234567890')->hasErrors());
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException
     */
    public function stringLengthValidatorThrowsAnExceptionIfMinLengthIsGreaterThanMaxLength()
    {
        $this->validator = $this->getMockBuilder(\TYPO3\Flow\Validation\Validator\StringLengthValidator::class)->disableOriginalConstructor()->setMethods(array('addError'))->getMock();
        $this->validatorOptions(array('minimum' => 101, 'maximum' => 100));
        $this->validator->validate('1234567890');
    }

    /**
     * @test
     */
    public function stringLengthValidatorInsertsAnErrorObjectIfValidationFails()
    {
        $this->validatorOptions(array('minimum' => 50, 'maximum' => 100));

        $this->assertEquals(1, count($this->validator->validate('this is a very short string')->getErrors()));
    }

    /**
     * @test
     */
    public function stringLengthValidatorCanHandleAnObjectWithAToStringMethod()
    {
        $this->validator = $this->getMockBuilder(\TYPO3\Flow\Validation\Validator\StringLengthValidator::class)->disableOriginalConstructor()->setMethods(array('addError'))->getMock();
        $this->validatorOptions(array('minimum' => 5, 'maximum' => 100));

        $className = 'TestClass' . md5(uniqid(mt_rand(), true));

        eval('
			class ' . $className . ' {
				public function __toString() {
					return \'some string\';
				}
			}
		');

        $object = new $className();
        $this->assertFalse($this->validator->validate($object)->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsErrorsIfTheGivenObjectCanNotBeConvertedToAString()
    {
        $this->validator = $this->getMockBuilder(\TYPO3\Flow\Validation\Validator\StringLengthValidator::class)->disableOriginalConstructor()->setMethods(array('addError'))->getMock();
        $this->validatorOptions(array('minimum' => 5, 'maximum' => 100));

        $className = 'TestClass' . md5(uniqid(mt_rand(), true));

        eval('
			class ' . $className . ' {
				protected $someProperty;
			}
		');

        $object = new $className();
        $this->assertTrue($this->validator->validate($object)->hasErrors());
    }

    /**
     * @test
     */
    public function validateRegardsMultibyteStringsCorrectly()
    {
        $this->validatorOptions(array('maximum' => 8));
        $this->assertFalse($this->validator->validate('überlang')->hasErrors());
    }
}
