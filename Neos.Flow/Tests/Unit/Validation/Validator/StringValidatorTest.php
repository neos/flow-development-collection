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

use Neos\Flow\Validation\Validator\StringValidator;

require_once('AbstractValidatorTestcase.php');
/**
 * Testcase for the string length validator
 *
 */
class StringValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = StringValidator::class;

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
    public function stringValidatorShouldValidateString()
    {
        $this->assertFalse($this->validator->validate('Hello World')->hasErrors());
    }

    /**
     * @test
     */
    public function stringValidatorShouldReturnErrorIfNumberIsGiven()
    {
        $this->assertTrue($this->validator->validate(42)->hasErrors());
    }

    /**
     * @test
     */
    public function stringValidatorShouldReturnErrorIfObjectWithToStringMethodStringIsGiven()
    {
        $className = 'TestClass' . md5(uniqid(mt_rand(), true));

        eval('
			class ' . $className . ' {
				public function __toString() {
					return "ASDF";
				}
			}
		');
        $object = new $className();
        $this->assertTrue($this->validator->validate($object)->hasErrors());
    }
}
