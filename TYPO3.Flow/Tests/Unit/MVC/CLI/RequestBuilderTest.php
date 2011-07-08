<?php
namespace TYPO3\FLOW3\Tests\Unit\MVC\CLI;

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
class RequestBuilderTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\MVC\CLI\RequestBuilder
	 */
	protected $requestBuilder;

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var \TYPO\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * Sets up this test case
	 *
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Test'));
		mkdir('vfs://Test/Packages/Application/Acme/Test', 0770, TRUE);
		mkdir('vfs://Test/Packages/Application/Bcme/Test', 0770, TRUE);
		mkdir('vfs://Test/Packages/Application/Acme/NoTest', 0770, TRUE);

		$this->mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$this->mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->with('acme\test\command\defaultcommandcontroller')->will($this->returnValue('Acme\Test\Command\DefaultCommandController'));

		$this->mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService');

		$this->requestBuilder = new \TYPO3\FLOW3\MVC\CLI\RequestBuilder();
		$this->requestBuilder->injectObjectManager($this->mockObjectManager);
		$this->requestBuilder->injectReflectionService($this->mockReflectionService);
	}

	/**
	 * Checks if a CLI request specifying a package, controller and action name results in the expected request object
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cliAccessWithPackageControllerAndActionNameBuildsCorrectRequest() {
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->will($this->returnValue(array()));

		$request = $this->requestBuilder->build('acme.test:default:list');
		$this->assertEquals('Acme\Test\Command\DefaultCommandController', $request->getControllerObjectName());
		$this->assertEquals('list', $request->getControllerCommandName(), 'The CLI request specifying a package, controller and action name did not return a request object pointing to the expected action.');
	}

	/**
	 * Checks the support of short-hand package keys, ie. specifying just "flow3" instead of "typo3.flow3".
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function ifThePackageNamespaceIsUnambiguousTheLastPartOfTheNamespaceSuffices() {
		$packages = array(
			'Acme.Test' => new \TYPO3\FLOW3\Package\Package('Acme.Test', 'vfs://Test/Packages/Application/Acme/Test/'),
			'Acme.NoTest' => new \TYPO3\FLOW3\Package\Package('Acme.NoTest', 'vfs://Test/Packages/Application/Acme/NoTest/'),
		);

		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->will($this->returnValue(array()));

		$mockPackageManager = $this->getMock('TYPO3\FLOW3\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->any())->method('getActivePackages')->will($this->returnValue($packages));
		$this->requestBuilder->injectPackageManager($mockPackageManager);

		$request = $this->requestBuilder->build('test:default:list');
		$this->assertEquals('Acme\Test\Command\DefaultCommandController', $request->getControllerObjectName());
	}

	/**
	 * Checks the support of short-hand package keys, ie. specifying just "flow3" instead of "typo3.flow3".
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function ifThePackageNamespaceIsAmbiguousTheHelpScreenIsShown() {
			// The following call is only made to satisfy PHPUnit. For some weird reason PHPUnit complains that the
			// mocked method ("getCaseSensitiveObjectName") does not exist _if the mock object is not used_.
		$this->mockObjectManager->getCaseSensitiveObjectName('acme\test\command\defaultcommandcontroller');

		$packages = array(
			'Acme.Test' => new \TYPO3\FLOW3\Package\Package('Acme.Test', 'vfs://Test/Packages/Application/Acme/Test/'),
			'Bcme.Test' => new \TYPO3\FLOW3\Package\Package('Bcme.Test', 'vfs://Test/Packages/Application/Bcme/Test/')
		);

		$mockPackageManager = $this->getMock('TYPO3\FLOW3\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->any())->method('getActivePackages')->will($this->returnValue($packages));
		$this->requestBuilder->injectPackageManager($mockPackageManager);

		$request = $this->requestBuilder->build('test:default:list');
		$this->assertEquals('TYPO3\FLOW3\Command\HelpCommandController', $request->getControllerObjectName());
	}

	/**
	 * Checks the support of very short-hand command identifiers, only specifying controller name and command name.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function ifJustControllerAndCommandNameIsUnambiguousThatShortIdentifierSuffices() {
		$this->markTestIncomplete();

		$classNames = array(
			md5(uniqid(mt_rand(), TRUE)) . 'Acme\Test\Command\FooCommandController',
			md5(uniqid(mt_rand(), TRUE)) . 'Acme\Test\Command\BarCommandController',
			md5(uniqid(mt_rand(), TRUE)) . 'Dooo\Test\Command\BarCommandController'
		);

		$this->mockReflectionService->expects($this->once())->method('getAllSubClassNamesForClass')->will($this->returnValue($classNames));

		$request = $this->requestBuilder->build('foocommand:list');
		$this->assertEquals($classNames[0], $request->getControllerObjectName());
		$this->assertEquals('list', $request->getControllerCommandName());
	}

	/**
	 * Checks if a CLI request specifying some "console style" (--my-argument=value) arguments results in the expected request object
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function cliAccesWithPackageControllerActionAndArgumentsBuildsCorrectRequest() {
		$methodParameters = array(
			'testArgument' => array('optional' => FALSE),
			'testArgument2' => array('optional' => FALSE)
		);
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

		$request = $this->requestBuilder->build('acme.test:default:list --test-argument=value --test-argument2=value2');
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
		$methodParameters = array(
			'testArgument' => array('optional' => FALSE),
			'testArgument2' => array('optional' => FALSE),
			'testArgument3' => array('optional' => FALSE),
			'testArgument4' => array('optional' => FALSE)
		);
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

		$request = $this->requestBuilder->build('acme.test:default:list --test-argument= value --test-argument2 =value2 --test-argument3 = value3 --test-argument4=value4');
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
		$methodParameters = array(
			'a' => array('optional' => FALSE),
			'd' => array('optional' => FALSE),
			'f' => array('optional' => FALSE),
		);
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

		$request = $this->requestBuilder->build('acme.test:default:list -d valued -f=valuef -a = valuea');
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
		$methodParameters = array(
			'testArgument' => array('optional' => FALSE),
			'testArgument2' => array('optional' => FALSE),
			'testArgument3' => array('optional' => FALSE),
			'testArgument4' => array('optional' => FALSE),
			'testArgument5' => array('optional' => FALSE),
			'testArgument6' => array('optional' => FALSE),
			'testArgument7' => array('optional' => FALSE),
			'f' => array('optional' => FALSE),
			'd' => array('optional' => FALSE),
			'a' => array('optional' => FALSE),
			'c' => array('optional' => FALSE),
			'j' => array('optional' => FALSE),
			'k' => array('optional' => FALSE),
			'm' => array('optional' => FALSE),
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function insteadOfNamedArgumentsTheArgumentsCanBePassedUnnamedInTheCorrectOrder() {
		$methodParameters = array(
			'testArgument1' => array('optional' => FALSE),
			'testArgument2' => array('optional' => FALSE),
		);
		$this->mockReflectionService->expects($this->exactly(2))->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

		$request = $this->requestBuilder->build('acme.test:default:list --test-argument1 firstArgumentValue --test-argument2 secondArgumentValue');
		$this->assertEquals('firstArgumentValue', $request->getArgument('testArgument1'));
		$this->assertEquals('secondArgumentValue', $request->getArgument('testArgument2'));

		$request = $this->requestBuilder->build('acme.test:default:list firstArgumentValue secondArgumentValue');
		$this->assertEquals('firstArgumentValue', $request->getArgument('testArgument1'));
		$this->assertEquals('secondArgumentValue', $request->getArgument('testArgument2'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function argumentsAreDetectedAfterOptions() {
		$methodParameters = array(
			'some' => array('optional' => TRUE),
			'option' => array('optional' => TRUE),
			'argument1' => array('optional' => FALSE),
			'argument2' => array('optional' => FALSE),
		);
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

		$request = $this->requestBuilder->build('acme.test:default:list --some -option=value file1 file2');
		$this->assertEquals('list', $request->getControllerCommandName());
		$this->assertTrue($request->getArgument('some'));
		$this->assertEquals('file1', $request->getArgument('argument1'));
		$this->assertEquals('file2', $request->getArgument('argument2'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function exceedingArgumentsMayBeSpecified() {
		$methodParameters = array(
			'testArgument1' => array('optional' => FALSE),
			'testArgument2' => array('optional' => FALSE),
		);
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

		$expectedArguments = array('testArgument1' => 'firstArgumentValue', 'testArgument2' => 'secondArgumentValue', 0 => 'exceedingArgument1');

		$request = $this->requestBuilder->build('acme.test:default:list --test-argument1=firstArgumentValue --test-argument2 secondArgumentValue exceedingArgument1');
		$this->assertEquals($expectedArguments, $request->getArguments());
		$this->assertEquals(array('exceedingArgument1'), $request->getExceedingArguments());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\MVC\Exception\InvalidArgumentMixingException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function ifNamedArgumentsAreUsedAllRequiredArgumentsMustBeNamed() {
		$methodParameters = array(
			'testArgument1' => array('optional' => FALSE),
			'testArgument2' => array('optional' => FALSE),
		);
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

		$this->requestBuilder->build('acme.test:default:list --test-argument1 firstArgumentValue secondArgumentValue');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\MVC\Exception\InvalidArgumentMixingException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function ifUnnamedArgumentsAreUsedAllRequiredArgumentsMustBeUnnamed() {
		$methodParameters = array(
			'requiredArgument1' => array('optional' => FALSE),
			'requiredArgument2' => array('optional' => FALSE),
		);
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->will($this->returnValue($methodParameters));

		$this->requestBuilder->build('acme.test:default:list firstArgumentValue --required-argument2 secondArgumentValue');
	}

}

?>