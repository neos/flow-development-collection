<?php
namespace TYPO3\Flow\Tests\Unit\Cli;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the MVC CLI Request Builder
 *
 */
class RequestBuilderTest extends \TYPO3\Flow\Tests\UnitTestCase {

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
	public function setUp() {
		$this->mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$this->mockObjectManager->expects($this->any())->method('getObjectNameByClassName')->with('Acme\Test\Command\DefaultCommandController')->will($this->returnValue('Acme\Test\Command\DefaultCommandController'));

		$this->mockCommand = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$this->mockCommand->expects($this->any())->method('getControllerClassName')->will($this->returnValue('Acme\Test\Command\DefaultCommandController'));
		$this->mockCommand->expects($this->any())->method('getControllerCommandName')->will($this->returnValue('list'));

		$this->mockCommandManager = $this->getMock('TYPO3\Flow\Cli\CommandManager');
		$this->mockCommandManager->expects($this->any())->method('getCommandByIdentifier')->with('acme.test:default:list')->will($this->returnValue($this->mockCommand));

		$this->mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService');

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
	public function cliAccessWithPackageControllerAndActionNameBuildsCorrectRequest() {
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->will($this->returnValue(array()));

		$request = $this->requestBuilder->build('acme.test:default:list');
		$this->assertSame('Acme\Test\Command\DefaultCommandController', $request->getControllerObjectName());
		$this->assertSame('list', $request->getControllerCommandName(), 'The CLI request specifying a package, controller and action name did not return a request object pointing to the expected action.');
	}

	/**
	 * @test
	 */
	public function ifCommandCantBeResolvedTheHelpScreenIsShown() {
		// The following call is only made to satisfy PHPUnit. For some weird reason PHPUnit complains that the
		// mocked method ("getObjectNameByClassName") does not exist _if the mock object is not used_.
		$this->mockObjectManager->getObjectNameByClassName('Acme\Test\Command\DefaultCommandController');
		$this->mockCommandManager->getCommandByIdentifier('acme.test:default:list');

		$mockCommandManager = $this->getMock('TYPO3\Flow\Cli\CommandManager');
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
	public function cliAccessWithPackageControllerActionAndArgumentsBuildsCorrectRequest() {
		$methodParameters = array(
			'testArgument' => array('optional' => FALSE, 'type' => 'string'),
			'testArgument2' => array('optional' => FALSE, 'type' => 'string')
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
	public function checkIfCliAccesWithPackageControllerActionAndArgumentsToleratesSpaces() {
		$methodParameters = array(
			'testArgument' => array('optional' => FALSE, 'type' => 'string'),
			'testArgument2' => array('optional' => FALSE, 'type' => 'string'),
			'testArgument3' => array('optional' => FALSE, 'type' => 'string'),
			'testArgument4' => array('optional' => FALSE, 'type' => 'string')
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
	public function CliAccesWithShortArgumentsBuildsCorrectRequest() {
		$methodParameters = array(
			'a' => array('optional' => FALSE, 'type' => 'string'),
			'd' => array('optional' => FALSE, 'type' => 'string'),
			'f' => array('optional' => FALSE, 'type' => 'string'),
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
	public function CliAccesWithArgumentsWithAndWithoutValuesBuildsCorrectRequest() {
		$methodParameters = array(
			'testArgument' => array('optional' => FALSE, 'type' => 'string'),
			'testArgument2' => array('optional' => FALSE, 'type' => 'string'),
			'testArgument3' => array('optional' => FALSE, 'type' => 'string'),
			'testArgument4' => array('optional' => FALSE, 'type' => 'string'),
			'testArgument5' => array('optional' => FALSE, 'type' => 'string'),
			'testArgument6' => array('optional' => FALSE, 'type' => 'string'),
			'testArgument7' => array('optional' => FALSE, 'type' => 'string'),
			'f' => array('optional' => FALSE, 'type' => 'string'),
			'd' => array('optional' => FALSE, 'type' => 'string'),
			'a' => array('optional' => FALSE, 'type' => 'string'),
			'c' => array('optional' => FALSE, 'type' => 'string'),
			'j' => array('optional' => FALSE, 'type' => 'string'),
			'k' => array('optional' => FALSE, 'type' => 'string'),
			'm' => array('optional' => FALSE, 'type' => 'string'),
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
	public function argumentWithValueSeparatedByEqualSignBuildsCorrectRequest() {
		$methodParameters = array(
			'testArgument' => array('optional' => FALSE, 'type' => 'string')
		);
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

		$request = $this->requestBuilder->build('acme.test:default:list --test-argument=value');
		$this->assertTrue($request->hasArgument('testArgument'), 'The given "testArgument" was not found in the built request.');
		$this->assertSame($request->getArgument('testArgument'), 'value', 'The "testArgument" had not the given value.');
	}

	/**
	 * @test
	 */
	public function insteadOfNamedArgumentsTheArgumentsCanBePassedUnnamedInTheCorrectOrder() {
		$methodParameters = array(
			'testArgument1' => array('optional' => FALSE, 'type' => 'string'),
			'testArgument2' => array('optional' => FALSE, 'type' => 'string'),
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
	public function argumentsAreDetectedAfterOptions() {
		$methodParameters = array(
			'some' => array('optional' => TRUE, 'type' => 'boolean'),
			'option' => array('optional' => TRUE, 'type' => 'string'),
			'argument1' => array('optional' => FALSE, 'type' => 'string'),
			'argument2' => array('optional' => FALSE, 'type' => 'string'),
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
	public function exceedingArgumentsMayBeSpecified() {
		$methodParameters = array(
			'testArgument1' => array('optional' => FALSE, 'type' => 'string'),
			'testArgument2' => array('optional' => FALSE, 'type' => 'string'),
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
	public function ifNamedArgumentsAreUsedAllRequiredArgumentsMustBeNamed() {
		$methodParameters = array(
			'testArgument1' => array('optional' => FALSE, 'type' => 'string'),
			'testArgument2' => array('optional' => FALSE, 'type' => 'string'),
		);
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

		$this->requestBuilder->build('acme.test:default:list --test-argument1 firstArgumentValue secondArgumentValue');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Mvc\Exception\InvalidArgumentMixingException
	 */
	public function ifUnnamedArgumentsAreUsedAllRequiredArgumentsMustBeUnnamed() {
		$methodParameters = array(
			'requiredArgument1' => array('optional' => FALSE, 'type' => 'string'),
			'requiredArgument2' => array('optional' => FALSE, 'type' => 'string'),
		);
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

		$this->requestBuilder->build('acme.test:default:list firstArgumentValue --required-argument2 secondArgumentValue');
	}

	/**
	 * @test
	 */
	public function booleanOptionsAreConsideredEvenIfAnUnnamedArgumentFollows() {
		$methodParameters = array(
			'requiredArgument1' => array('optional' => FALSE, 'type' => 'string'),
			'requiredArgument2' => array('optional' => FALSE, 'type' => 'string'),
			'booleanOption' => array('optional' => TRUE, 'type' => 'boolean'),
		);
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

		$expectedArguments = array('requiredArgument1' => 'firstArgumentValue', 'requiredArgument2' => 'secondArgumentValue', 'booleanOption' => TRUE);

		$request = $this->requestBuilder->build('acme.test:default:list --booleanOption firstArgumentValue secondArgumentValue');
		$this->assertEquals($expectedArguments, $request->getArguments());
	}

	/**
	 * @test
	 */
	public function optionsAreNotMappedToCommandArgumentsIfTheyAreUnnamed() {
		$methodParameters = array(
			'requiredArgument1' => array('optional' => FALSE, 'type' => 'string'),
			'requiredArgument2' => array('optional' => FALSE, 'type' => 'string'),
			'booleanOption' => array('optional' => TRUE, 'type' => 'boolean'),
		);
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

		$expectedArguments = array('requiredArgument1' => 'firstArgumentValue', 'requiredArgument2' => 'secondArgumentValue');

		$request = $this->requestBuilder->build('acme.test:default:list firstArgumentValue secondArgumentValue true');
		$this->assertSame($expectedArguments, $request->getArguments());
	}

	/**
	 * @test
	 */
	public function afterAllRequiredArgumentsUnnamedParametersAreStoredAsExceedingArguments() {
		$methodParameters = array(
			'requiredArgument1' => array('optional' => FALSE, 'type' => 'string'),
			'requiredArgument2' => array('optional' => FALSE, 'type' => 'string'),
			'booleanOption' => array('optional' => TRUE, 'type' => 'boolean'),
		);
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

		$expectedExceedingArguments = array('true');

		$request = $this->requestBuilder->build('acme.test:default:list firstArgumentValue secondArgumentValue true');
		$this->assertSame($expectedExceedingArguments, $request->getExceedingArguments());
	}

	/**
	 * @test
	 */
	public function booleanOptionsCanHaveOnlyCertainValuesIfTheValueIsAssignedWithoutEqualSign() {
		$methodParameters = array(
			'b1' => array('optional' => TRUE, 'type' => 'boolean'),
			'b2' => array('optional' => TRUE, 'type' => 'boolean'),
			'b3' => array('optional' => TRUE, 'type' => 'boolean'),
			'b4' => array('optional' => TRUE, 'type' => 'boolean'),
			'b5' => array('optional' => TRUE, 'type' => 'boolean'),
			'b6' => array('optional' => TRUE, 'type' => 'boolean'),
		);
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

		$expectedArguments = array('b1' => TRUE, 'b2' => TRUE, 'b3' => TRUE, 'b4' => FALSE, 'b5' => FALSE, 'b6' => FALSE);

		$request = $this->requestBuilder->build('acme.test:default:list --b2 y --b1 1 --b3 true --b4 false --b5 n --b6 0');
		$this->assertEquals($expectedArguments, $request->getArguments());
	}

}
