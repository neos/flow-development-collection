<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\MVC\CLI;

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
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $mockEnvironment;

	/**
	 * @var ArrayObject
	 */
	protected $SERVER;

	/**
	 * Sets up this test case
	 *
	 * @author  Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$this->mockRequest = $this->getMock('F3\FLOW3\MVC\CLI\Request');

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('create')->with('F3\FLOW3\MVC\CLI\Request')->will($this->returnValue($this->mockRequest));

		$this->SERVER = new \ArrayObject();

		$this->mockEnvironment = $this->getAccessibleMock('F3\FLOW3\Utility\Environment', array('dummy'), array(), '', FALSE);
		$this->mockEnvironment->_set('SERVER', $this->SERVER);

		$this->SERVER['argc'] = 0;
		$this->SERVER['argv'] = array();

		$this->requestBuilder = new \F3\FLOW3\MVC\CLI\RequestBuilder($mockObjectManager, $this->mockEnvironment);
	}

	/**
	 * Checks if a CLI request without any arguments results in the expected request object
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function simpleCLIAccessBuildsCorrectRequest() {
		$this->mockRequest->expects($this->once())->method('setControllerPackageKey')->with('FLOW3');
		$this->mockRequest->expects($this->once())->method('setControllerSubpackageKey')->with('MVC');
		$this->mockRequest->expects($this->once())->method('setControllerName')->with('Standard');

		$this->SERVER['argc'] = 1;
		$this->SERVER['argv'][0] = 'index.php';

		$this->requestBuilder->build();
	}

	/**
	 * Checks if a CLI request with a package name argument results in the expected request object
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function CLIAccessWithPackageNameBuildsCorrectRequest() {
		$this->mockRequest->expects($this->once())->method('setControllerPackageKey')->with('TestPackage');

		$this->SERVER['argc'] = 2;
		$this->SERVER['argv'][0] = 'index.php';
		$this->SERVER['argv'][1] = 'TestPackage';

		$this->requestBuilder->build();
	}

	/**
	 * Checks if a CLI request specifying a package and a controller name results in the expected exception
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException \F3\FLOW3\MVC\Exception\InvalidFormatException
	 */
	public function CLIAccessWithPackageAndControllerNameThrowsInvalidFormatException() {
		$this->SERVER['argc'] = 3;
		$this->SERVER['argv'][0] = 'index.php';
		$this->SERVER['argv'][1] = 'TestPackage';
		$this->SERVER['argv'][2] = 'Standard';

		$request = $this->requestBuilder->build();
	}

	/**
	 * Checks if a CLI request specifying a package, controller and action name results in the expected request object
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function checkIfCLIAccessWithPackageControllerAndActionNameBuildsCorrectRequest() {
		$this->mockRequest->expects($this->once())->method('setControllerPackageKey')->with('TestPackage');
		$this->mockRequest->expects($this->once())->method('setControllerName')->with('Standard');
		$this->mockRequest->expects($this->once())->method('setControllerActionName')->with('list');

		$this->SERVER['argc'] = 4;
		$this->SERVER['argv'][0] = 'index.php';
		$this->SERVER['argv'][1] = 'TestPackage';
		$this->SERVER['argv'][2] = 'Standard';
		$this->SERVER['argv'][3] = 'list';

		$request = $this->requestBuilder->build();
	}

	/**
	 * Checks if a CLI request specifying some "console style" (--my-argument=value) arguments results in the expected request object
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function CLIAccessWithPackageControllerActionAndArgumentsBuildsCorrectRequest() {
		$this->mockRequest->expects($this->once())->method('setControllerPackageKey')->with('TestPackage');
		$this->mockRequest->expects($this->once())->method('setControllerName')->with('Standard');
		$this->mockRequest->expects($this->once())->method('setControllerActionName')->with('list');
		$this->mockRequest->expects($this->once())->method('setArguments')->with(array('testArgument' => 'value', 'testArgument2' => 'value2'));

		$this->SERVER['argc'] = 6;
		$this->SERVER['argv'][0] = 'index.php';
		$this->SERVER['argv'][1] = 'TestPackage';
		$this->SERVER['argv'][2] = 'Standard';
		$this->SERVER['argv'][3] = 'list';
		$this->SERVER['argv'][4] = '--test-argument=value';
		$this->SERVER['argv'][5] = '--test-argument2=value2';

		$this->requestBuilder->build();
	}

	/**
	 * Checks if a CLI request specifying some "console style" (--my-argument =value) arguments with spaces between name and value results in the expected request object
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function checkIfCLIAccessWithPackageControllerActionAndArgumentsToleratesSpaces() {
		$this->mockRequest->expects($this->once())->method('setControllerPackageKey')->with('TestPackage');
		$this->mockRequest->expects($this->once())->method('setControllerName')->with('Standard');
		$this->mockRequest->expects($this->once())->method('setControllerActionName')->with('list');
		$this->mockRequest->expects($this->once())->method('setArguments')->with(array('testArgument' => 'value', 'testArgument2' => 'value2', 'testArgument3' => 'value3', 'testArgument4' => 'value4'));

		$this->SERVER['argc'] = 12;
		$this->SERVER['argv'][0] = 'index.php';
		$this->SERVER['argv'][1] = 'TestPackage';
		$this->SERVER['argv'][2] = 'Standard';
		$this->SERVER['argv'][3] = 'list';
		$this->SERVER['argv'][4] = '--test-argument=';
		$this->SERVER['argv'][5] = 'value';
		$this->SERVER['argv'][6] = '--test-argument2';
		$this->SERVER['argv'][7] = '=value2';
		$this->SERVER['argv'][8] = '--test-argument3';
		$this->SERVER['argv'][9] = '=';
		$this->SERVER['argv'][10] = 'value3';
		$this->SERVER['argv'][11] = '--test-argument4=value4';

		$this->requestBuilder->build();
	}

	/**
	 * Checks if a CLI request specifying some short "console style" (-c value or -c=value or -c = value) arguments results in the expected request object
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function CLIAccessWithShortArgumentsBuildsCorrectRequest() {
		$this->mockRequest->expects($this->once())->method('setControllerPackageKey')->with('TestPackage');
		$this->mockRequest->expects($this->once())->method('setControllerName')->with('Standard');
		$this->mockRequest->expects($this->once())->method('setControllerActionName')->with('list');
		$this->mockRequest->expects($this->once())->method('setArguments')->with(array('d' => 'valued', 'f' => 'valuef', 'a' => 'valuea'));

		$this->SERVER['argc'] = 10;
		$this->SERVER['argv'][0] = 'index.php';
		$this->SERVER['argv'][1] = 'TestPackage';
		$this->SERVER['argv'][2] = 'Standard';
		$this->SERVER['argv'][3] = 'list';
		$this->SERVER['argv'][4] = '-d';
		$this->SERVER['argv'][5] = 'valued';
		$this->SERVER['argv'][6] = '-f=valuef';
		$this->SERVER['argv'][7] = '-a';
		$this->SERVER['argv'][8] = '=';
		$this->SERVER['argv'][9] = 'valuea';

		$this->requestBuilder->build();
	}

	/**
	 * Checks if a CLI request specifying some mixed "console style" (-c or --my-argument -f=value) arguments with and without values results in the expected request object
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function CLIAccessWithArgumentsWithAndWithoutValuesBuildsCorrectRequest() {
		$this->mockRequest->expects($this->once())->method('setControllerPackageKey')->with('TestPackage');
		$this->mockRequest->expects($this->once())->method('setControllerName')->with('Standard');
		$this->mockRequest->expects($this->once())->method('setControllerActionName')->with('list');
		$this->mockRequest->expects($this->once())->method('setArguments')->with(array(
			'testArgument' => 'value',
			'testArgument2' => 'value2',
			'k' => NULL,
			'testArgument3' => 'value3',
			'testArgument4' => 'value4',
			'f' => 'valuef',
			'd' => 'valued',
			'a' => 'valuea',
			'c' => NULL,
			'testArgument7' => NULL,
			'testArgument5' => 5,
			'testArgument6' => NULL,
			'j' => 'kjk',
			'm' => NULL
		));

		$this->SERVER['argc'] = 27;
		$this->SERVER['argv'][0] = 'index.php';
		$this->SERVER['argv'][1] = 'TestPackage';
		$this->SERVER['argv'][2] = 'Standard';
		$this->SERVER['argv'][3] = 'list';
		$this->SERVER['argv'][4] = '--test-argument=value';
		$this->SERVER['argv'][5] = '--test-argument2=';
		$this->SERVER['argv'][6] = 'value2';
		$this->SERVER['argv'][7] = '-k';
		$this->SERVER['argv'][8] = '--test-argument-3';
		$this->SERVER['argv'][9] = '=';
		$this->SERVER['argv'][10] = 'value3';
		$this->SERVER['argv'][11] = '--test-argument4=value4';
		$this->SERVER['argv'][12] = '-f';
		$this->SERVER['argv'][13] = 'valuef';
		$this->SERVER['argv'][14] = '-d=valued';
		$this->SERVER['argv'][15] = '-a';
		$this->SERVER['argv'][16] = '=';
		$this->SERVER['argv'][17] = 'valuea';
		$this->SERVER['argv'][18] = '-c';
		$this->SERVER['argv'][19] = '--testArgument7';
		$this->SERVER['argv'][20] = '--test-argument5';
		$this->SERVER['argv'][21] = '=';
		$this->SERVER['argv'][22] = '5';
		$this->SERVER['argv'][23] = '--test-argument6';
		$this->SERVER['argv'][24] = '-j';
		$this->SERVER['argv'][25] = 'kjk';
		$this->SERVER['argv'][26] = '-m';

		$this->requestBuilder->build();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function CLIAccessWithSubpackageBuildsCorrectRequest() {
		$this->mockRequest->expects($this->once())->method('setControllerSubpackageKey')->with('Sub\Package');
		$this->mockRequest->expects($this->once())->method('setControllerPackageKey')->with('TestPackage');
		$this->mockRequest->expects($this->once())->method('setControllerName')->with('Test');
		$this->mockRequest->expects($this->once())->method('setControllerActionName')->with('run');

		$this->SERVER['argc'] = 6;
		$this->SERVER['argv'][0] = 'index.php';
		$this->SERVER['argv'][1] = 'TestPackage';
		$this->SERVER['argv'][2] = 'Sub';
		$this->SERVER['argv'][3] = 'Package';
		$this->SERVER['argv'][4] = 'Test';
		$this->SERVER['argv'][5] = 'run';

		$this->requestBuilder->build();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function argumentsAreDetectedAfterOptions() {
		$this->mockRequest->expects($this->once())->method('setControllerPackageKey')->with('TestPackage');
		$this->mockRequest->expects($this->once())->method('setCommandLineArguments')->with(array('file1', 'file2'));

		$this->SERVER['argc'] = 6;
		$this->SERVER['argv'][0] = 'index.php';
		$this->SERVER['argv'][1] = 'TestPackage';
		$this->SERVER['argv'][2] = '--some';
		$this->SERVER['argv'][3] = '-option=value';
		$this->SERVER['argv'][4] = 'file1';
		$this->SERVER['argv'][5] = 'file2';

		$this->requestBuilder->build();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function argumentsAreDetectedIfNoOptionsAreGivenWithFullCommand() {
		$this->mockRequest->expects($this->once())->method('setControllerPackageKey')->with('TestPackage');
		$this->mockRequest->expects($this->once())->method('setCommandLineArguments')->with(array('file1', 'file2'));

		$this->SERVER['argc'] = 7;
		$this->SERVER['argv'][0] = 'index.php';
		$this->SERVER['argv'][1] = 'TestPackage';
		$this->SERVER['argv'][2] = 'Standard';
		$this->SERVER['argv'][3] = 'index';
		$this->SERVER['argv'][4] = '--';
		$this->SERVER['argv'][5] = 'file1';
		$this->SERVER['argv'][6] = 'file2';

		$this->requestBuilder->build();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function argumentsAreDetectedIfNoOptionsAreGiven() {
		$this->mockRequest->expects($this->once())->method('setControllerPackageKey')->with('TestPackage');
		$this->mockRequest->expects($this->once())->method('setCommandLineArguments')->with(array('file1', 'file2'));

		$this->SERVER['argc'] = 6;
		$this->SERVER['argv'][0] = 'index.php';
		$this->SERVER['argv'][1] = 'TestPackage';
		$this->SERVER['argv'][3] = '--';
		$this->SERVER['argv'][4] = 'file1';
		$this->SERVER['argv'][5] = 'file2';

		$this->requestBuilder->build();
	}

}
?>