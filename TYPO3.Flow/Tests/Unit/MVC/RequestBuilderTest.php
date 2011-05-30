<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\MVC;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the MVC CLI Request Builder
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestBuilderTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \F3\FLOW3\MVC\CLI\RequestBuilder
	 */
	protected $requestBuilder;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * Sets up this test case
	 *
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$this->mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->with('F3\test\Command\defaultCommandController')->will($this->returnValue('F3\Test\Command\DefaultCommandController'));

		$this->requestBuilder = new \F3\FLOW3\MVC\CLI\RequestBuilder();
		$this->requestBuilder->injectObjectManager($this->mockObjectManager);
	}

	/**
	 * Checks if a CLI request specifying a package, controller and action name results in the expected request object
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function checkIfCLIAccessWithPackageControllerAndActionNameBuildsCorrectRequest() {
		$request = $this->requestBuilder->build('test:default:list');
		$this->assertEquals('F3\Test\Command\DefaultCommandController', $request->getControllerObjectName());
		$this->assertEquals('list', $request->getControllerCommandName(), 'The CLI request specifying a package, controller and action name did not return a request object pointing to the expected action.');
	}

	/**
	 * Checks if a CLI request specifying some "console style" (--my-argument=value) arguments results in the expected request object
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function CLIAccesWithPackageControllerActionAndArgumentsBuildsCorrectRequest() {
		$request = $this->requestBuilder->build('test:default:list --test-argument=value --test-argument2=value2');
		$this->assertTrue($request->hasArgument('testArgument'), 'The given "testArgument" was not found in the built request.');
		$this->assertTrue($request->hasArgument('testArgument2'), 'The given "testArgument2" was not found in the built request.');
		$this->assertEquals($request->getArgument('testArgument'), 'value', 'The "testArgument" had not the given value.');
		$this->assertEquals($request->getArgument('testArgument2'), 'value2', 'The "testArgument2" had not the given value.');
	}

	/**
	 * Checks if a CLI request specifying some "console style" (--my-argument =value) arguments with spaces between name and value results in the expected request object
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function checkIfCLIAccesWithPackageControllerActionAndArgumentsToleratesSpaces() {
		$request = $this->requestBuilder->build('test:default:list --test-argument= value --test-argument2 =value2 --test-argument3 = value3 --test-argument4=value4');
		$this->assertTrue($request->hasArgument('testArgument'), 'The given "testArgument" was not found in the built request.');
		$this->assertTrue($request->hasArgument('testArgument2'), 'The given "testArgument2" was not found in the built request.');
		$this->assertTrue($request->hasArgument('testArgument3'), 'The given "testArgument3" was not found in the built request.');
		$this->assertTrue($request->hasArgument('testArgument4'), 'The given "testArgument4" was not found in the built request.');
		$this->assertEquals($request->getArgument('testArgument'), 'value', 'The "testArgument" had not the given value.');
		$this->assertEquals($request->getArgument('testArgument2'), 'value2', 'The "testArgument2" had not the given value.');
		$this->assertEquals($request->getArgument('testArgument3'), 'value3', 'The "testArgument3" had not the given value.');
		$this->assertEquals($request->getArgument('testArgument4'), 'value4', 'The "testArgument4" had not the given value.');
	}

	/**
	 * Checks if a CLI request specifying some short "console style" (-c value or -c=value or -c = value) arguments results in the expected request object
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function CLIAccesWithShortArgumentsBuildsCorrectRequest() {
		$request = $this->requestBuilder->build('test:default:list -d valued -f=valuef -a = valuea');
		$this->assertTrue($request->hasArgument('d'), 'The given "d" was not found in the built request.');
		$this->assertTrue($request->hasArgument('f'), 'The given "f" was not found in the built request.');
		$this->assertTrue($request->hasArgument('a'), 'The given "a" was not found in the built request.');
		$this->assertEquals($request->getArgument('d'), 'valued', 'The "d" had not the given value.');
		$this->assertEquals($request->getArgument('f'), 'valuef', 'The "f" had not the given value.');
		$this->assertEquals($request->getArgument('a'), 'valuea', 'The "a" had not the given value.');
	}

	/**
	 * Checks if a CLI request specifying some mixed "console style" (-c or --my-argument -f=value) arguments with and
	 * without values results in the expected request object
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function CLIAccesWithArgumentsWithAndWithoutValuesBuildsCorrectRequest() {
		$request = $this->requestBuilder->build('test:default:list --test-argument=value --test-argument2= value2 -k --test-argument-3 = value3 --test-argument4=value4 -f valuef -d=valued -a = valuea -c --testArgument7 --test-argument5 = 5 --test-argument6 -j kjk -m');
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
		$this->assertEquals($request->getArgument('testArgument'), 'value', 'The "testArgument" had not the given value.');
		$this->assertEquals($request->getArgument('testArgument2'), 'value2', 'The "testArgument2" had not the given value.');
		$this->assertEquals($request->getArgument('testArgument3'), 'value3', 'The "testArgument3" had not the given value.');
		$this->assertEquals($request->getArgument('testArgument4'), 'value4', 'The "testArgument4" had not the given value.');
		$this->assertEquals($request->getArgument('f'), 'valuef', 'The "f" had not the given value.');
		$this->assertEquals($request->getArgument('d'), 'valued', 'The "d" had not the given value.');
		$this->assertEquals($request->getArgument('a'), 'valuea', 'The "a" had not the given value.');
		$this->assertEquals($request->getArgument('testArgument5'), '5', 'The "testArgument4" had not the given value.');
		$this->assertEquals($request->getArgument('j'), 'kjk', 'The "j" had not the given value.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function argumentsAreDetectedAfterOptions() {
		$this->environment->SERVER['argc'] = 6;
		$this->environment->SERVER['argv'][0] = 'index.php';
		$this->environment->SERVER['argv'][1] = 'TestPackage';
		$this->environment->SERVER['argv'][2] = '--some';
		$this->environment->SERVER['argv'][3] = '-option=value';
		$this->environment->SERVER['argv'][4] = 'file1';
		$this->environment->SERVER['argv'][5] = 'file2';

		$request = $this->requestBuilder->build('test:default:list --some -option=value file1 file2');
		$this->assertEquals('F3\Test\Command\DefaultCommandController', $request->getControllerObjectName());
		$this->assertEquals('list', $request->getControllerCommandName());
		$this->assertEquals(array('file1', 'file2'), $request->getCommandLineArguments());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function argumentsAreDetectedIfNoOptionsAreGiven() {
		$request = $this->requestBuilder->build('test:default:list -- file1 file2');
		$this->assertEquals('F3\Test\Command\DefaultCommandController', $request->getControllerObjectName());
		$this->assertEquals('list', $request->getControllerCommandName());
		$this->assertEquals(array('file1', 'file2'), $request->getCommandLineArguments());
	}

}

?>