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

use Neos\Flow\Validation\Validator\GenericObjectValidator;
use Neos\Error\Messages as Error;
use Neos\Flow\Validation\Validator\IntegerValidator;
use Neos\Flow\Validation\Validator\ValidatorInterface;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the Generic Object Validator
 *
 */
class GenericObjectValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = GenericObjectValidator::class;

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
    public function validatorShouldReturnErrorsIfTheValueIsNoObjectAndNotNull()
    {
        $this->assertTrue($this->validator->validate('foo')->hasErrors());
    }

    /**
     * @test
     */
    public function validatorShouldReturnNoErrorsIfTheValueIsNull()
    {
        $this->assertFalse($this->validator->validate(null)->hasErrors());
    }

    /**
     * @return array
     */
    public function dataProviderForValidator()
    {
        $error1 = new Error\Error('error1', 1);
        $error2 = new Error\Error('error2', 2);

        $emptyResult1 = new Error\Result();
        $emptyResult2 = new Error\Result();

        $resultWithError1 = new Error\Result();
        $resultWithError1->addError($error1);

        $resultWithError2 = new Error\Result();
        $resultWithError2->addError($error2);

        $classNameForObjectWithPrivateProperties = 'B' . md5(uniqid(mt_rand(), true));
        eval('class ' . $classNameForObjectWithPrivateProperties . '{ protected $foo = \'foovalue\'; protected $bar = \'barvalue\'; }');
        $objectWithPrivateProperties = new $classNameForObjectWithPrivateProperties();

        return [
            // If no errors happened, this is shown
            [$objectWithPrivateProperties, $emptyResult1, $emptyResult2, []],

            // If errors on two properties happened, they are merged together.
            [$objectWithPrivateProperties, $resultWithError1, $resultWithError2, ['foo' => [$error1], 'bar' => [$error2]]]
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForValidator
     */
    public function validateChecksAllPropertiesForWhichAPropertyValidatorExists($mockObject, $validationResultForFoo, $validationResultForBar, $errors)
    {
        $validatorForFoo = $this->createMock(ValidatorInterface::class);
        $validatorForFoo->expects($this->once())->method('validate')->with('foovalue')->will($this->returnValue($validationResultForFoo));

        $validatorForBar = $this->createMock(ValidatorInterface::class);
        $validatorForBar->expects($this->once())->method('validate')->with('barvalue')->will($this->returnValue($validationResultForBar));

        $this->validator->addPropertyValidator('foo', $validatorForFoo);
        $this->validator->addPropertyValidator('bar', $validatorForBar);
        $this->assertEquals($errors, $this->validator->validate($mockObject)->getFlattenedErrors());
    }

    /**
     * @test
     */
    public function validateCanHandleRecursiveTargetsWithoutEndlessLooping()
    {
        $classNameA = 'B' . md5(uniqid(mt_rand(), true));
        eval('class ' . $classNameA . '{ public $b; }');
        $classNameB = 'B' . md5(uniqid(mt_rand(), true));
        eval('class ' . $classNameB . '{ public $a; }');
        $A = new $classNameA();
        $B = new $classNameB();
        $A->b = $B;
        $B->a = $A;

        $aValidator = new GenericObjectValidator([]);
        $bValidator = new GenericObjectValidator([]);
        $aValidator->addPropertyValidator('b', $bValidator);
        $bValidator->addPropertyValidator('a', $aValidator);

        $this->assertFalse($aValidator->validate($A)->hasErrors());
    }

    /**
     * @test
     */
    public function validateDetectsFailuresInRecursiveTargetsI()
    {
        $classNameA = 'A' . md5(uniqid(mt_rand(), true));
        eval('class ' . $classNameA . '{ public $b; }');
        $classNameB = 'B' . md5(uniqid(mt_rand(), true));
        eval('class ' . $classNameB . '{ public $a; public $uuid = 0xF; }');
        $A = new $classNameA();
        $B = new $classNameB();
        $A->b = $B;
        $B->a = $A;

        $aValidator = $this->getValidator();
        $bValidator = $this->getValidator();

        $aValidator->addPropertyValidator('b', $bValidator);
        $bValidator->addPropertyValidator('a', $aValidator);

        $error = new Error\Error('error1', 123);
        $result = new Error\Result();
        $result->addError($error);
        $mockUuidValidator = $this->createMock(ValidatorInterface::class);
        $mockUuidValidator->expects($this->any())->method('validate')->with(0xF)->will($this->returnValue($result));
        $bValidator->addPropertyValidator('uuid', $mockUuidValidator);

        $this->assertSame(['b.uuid' => [$error]], $aValidator->validate($A)->getFlattenedErrors());
    }

    /**
     * @test
     */
    public function validateDetectsFailuresInRecursiveTargetsII()
    {
        $classNameA = 'A' . md5(uniqid(mt_rand(), true));
        eval('class ' . $classNameA . '{ public $b; public $uuid = 0xF; }');
        $classNameB = 'B' . md5(uniqid(mt_rand(), true));
        eval('class ' . $classNameB . '{ public $a; public $uuid = 0xF; }');
        $A = new $classNameA();
        $B = new $classNameB();
        $A->b = $B;
        $B->a = $A;

        $aValidator = $this->getValidator();
        $bValidator = $this->getValidator();

        $aValidator->addPropertyValidator('b', $bValidator);
        $bValidator->addPropertyValidator('a', $aValidator);

        $error1 = new Error\Error('error1', 123);
        $result1 = new Error\Result();
        $result1->addError($error1);
        $mockUuidValidator = $this->createMock(ValidatorInterface::class);
        $mockUuidValidator->expects($this->any())->method('validate')->with(0xF)->will($this->returnValue($result1));
        $aValidator->addPropertyValidator('uuid', $mockUuidValidator);
        $bValidator->addPropertyValidator('uuid', $mockUuidValidator);

        $this->assertSame(['b.uuid' => [$error1], 'uuid' => [$error1]], $aValidator->validate($A)->getFlattenedErrors());
    }

    /**
     * @test
     */
    public function objectsAreValidatedOnlyOnce()
    {
        $className = 'A' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . '{ public $integer = 1; }');
        $object = new $className();

        $integerValidator = $this->getAccessibleMock(IntegerValidator::class);
        $matcher = $this->any();
        $integerValidator->expects($matcher)->method('validate')->with(1)->will($this->returnValue(new Error\Result()));

        $validator = $this->getValidator();
        $validator->addPropertyValidator('integer', $integerValidator);

        // Call the validation twice
        $validator->validate($object);
        $validator->validate($object);

        $this->assertEquals(1, $matcher->getInvocationCount());
    }
}
