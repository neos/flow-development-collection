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
 * Testcase for the Generic Object Validator
 *
 */
class GenericObjectValidatorTest extends \TYPO3\Flow\Tests\Unit\Validation\Validator\AbstractValidatorTestcase
{
    protected $validatorClassName = \TYPO3\Flow\Validation\Validator\GenericObjectValidator::class;

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
        $error1 = new \TYPO3\Flow\Error\Error('error1', 1);
        $error2 = new \TYPO3\Flow\Error\Error('error2', 2);

        $emptyResult1 = new \TYPO3\Flow\Error\Result();
        $emptyResult2 = new \TYPO3\Flow\Error\Result();

        $resultWithError1 = new \TYPO3\Flow\Error\Result();
        $resultWithError1->addError($error1);

        $resultWithError2 = new \TYPO3\Flow\Error\Result();
        $resultWithError2->addError($error2);

        $classNameForObjectWithPrivateProperties = 'B' . md5(uniqid(mt_rand(), true));
        eval('class ' . $classNameForObjectWithPrivateProperties . '{ protected $foo = \'foovalue\'; protected $bar = \'barvalue\'; }');
        $objectWithPrivateProperties = new $classNameForObjectWithPrivateProperties();

        return array(
            // If no errors happened, this is shown
            array($objectWithPrivateProperties, $emptyResult1, $emptyResult2, array()),

            // If errors on two properties happened, they are merged together.
            array($objectWithPrivateProperties, $resultWithError1, $resultWithError2, array('foo' => array($error1), 'bar' => array($error2)))
        );
    }

    /**
     * @test
     * @dataProvider dataProviderForValidator
     */
    public function validateChecksAllPropertiesForWhichAPropertyValidatorExists($mockObject, $validationResultForFoo, $validationResultForBar, $errors)
    {
        $validatorForFoo = $this->createMock(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class);
        $validatorForFoo->expects($this->once())->method('validate')->with('foovalue')->will($this->returnValue($validationResultForFoo));

        $validatorForBar = $this->createMock(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class);
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

        $aValidator = new \TYPO3\Flow\Validation\Validator\GenericObjectValidator(array());
        $bValidator = new \TYPO3\Flow\Validation\Validator\GenericObjectValidator(array());
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

        $error = new \TYPO3\Flow\Error\Error('error1', 123);
        $result = new \TYPO3\Flow\Error\Result();
        $result->addError($error);
        $mockUuidValidator = $this->createMock(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class);
        $mockUuidValidator->expects($this->any())->method('validate')->with(0xF)->will($this->returnValue($result));
        $bValidator->addPropertyValidator('uuid', $mockUuidValidator);

        $this->assertSame(array('b.uuid' => array($error)), $aValidator->validate($A)->getFlattenedErrors());
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

        $error1 = new \TYPO3\Flow\Error\Error('error1', 123);
        $result1 = new \TYPO3\Flow\Error\Result();
        $result1->addError($error1);
        $mockUuidValidator = $this->createMock(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class);
        $mockUuidValidator->expects($this->any())->method('validate')->with(0xF)->will($this->returnValue($result1));
        $aValidator->addPropertyValidator('uuid', $mockUuidValidator);
        $bValidator->addPropertyValidator('uuid', $mockUuidValidator);

        $this->assertSame(array('b.uuid' => array($error1), 'uuid' => array($error1)), $aValidator->validate($A)->getFlattenedErrors());
    }

    /**
     * @test
     */
    public function objectsAreValidatedOnlyOnce()
    {
        $className = 'A' . md5(uniqid(mt_rand(), true));
        eval('class ' . $className . '{ public $integer = 1; }');
        $object = new $className();

        $integerValidator = $this->getAccessibleMock(\TYPO3\Flow\Validation\Validator\IntegerValidator::class);
        $matcher = $this->any();
        $integerValidator->expects($matcher)->method('validate')->with(1)->will($this->returnValue(new \TYPO3\Flow\Error\Result()));

        $validator = $this->getValidator();
        $validator->addPropertyValidator('integer', $integerValidator);

        // Call the validation twice
        $validator->validate($object);
        $validator->validate($object);

        $this->assertEquals(1, $matcher->getInvocationCount());
    }
}
