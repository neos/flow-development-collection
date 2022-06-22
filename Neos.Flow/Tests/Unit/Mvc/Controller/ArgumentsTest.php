<?php
namespace Neos\Flow\Tests\Unit\Mvc\Controller;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\Argument;
use Neos\Flow\Mvc\Exception\NoSuchArgumentException;
use Neos\Error\Messages as FlowError;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the MVC Controller Arguments
 */
class ArgumentsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function addingAnArgumentManuallyWorks()
    {
        $arguments = new Arguments();
        $newArgument = new Argument('argumentName1234', 'Text');

        $arguments->addArgument($newArgument);
        self::assertSame($newArgument, $arguments->getArgument('argumentName1234'), 'The added and retrieved argument is not the same.');
    }

    /**
     * @test
     */
    public function addingAnArgumentReplacesArgumentWithSameName()
    {
        $arguments = new Arguments();

        $firstArgument = new Argument('argumentName1234', 'Text');
        $arguments->addArgument($firstArgument);

        $secondArgument = new Argument('argumentName1234', 'Text');
        $arguments->addArgument($secondArgument);

        self::assertSame($secondArgument, $arguments->getArgument('argumentName1234'), 'The added and retrieved argument is not the same.');
    }

    /**
     * @test
     */
    public function addingArgumentThroughArrayAccessWorks()
    {
        $arguments = new Arguments();
        $argument = new Argument('argumentName1234', 'Text');
        $arguments[] = $argument;
        self::assertTrue($arguments->hasArgument('argumentName1234'), 'Added argument does not exist.');
        self::assertSame($argument, $arguments->getArgument('argumentName1234'), 'Added and retrieved arguments are not the same.');
    }

    /**
     * @test
     */
    public function retrievingArgumentThroughArrayAccessWorks()
    {
        $arguments = new Arguments();
        $newArgument = $arguments->addNewArgument('someArgument');
        self::assertSame($newArgument, $arguments['someArgument'], 'Argument retrieved by array access is not the one we added.');
    }

    /**
     * @test
     */
    public function getArgumentWithNonExistingArgumentNameThrowsException()
    {
        $arguments = new Arguments();
        try {
            $arguments->getArgument('someArgument');
            $this->fail('getArgument() did not throw an exception although the specified argument does not exist.');
        } catch (NoSuchArgumentException $exception) {
            self::assertTrue(true);
        }
    }

    /**
     * @test
     */
    public function issetReturnsCorrectResult()
    {
        $arguments = new Arguments();
        self::assertFalse(isset($arguments['someArgument']), 'isset() did not return false.');
        $arguments->addNewArgument('someArgument');
        self::assertTrue(isset($arguments['someArgument']), 'isset() did not return true.');
    }

    /**
     * @test
     */
    public function getArgumentNamesReturnsNamesOfAddedArguments()
    {
        $arguments = new Arguments();
        $arguments->addNewArgument('first');
        $arguments->addNewArgument('second');
        $arguments->addNewArgument('third');

        $expectedArgumentNames = ['first', 'second', 'third'];
        self::assertEquals($expectedArgumentNames, $arguments->getArgumentNames(), 'Returned argument names were not as expected.');
    }

    /**
     * @test
     */
    public function addNewArgumentCreatesAndAddsNewArgument()
    {
        $arguments = new Arguments();
        $addedArgument = $arguments->addNewArgument('dummyName');
        self::assertInstanceOf(Argument::class, $addedArgument, 'addNewArgument() either did not add a new argument or did not return it.');

        $retrievedArgument = $arguments['dummyName'];
        self::assertSame($addedArgument, $retrievedArgument, 'The added and the retrieved argument are not the same.');

        self::assertEquals('dummyName', $addedArgument->getName(), 'The name of the added argument is not as expected.');
    }

    /**
     * @test
     */
    public function addNewArgumentCanAddArgumentsMarkedAsRequired()
    {
        $arguments = new Arguments();
        $addedArgument = $arguments->addNewArgument('dummyName', 'Text', true);
        self::assertTrue($addedArgument->isRequired(), 'addNewArgument() did not create an argument that is marked as required.');
    }

    /**
     * @test
     */
    public function addNewArgumentCanAddArgumentsMarkedAsOptionalWithDefaultValues()
    {
        $arguments = new Arguments();
        $defaultValue = 'Default Value 42';
        $addedArgument = $arguments->addNewArgument('dummyName', 'Text', false, $defaultValue);
        self::assertEquals($defaultValue, $addedArgument->getValue(), 'addNewArgument() did not store the default value in the argument.');
    }

    /**
     * @test
     */
    public function callingInvalidMethodThrowsException()
    {
        $this->expectException(\LogicException::class);
        $arguments = new Arguments();
        $arguments->nonExistingMethod();
    }

    /**
     * @test
     */
    public function removeAllClearsAllArguments()
    {
        $arguments = new Arguments();
        $arguments->addArgument(new Argument('foo', 'Text'));

        $arguments->removeAll();

        self::assertFalse($arguments->hasArgument('foo'));
    }

    /**
     * @test
     */
    public function getValidationResultsShouldFetchAllValidationResltsFromArguments()
    {
        $error1 = new FlowError\Error('Validation error', 1234);
        $error2 = new FlowError\Error('Validation error 2', 1235);

        $results1 = new FlowError\Result();
        $results1->addError($error1);

        $results2 = new FlowError\Result();
        $results2->addError($error2);

        $argument1 = $this->getMockBuilder(Argument::class)->setMethods(['getValidationResults'])->setConstructorArgs(['name1', 'string'])->getMock();
        $argument1->expects(self::once())->method('getValidationResults')->will(self::returnValue($results1));

        $argument2 = $this->getMockBuilder(Argument::class)->setMethods(['getValidationResults'])->setConstructorArgs(['name2', 'string'])->getMock();
        $argument2->expects(self::once())->method('getValidationResults')->will(self::returnValue($results2));

        $arguments = new Arguments();
        $arguments->addArgument($argument1);
        $arguments->addArgument($argument2);
        self::assertSame(['name1' => [$error1], 'name2' => [$error2]], $arguments->getValidationResults()->getFlattenedErrors());
    }

    /**
     * @test
     */
    public function addingAnArgumentUsesStringAsDataTypeDefault()
    {
        $arguments = new Arguments();
        $argument = $arguments->addNewArgument('someArgumentName');

        self::assertEquals('string', $argument->getDataType());
    }
}
