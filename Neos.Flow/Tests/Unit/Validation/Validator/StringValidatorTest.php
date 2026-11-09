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
use Neos\Flow\Validation\Validator\StringValidator;
use PHPUnit\Framework\Attributes\Test;

require_once('AbstractValidatorTestcase.php');
/**
 * Testcase for the string length validator
 *
 */
final class StringValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = StringValidator::class;

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
    public function stringValidatorShouldValidateString()
    {
        self::assertFalse($this->validator->validate('Hello World')->hasErrors());
    }

    #[Test]
    public function stringValidatorShouldReturnErrorIfNumberIsGiven()
    {
        self::assertTrue($this->validator->validate(42)->hasErrors());
    }

    #[Test]
    public function stringValidatorShouldReturnErrorIfObjectWithToStringMethodStringIsGiven()
    {
        $className = 'TestClass' . md5(uniqid((string)mt_rand(), true));

        eval('
			class ' . $className . ' {
				public function __toString() {
					return "ASDF";
				}
			}
		');
        $object = new $className();
        self::assertTrue($this->validator->validate($object)->hasErrors());
    }
}
