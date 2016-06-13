<?php
namespace TYPO3\Flow\Tests\Unit\Cli;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the MVC CLI Request Builder
 *
 */
class RequestBuilderTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Flow\Cli\RequestBuilder
     */
    protected $requestBuilder;

    /**
     * @var \TYPO3\Flow\Object\ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * @var \TYPO3\Flow\Cli\Command
     */
    protected $mockCommand;

    /**
     * @var \TYPO3\Flow\Cli\CommandManager
     */
    protected $mockCommandManager;

    /**
     * @var \TYPO\Flow\Reflection\ReflectionService
     */
    protected $mockReflectionService;

    /**
     * Sets up this test case
     *
     */
    public function setUp()
    {
        $this->mockObjectManager = $this->createMock('TYPO3\Flow\Object\ObjectManagerInterface');
        $this->mockObjectManager->expects($this->any())->method('getObjectNameByClassName')->with('Acme\Test\Command\DefaultCommandController')->will($this->returnValue('Acme\Test\Command\DefaultCommandController'));

        $this->mockCommand = $this->getMockBuilder('TYPO3\Flow\Cli\Command')->disableOriginalConstructor()->getMock();
        $this->mockCommand->expects($this->any())->method('getControllerClassName')->will($this->returnValue('Acme\Test\Command\DefaultCommandController'));
        $this->mockCommand->expects($this->any())->method('getControllerCommandName')->will($this->returnValue('list'));

        $this->mockCommandManager = $this->createMock('TYPO3\Flow\Cli\CommandManager');
        $this->mockCommandManager->expects($this->any())->method('getCommandByIdentifier')->with('acme.test:default:list')->will($this->returnValue($this->mockCommand));

        $this->mockReflectionService = $this->createMock('TYPO3\Flow\Reflection\ReflectionService');

        $this->requestBuilder = new \TYPO3\Flow\Cli\RequestBuilder();
        $this->requestBuilder->injectObjectManager($this->mockObjectManager);
        $this->requestBuilder->injectReflectionService($this->mockReflectionService);
        $this->requestBuilder->injectCommandManager($this->mockCommandManager);
    }

    /**
     * Checks if a CLI request specifying a package, controller and action name results in the expected request object
     *
     * @test
     */
    public function cliAccessWithPackageControllerAndActionNameBuildsCorrectRequest()
    {
        $this->mockReflectionService->expects($this->once())->method('getMethodParameters')->will($this->returnValue(array()));

        $request = $this->requestBuilder->build('acme.test:default:list');
        $this->assertSame('Acme\Test\Command\DefaultCommandController', $request->getControllerObjectName());
        $this->assertSame('list', $request->getControllerCommandName(), 'The CLI request specifying a package, controller and action name did not return a request object pointing to the expected action.');
    }

    /**
     * @test
     */
    public function ifCommandCantBeResolvedTheHelpScreenIsShown()
    {
        // The following call is only made to satisfy PHPUnit. For some weird reason PHPUnit complains that the
        // mocked method ("getObjectNameByClassName") does not exist _if the mock object is not used_.
        $this->mockObjectManager->getObjectNameByClassName('Acme\Test\Command\DefaultCommandController');
        $this->mockCommandManager->getCommandByIdentifier('acme.test:default:list');

        $mockCommandManager = $this->createMock('TYPO3\Flow\Cli\CommandManager');
        $mockCommandManager->expects($this->any())->method('getCommandByIdentifier')->with('test:default:list')->will($this->throwException(new \TYPO3\Flow\Mvc\Exception\NoSuchCommandException()));
        $this->requestBuilder->injectCommandManager($mockCommandManager);

        $request = $this->requestBuilder->build('test:default:list');
        $this->assertSame('TYPO3\Flow\Command\HelpCommandController', $request->getControllerObjectName());
    }

    /**
     * Checks if a CLI request specifying some "console style" (--my-argument=value) arguments results in the expected request object
     *
     * @test
     */
    public function cliAccessWithPackageControllerActionAndArgumentsBuildsCorrectRequest()
    {
        $methodParameters = array(
            'testArgument' => array('optional' => false, 'type' => 'string'),
            'testArgument2' => array('optional' => false, 'type' => 'string')
        );
        $this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

        $request = $this->requestBuilder->build('acme.test:default:list --test-argument=value --test-argument2=value2');
        $this->assertTrue($request->hasArgument('testArgument'), 'The given "testArgument" was not found in the built request.');
        $this->assertTrue($request->hasArgument('testArgument2'), 'The given "testArgument2" was not found in the built request.');
        $this->assertSame($request->getArgument('testArgument'), 'value', 'The "testArgument" had not the given value.');
        $this->assertSame($request->getArgument('testArgument2'), 'value2', 'The "testArgument2" had not the given value.');
    }

    /**
     * Checks if a CLI request specifying some "console style" (--my-argument =value) arguments with spaces between name and value results in the expected request object
     *
     * @test
     */
    public function checkIfCliAccesWithPackageControllerActionAndArgumentsToleratesSpaces()
    {
        $methodParameters = array(
            'testArgument' => array('optional' => false, 'type' => 'string'),
            'testArgument2' => array('optional' => false, 'type' => 'string'),
            'testArgument3' => array('optional' => false, 'type' => 'string'),
            'testArgument4' => array('optional' => false, 'type' => 'string')
        );
        $this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

        $request = $this->requestBuilder->build('acme.test:default:list --test-argument= value --test-argument2 =value2 --test-argument3 = value3 --test-argument4=value4');
        $this->assertTrue($request->hasArgument('testArgument'), 'The given "testArgument" was not found in the built request.');
        $this->assertTrue($request->hasArgument('testArgument2'), 'The given "testArgument2" was not found in the built request.');
        $this->assertTrue($request->hasArgument('testArgument3'), 'The given "testArgument3" was not found in the built request.');
        $this->assertTrue($request->hasArgument('testArgument4'), 'The given "testArgument4" was not found in the built request.');
        $this->assertSame($request->getArgument('testArgument'), 'value', 'The "testArgument" had not the given value.');
        $this->assertSame($request->getArgument('testArgument2'), 'value2', 'The "testArgument2" had not the given value.');
        $this->assertSame($request->getArgument('testArgument3'), 'value3', 'The "testArgument3" had not the given value.');
        $this->assertSame($request->getArgument('testArgument4'), 'value4', 'The "testArgument4" had not the given value.');
    }

    /**
     * Checks if a CLI request specifying some short "console style" (-c value or -c=value or -c = value) arguments results in the expected request object
     *
     * @test
     */
    public function CliAccesWithShortArgumentsBuildsCorrectRequest()
    {
        $methodParameters = array(
            'a' => array('optional' => false, 'type' => 'string'),
            'd' => array('optional' => false, 'type' => 'string'),
            'f' => array('optional' => false, 'type' => 'string'),
        );
        $this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

        $request = $this->requestBuilder->build('acme.test:default:list -d valued -f=valuef -a = valuea');
        $this->assertTrue($request->hasArgument('d'), 'The given "d" was not found in the built request.');
        $this->assertTrue($request->hasArgument('f'), 'The given "f" was not found in the built request.');
        $this->assertTrue($request->hasArgument('a'), 'The given "a" was not found in the built request.');
        $this->assertSame($request->getArgument('d'), 'valued', 'The "d" had not the given value.');
        $this->assertSame($request->getArgument('f'), 'valuef', 'The "f" had not the given value.');
        $this->assertSame($request->getArgument('a'), 'valuea', 'The "a" had not the given value.');
    }

    /**
     * Checks if a CLI request specifying some mixed "console style" (-c or --my-argument -f=value) arguments with and
     * without values results in the expected request object
     *
     * @test
     */
    public function CliAccesWithArgumentsWithAndWithoutValuesBuildsCorrectRequest()
    {
        $methodParameters = array(
            'testArgument' => array('optional' => false, 'type' => 'string'),
            'testArgument2' => array('optional' => false, 'type' => 'string'),
            'testArgument3' => array('optional' => false, 'type' => 'string'),
            'testArgument4' => array('optional' => false, 'type' => 'string'),
            'testArgument5' => array('optional' => false, 'type' => 'string'),
            'testArgument6' => array('optional' => false, 'type' => 'string'),
            'testArgument7' => array('optional' => false, 'type' => 'string'),
            'f' => array('optional' => false, 'type' => 'string'),
            'd' => array('optional' => false, 'type' => 'string'),
            'a' => array('optional' => false, 'type' => 'string'),
            'c' => array('optional' => false, 'type' => 'string'),
            'j' => array('optional' => false, 'type' => 'string'),
            'k' => array('optional' => false, 'type' => 'string'),
            'm' => array('optional' => false, 'type' => 'string'),
        );
        $this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

        $request = $this->requestBuilder->build('acme.test:default:list --test-argument=value --test-argument2= value2 -k --test-argument-3 = value3 --test-argument4=value4 -f valuef -d=valued -a = valuea -c --testArgument7 --test-argument5 = 5 --test-argument6 -j kjk -m');
        $this->assertTrue($request->hasArgument('testArgument'), 'The given "testArgument" was not found in the built request.');
        $this->assertTrue($request->hasArgument('testArgument2'), 'The given "testArgument2" was not found in the built request.');
        $this->assertTrue($request->hasArgument('k'), 'The given "k" was not found in the built request.');
        $this->assertTrue($request->hasArgument('testArgument3'), 'The given "testArgument3" was not found in the built request.');
        $this->assertTrue($request->hasArgument('testArgument4'), 'The given "testArgument4" was not found in the built request.');
        $this->assertTrue($request->hasArgument('f'), 'The given "f" was not found in the built request.');
        $this->assertTrue($request->hasArgument('d'), 'The given "d" was not found in the built request.');
        $this->assertTrue($request->hasArgument('a'), 'The given "a" was not found in the built request.');
        $this->assertTrue($request->hasArgument('c'), 'The given "d" was not found in the built request.');
        $this->assertTrue($request->hasArgument('testArgument7'), 'The given "testArgument7" was not found in the built request.');
        $this->assertTrue($request->hasArgument('testArgument5'), 'The given "testArgument5" was not found in the built request.');
        $this->assertTrue($request->hasArgument('testArgument6'), 'The given "testArgument6" was not found in the built request.');
        $this->assertTrue($request->hasArgument('j'), 'The given "j" was not found in the built request.');
        $this->assertTrue($request->hasArgument('m'), 'The given "m" was not found in the built request.');
        $this->assertSame($request->getArgument('testArgument'), 'value', 'The "testArgument" had not the given value.');
        $this->assertSame($request->getArgument('testArgument2'), 'value2', 'The "testArgument2" had not the given value.');
        $this->assertSame($request->getArgument('testArgument3'), 'value3', 'The "testArgument3" had not the given value.');
        $this->assertSame($request->getArgument('testArgument4'), 'value4', 'The "testArgument4" had not the given value.');
        $this->assertSame($request->getArgument('f'), 'valuef', 'The "f" had not the given value.');
        $this->assertSame($request->getArgument('d'), 'valued', 'The "d" had not the given value.');
        $this->assertSame($request->getArgument('a'), 'valuea', 'The "a" had not the given value.');
        $this->assertSame($request->getArgument('testArgument5'), '5', 'The "testArgument4" had not the given value.');
        $this->assertSame($request->getArgument('j'), 'kjk', 'The "j" had not the given value.');
    }

    /**
     * @test
     */
    public function argumentWithValueSeparatedByEqualSignBuildsCorrectRequest()
    {
        $methodParameters = array(
            'testArgument' => array('optional' => false, 'type' => 'string')
        );
        $this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

        $request = $this->requestBuilder->build('acme.test:default:list --test-argument=value');
        $this->assertTrue($request->hasArgument('testArgument'), 'The given "testArgument" was not found in the built request.');
        $this->assertSame($request->getArgument('testArgument'), 'value', 'The "testArgument" had not the given value.');
    }

    /**
     * @test
     */
    public function insteadOfNamedArgumentsTheArgumentsCanBePassedUnnamedInTheCorrectOrder()
    {
        $methodParameters = array(
            'testArgument1' => array('optional' => false, 'type' => 'string'),
            'testArgument2' => array('optional' => false, 'type' => 'string'),
        );
        $this->mockReflectionService->expects($this->exactly(2))->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

        $request = $this->requestBuilder->build('acme.test:default:list --test-argument1 firstArgumentValue --test-argument2 secondArgumentValue');
        $this->assertSame('firstArgumentValue', $request->getArgument('testArgument1'));
        $this->assertSame('secondArgumentValue', $request->getArgument('testArgument2'));

        $request = $this->requestBuilder->build('acme.test:default:list firstArgumentValue secondArgumentValue');
        $this->assertSame('firstArgumentValue', $request->getArgument('testArgument1'));
        $this->assertSame('secondArgumentValue', $request->getArgument('testArgument2'));
    }

    /**
     * @test
     */
    public function argumentsAreDetectedAfterOptions()
    {
        $methodParameters = array(
            'some' => array('optional' => true, 'type' => 'boolean'),
            'option' => array('optional' => true, 'type' => 'string'),
            'argument1' => array('optional' => false, 'type' => 'string'),
            'argument2' => array('optional' => false, 'type' => 'string'),
        );
        $this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

        $request = $this->requestBuilder->build('acme.test:default:list --some -option=value file1 file2');
        $this->assertSame('list', $request->getControllerCommandName());
        $this->assertTrue($request->getArgument('some'));
        $this->assertSame('file1', $request->getArgument('argument1'));
        $this->assertSame('file2', $request->getArgument('argument2'));
    }

    /**
     * @test
     */
    public function exceedingArgumentsMayBeSpecified()
    {
        $methodParameters = array(
            'testArgument1' => array('optional' => false, 'type' => 'string'),
            'testArgument2' => array('optional' => false, 'type' => 'string'),
        );
        $this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

        $expectedArguments = array('testArgument1' => 'firstArgumentValue', 'testArgument2' => 'secondArgumentValue');

        $request = $this->requestBuilder->build('acme.test:default:list --test-argument1=firstArgumentValue --test-argument2 secondArgumentValue exceedingArgument1');
        $this->assertSame($expectedArguments, $request->getArguments());
        $this->assertSame(array('exceedingArgument1'), $request->getExceedingArguments());
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Mvc\Exception\InvalidArgumentMixingException
     */
    public function ifNamedArgumentsAreUsedAllRequiredArgumentsMustBeNamed()
    {
        $methodParameters = array(
            'testArgument1' => array('optional' => false, 'type' => 'string'),
            'testArgument2' => array('optional' => false, 'type' => 'string'),
        );
        $this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

        $this->requestBuilder->build('acme.test:default:list --test-argument1 firstArgumentValue secondArgumentValue');
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Mvc\Exception\InvalidArgumentMixingException
     */
    public function ifUnnamedArgumentsAreUsedAllRequiredArgumentsMustBeUnnamed()
    {
        $methodParameters = array(
            'requiredArgument1' => array('optional' => false, 'type' => 'string'),
            'requiredArgument2' => array('optional' => false, 'type' => 'string'),
        );
        $this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

        $this->requestBuilder->build('acme.test:default:list firstArgumentValue --required-argument2 secondArgumentValue');
    }

    /**
     * @test
     */
    public function booleanOptionsAreConsideredEvenIfAnUnnamedArgumentFollows()
    {
        $methodParameters = array(
            'requiredArgument1' => array('optional' => false, 'type' => 'string'),
            'requiredArgument2' => array('optional' => false, 'type' => 'string'),
            'booleanOption' => array('optional' => true, 'type' => 'boolean'),
        );
        $this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

        $expectedArguments = array('requiredArgument1' => 'firstArgumentValue', 'requiredArgument2' => 'secondArgumentValue', 'booleanOption' => true);

        $request = $this->requestBuilder->build('acme.test:default:list --booleanOption firstArgumentValue secondArgumentValue');
        $this->assertEquals($expectedArguments, $request->getArguments());
    }

    /**
     * @test
     */
    public function optionsAreNotMappedToCommandArgumentsIfTheyAreUnnamed()
    {
        $methodParameters = array(
            'requiredArgument1' => array('optional' => false, 'type' => 'string'),
            'requiredArgument2' => array('optional' => false, 'type' => 'string'),
            'booleanOption' => array('optional' => true, 'type' => 'boolean'),
        );
        $this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

        $expectedArguments = array('requiredArgument1' => 'firstArgumentValue', 'requiredArgument2' => 'secondArgumentValue');

        $request = $this->requestBuilder->build('acme.test:default:list firstArgumentValue secondArgumentValue true');
        $this->assertSame($expectedArguments, $request->getArguments());
    }

    /**
     * @test
     */
    public function afterAllRequiredArgumentsUnnamedParametersAreStoredAsExceedingArguments()
    {
        $methodParameters = array(
            'requiredArgument1' => array('optional' => false, 'type' => 'string'),
            'requiredArgument2' => array('optional' => false, 'type' => 'string'),
            'booleanOption' => array('optional' => true, 'type' => 'boolean'),
        );
        $this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

        $expectedExceedingArguments = array('true');

        $request = $this->requestBuilder->build('acme.test:default:list firstArgumentValue secondArgumentValue true');
        $this->assertSame($expectedExceedingArguments, $request->getExceedingArguments());
    }

    /**
     * @test
     */
    public function booleanOptionsCanHaveOnlyCertainValuesIfTheValueIsAssignedWithoutEqualSign()
    {
        $methodParameters = array(
            'b1' => array('optional' => true, 'type' => 'boolean'),
            'b2' => array('optional' => true, 'type' => 'boolean'),
            'b3' => array('optional' => true, 'type' => 'boolean'),
            'b4' => array('optional' => true, 'type' => 'boolean'),
            'b5' => array('optional' => true, 'type' => 'boolean'),
            'b6' => array('optional' => true, 'type' => 'boolean'),
        );
        $this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

        $expectedArguments = array('b1' => true, 'b2' => true, 'b3' => true, 'b4' => false, 'b5' => false, 'b6' => false);

        $request = $this->requestBuilder->build('acme.test:default:list --b2 y --b1 1 --b3 true --b4 false --b5 n --b6 0');
        $this->assertEquals($expectedArguments, $request->getArguments());
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function quotedValues()
    {
        return array(
            array("'value with spaces'", 'value with spaces'),
            array("'value with spaces and \\' escaped'", 'value with spaces and \' escaped'),
            array('"value with spaces"', 'value with spaces'),
            array('"value with spaces and \\" escaped"', 'value with spaces and " escaped'),
            array('value\\ with\\ spaces', 'value with spaces'),
            array('no\\"spaces\\\'here', 'no"spaces\'here'),
            array("nospaces\\'here", "nospaces'here"),
            array('no\\"spaceshere', 'no"spaceshere'),
            array('no\\\\spaceshere', 'no\\spaceshere'),
            array("''", '')
        );
    }

    /**
     * @test
     * @dataProvider quotedValues
     */
    public function quotedArgumentValuesAreCorrectlyParsedWhenPassingTheCommandAsString($quotedArgument, $expectedResult)
    {
        $methodParameters = array(
            'requiredArgument1' => array('optional' => false, 'type' => 'string'),
            'requiredArgument2' => array('optional' => false, 'type' => 'string'),
        );
        $this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

        $expectedArguments = array('requiredArgument1' => 'firstArgumentValue', 'requiredArgument2' => $expectedResult);

        $request = $this->requestBuilder->build('acme.test:default:list firstArgumentValue ' . $quotedArgument);
        $this->assertEquals($expectedArguments, $request->getArguments());
    }
}
