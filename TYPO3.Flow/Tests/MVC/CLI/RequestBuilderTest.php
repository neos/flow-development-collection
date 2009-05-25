<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\CLI;

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
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */

/**
 * Testcase for the MVC CLI Request Builder
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestBuilderTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\MVC\CLI\RequestBuilder
	 */
	protected $requestBuilder;

	/**
	 * @var \F3\FLOW3\Utility\MockEnvironment
	 */
	protected $environment;

	/**
	 * Sets up this test case
	 *
	 * @author  Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$this->mockRequest = $this->getMock('F3\FLOW3\MVC\CLI\Request');

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\MVC\CLI\Request')->will($this->returnValue($this->mockRequest));

		$this->environment = new \F3\FLOW3\Utility\MockEnvironment();
		$this->environment->SERVER['argc'] = 0;
		$this->environment->SERVER['argv'] = array();

		$this->requestBuilder = new \F3\FLOW3\MVC\CLI\RequestBuilder($mockObjectManager, $mockObjectFactory, $this->environment);
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

		$this->environment->SERVER['argc'] = 1;
		$this->environment->SERVER['argv'][0] = 'index.php';

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

		$this->environment->SERVER['argc'] = 2;
		$this->environment->SERVER['argv'][0] = 'index.php';
		$this->environment->SERVER['argv'][1] = 'TestPackage';

		$this->requestBuilder->build();
	}

	/**
	 * Checks if a CLI request specifying a package and a controller name results in the expected exception
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException \F3\FLOW3\MVC\Exception\InvalidFormat
	 */
	public function CLIAccessWithPackageAndControllerNameThrowsInvalidFormatException() {
		$this->environment->SERVER['argc'] = 3;
		$this->environment->SERVER['argv'][0] = 'index.php';
		$this->environment->SERVER['argv'][1] = 'TestPackage';
		$this->environment->SERVER['argv'][2] = 'Standard';

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

		$this->environment->SERVER['argc'] = 4;
		$this->environment->SERVER['argv'][0] = 'index.php';
		$this->environment->SERVER['argv'][1] = 'TestPackage';
		$this->environment->SERVER['argv'][2] = 'Standard';
		$this->environment->SERVER['argv'][3] = 'list';

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
		$this->mockRequest->expects($this->exactly(2))->method('setArgument');
		$this->mockRequest->expects($this->at(3))->method('setArgument')->with('testArgument', 'value');
		$this->mockRequest->expects($this->at(4))->method('setArgument')->with('testArgument2', 'value2');

		$this->environment->SERVER['argc'] = 6;
		$this->environment->SERVER['argv'][0] = 'index.php';
		$this->environment->SERVER['argv'][1] = 'TestPackage';
		$this->environment->SERVER['argv'][2] = 'Standard';
		$this->environment->SERVER['argv'][3] = 'list';
		$this->environment->SERVER['argv'][4] = '--test-argument=value';
		$this->environment->SERVER['argv'][5] = '--test-argument2=value2';

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
		$this->mockRequest->expects($this->exactly(4))->method('setArgument');
		$this->mockRequest->expects($this->at(3))->method('setArgument')->with('testArgument', 'value');
		$this->mockRequest->expects($this->at(4))->method('setArgument')->with('testArgument2', 'value2');
		$this->mockRequest->expects($this->at(5))->method('setArgument')->with('testArgument3', 'value3');
		$this->mockRequest->expects($this->at(6))->method('setArgument')->with('testArgument4', 'value4');

		$this->environment->SERVER['argc'] = 12;
		$this->environment->SERVER['argv'][0] = 'index.php';
		$this->environment->SERVER['argv'][1] = 'TestPackage';
		$this->environment->SERVER['argv'][2] = 'Standard';
		$this->environment->SERVER['argv'][3] = 'list';
		$this->environment->SERVER['argv'][4] = '--test-argument=';
		$this->environment->SERVER['argv'][5] = 'value';
		$this->environment->SERVER['argv'][6] = '--test-argument2';
		$this->environment->SERVER['argv'][7] = '=value2';
		$this->environment->SERVER['argv'][8] = '--test-argument3';
		$this->environment->SERVER['argv'][9] = '=';
		$this->environment->SERVER['argv'][10] = 'value3';
		$this->environment->SERVER['argv'][11] = '--test-argument4=value4';

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
		$this->mockRequest->expects($this->exactly(3))->method('setArgument');
		$this->mockRequest->expects($this->at(3))->method('setArgument')->with('d', 'valued');
		$this->mockRequest->expects($this->at(4))->method('setArgument')->with('f', 'valuef');
		$this->mockRequest->expects($this->at(5))->method('setArgument')->with('a', 'valuea');

		$this->environment->SERVER['argc'] = 10;
		$this->environment->SERVER['argv'][0] = 'index.php';
		$this->environment->SERVER['argv'][1] = 'TestPackage';
		$this->environment->SERVER['argv'][2] = 'Standard';
		$this->environment->SERVER['argv'][3] = 'list';
		$this->environment->SERVER['argv'][4] = '-d';
		$this->environment->SERVER['argv'][5] = 'valued';
		$this->environment->SERVER['argv'][6] = '-f=valuef';
		$this->environment->SERVER['argv'][7] = '-a';
		$this->environment->SERVER['argv'][8] = '=';
		$this->environment->SERVER['argv'][9] = 'valuea';

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
		$this->mockRequest->expects($this->exactly(14))->method('setArgument');
		$this->mockRequest->expects($this->at(3))->method('setArgument')->with('testArgument', 'value');
		$this->mockRequest->expects($this->at(4))->method('setArgument')->with('testArgument2', 'value2');
		$this->mockRequest->expects($this->at(5))->method('setArgument')->with('k', NULL);
		$this->mockRequest->expects($this->at(6))->method('setArgument')->with('testArgument3', 'value3');
		$this->mockRequest->expects($this->at(7))->method('setArgument')->with('testArgument4', 'value4');
		$this->mockRequest->expects($this->at(8))->method('setArgument')->with('f', 'valuef');
		$this->mockRequest->expects($this->at(9))->method('setArgument')->with('d', 'valued');
		$this->mockRequest->expects($this->at(10))->method('setArgument')->with('a', 'valuea');
		$this->mockRequest->expects($this->at(11))->method('setArgument')->with('c', NULL);
		$this->mockRequest->expects($this->at(12))->method('setArgument')->with('testArgument7', NULL);
		$this->mockRequest->expects($this->at(13))->method('setArgument')->with('testArgument5', 5);
		$this->mockRequest->expects($this->at(14))->method('setArgument')->with('testArgument6', NULL);
		$this->mockRequest->expects($this->at(15))->method('setArgument')->with('j', 'kjk');
		$this->mockRequest->expects($this->at(16))->method('setArgument')->with('m', NULL);

		$this->environment->SERVER['argc'] = 27;
		$this->environment->SERVER['argv'][0] = 'index.php';
		$this->environment->SERVER['argv'][1] = 'TestPackage';
		$this->environment->SERVER['argv'][2] = 'Standard';
		$this->environment->SERVER['argv'][3] = 'list';
		$this->environment->SERVER['argv'][4] = '--test-argument=value';
		$this->environment->SERVER['argv'][5] = '--test-argument2=';
		$this->environment->SERVER['argv'][6] = 'value2';
		$this->environment->SERVER['argv'][7] = '-k';
		$this->environment->SERVER['argv'][8] = '--test-argument-3';
		$this->environment->SERVER['argv'][9] = '=';
		$this->environment->SERVER['argv'][10] = 'value3';
		$this->environment->SERVER['argv'][11] = '--test-argument4=value4';
		$this->environment->SERVER['argv'][12] = '-f';
		$this->environment->SERVER['argv'][13] = 'valuef';
		$this->environment->SERVER['argv'][14] = '-d=valued';
		$this->environment->SERVER['argv'][15] = '-a';
		$this->environment->SERVER['argv'][16] = '=';
		$this->environment->SERVER['argv'][17] = 'valuea';
		$this->environment->SERVER['argv'][18] = '-c';
		$this->environment->SERVER['argv'][19] = '--testArgument7';
		$this->environment->SERVER['argv'][20] = '--test-argument5';
		$this->environment->SERVER['argv'][21] = '=';
		$this->environment->SERVER['argv'][22] = '5';
		$this->environment->SERVER['argv'][23] = '--test-argument6';
		$this->environment->SERVER['argv'][24] = '-j';
		$this->environment->SERVER['argv'][25] = 'kjk';
		$this->environment->SERVER['argv'][26] = '-m';

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

		$this->environment->SERVER['argc'] = 6;
		$this->environment->SERVER['argv'][0] = 'index.php';
		$this->environment->SERVER['argv'][1] = 'TestPackage';
		$this->environment->SERVER['argv'][2] = 'Sub';
		$this->environment->SERVER['argv'][3] = 'Package';
		$this->environment->SERVER['argv'][4] = 'Test';
		$this->environment->SERVER['argv'][5] = 'run';

		$this->requestBuilder->build();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function argumentsAreDetectedAfterOptions() {
		$this->mockRequest->expects($this->once())->method('setControllerPackageKey')->with('TestPackage');
		$this->mockRequest->expects($this->once())->method('setCommandLineArguments')->with(array('file1', 'file2'));

		$this->environment->SERVER['argc'] = 6;
		$this->environment->SERVER['argv'][0] = 'index.php';
		$this->environment->SERVER['argv'][1] = 'TestPackage';
		$this->environment->SERVER['argv'][2] = '--some';
		$this->environment->SERVER['argv'][3] = '-option=value';
		$this->environment->SERVER['argv'][4] = 'file1';
		$this->environment->SERVER['argv'][5] = 'file2';

		$this->requestBuilder->build();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function argumentsAreDetectedIfNoOptionsAreGivenWithFullCommand() {
		$this->mockRequest->expects($this->once())->method('setControllerPackageKey')->with('TestPackage');
		$this->mockRequest->expects($this->once())->method('setCommandLineArguments')->with(array('file1', 'file2'));

		$this->environment->SERVER['argc'] = 7;
		$this->environment->SERVER['argv'][0] = 'index.php';
		$this->environment->SERVER['argv'][1] = 'TestPackage';
		$this->environment->SERVER['argv'][2] = 'Standard';
		$this->environment->SERVER['argv'][3] = 'index';
		$this->environment->SERVER['argv'][4] = '--';
		$this->environment->SERVER['argv'][5] = 'file1';
		$this->environment->SERVER['argv'][6] = 'file2';

		$this->requestBuilder->build();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function argumentsAreDetectedIfNoOptionsAreGiven() {
		$this->mockRequest->expects($this->once())->method('setControllerPackageKey')->with('TestPackage');
		$this->mockRequest->expects($this->once())->method('setCommandLineArguments')->with(array('file1', 'file2'));

		$this->environment->SERVER['argc'] = 6;
		$this->environment->SERVER['argv'][0] = 'index.php';
		$this->environment->SERVER['argv'][1] = 'TestPackage';
		$this->environment->SERVER['argv'][3] = '--';
		$this->environment->SERVER['argv'][4] = 'file1';
		$this->environment->SERVER['argv'][5] = 'file2';

		$this->requestBuilder->build();
	}

}
?>