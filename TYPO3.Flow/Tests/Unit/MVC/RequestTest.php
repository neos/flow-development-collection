<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC;

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
 * Testcase for the MVC Generic Request
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theDefaultPatternForBuildingTheControllerObjectNameIsPackageKeyControllerControllerNameController() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')
			->with($this->equalTo('f3\testpackage\controller\foocontroller'))
			->will($this->returnValue('F3\TestPackage\Controller\FooController'));

		$mockPackageManager = $this->getMock('F3\FLOW3\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->once())->method('getCaseSensitivePackageKey')
			->will($this->returnValue('TestPackage'));

		$request = new \F3\FLOW3\MVC\Request();
		$request->injectObjectManager($mockObjectManager);
		$request->injectPackageManager($mockPackageManager);
		$request->setControllerPackageKey('TestPackage');
		$request->setControllerName('Foo');
		$this->assertEquals('F3\TestPackage\Controller\FooController', $request->getControllerObjectName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function lowerCasePackageKeysAndObjectNamesAreConvertedToTheRealObjectName() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')
			->with($this->equalTo('f3\testpackage\bar\baz\controller\foocontroller'))
			->will($this->returnValue('F3\TestPackage\Bar\Baz\Controller\FooController'));

		$mockPackageManager = $this->getMock('F3\FLOW3\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->once())->method('getCaseSensitivePackageKey')
			->with($this->equalTo('testpackage'))
			->will($this->returnValue('TestPackage'));

		$request = new \F3\FLOW3\MVC\Request();
		$request->injectObjectManager($mockObjectManager);
		$request->injectPackageManager($mockPackageManager);
		$request->setControllerPackageKey('testpackage');
		$request->setControllerName('foo');
		$request->setControllerSubpackageKey('bar\baz');

		$this->assertEquals('F3\TestPackage\Bar\Baz\Controller\FooController', $request->getControllerObjectName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getControllerObjectNameReturnsAnEmptyStringIfTheResolvedControllerDoesNotExist() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')
			->with($this->equalTo('f3\testpackage\controller\foocontroller'))
			->will($this->returnValue(FALSE));

		$mockPackageManager = $this->getMock('F3\FLOW3\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->once())->method('getCaseSensitivePackageKey')
			->with($this->equalTo('testpackage'))
			->will($this->returnValue('TestPackage'));

		$request = new \F3\FLOW3\MVC\Request();
		$request->injectObjectManager($mockObjectManager);
		$request->injectPackageManager($mockPackageManager);
		$request->setControllerPackageKey('testpackage');
		$request->setControllerName('foo');

		$this->assertEquals('', $request->getControllerObjectName());
	}

	/**
	 * @test
	 * @dataProvider caseSensitiveObjectNames
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerObjectNameSplitsTheGivenObjectNameIntoItsParts($objectName, array $parts) {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('getCaseSensitiveObjectName')->will($this->returnValue($objectName));

		$request = $this->getAccessibleMock('F3\FLOW3\MVC\Request', array('dummy'));
		$request->injectObjectManager($mockObjectManager);

		$request->setControllerObjectName($objectName);
		$this->assertSame($parts['controllerPackageKey'], $request->_get('controllerPackageKey'));
		$this->assertSame($parts['controllerSubpackageKey'], $request->_get('controllerSubpackageKey'));
		$this->assertSame($parts['controllerName'], $request->_get('controllerName'));
	}

	/**
	 * 
	 */
	public function caseSensitiveObjectNames() {
		return array(
			array(
				'F3\Foo\Controller\BarController',
				array(
					'controllerPackageKey' => 'Foo',
					'controllerSubpackageKey' => '',
					'controllerName' => 'Bar',
				)
			),
			array(
				'F3\Foo\Bar\Controller\BazController',
				array(
					'controllerPackageKey' => 'Foo',
					'controllerSubpackageKey' => 'Bar',
					'controllerName' => 'Baz',
				)
			),
			array(
				'F3\Foo\Controller\Bar\BazController',
				array(
					'controllerPackageKey' => 'Foo',
					'controllerSubpackageKey' => '',
					'controllerName' => 'Bar\Baz',
				)
			),
			array(
				'F3\Foo\Controller\Bar\Baz\QuuxController',
				array(
					'controllerPackageKey' => 'Foo',
					'controllerSubpackageKey' => '',
					'controllerName' => 'Bar\Baz\Quux',
				)
			)
		);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aSingleArgumentCanBeSetWithSetArgumentAndRetrievedWithGetArgument() {
		$request = new \F3\FLOW3\MVC\Request();
		$request->setArgument('someArgumentName', 'theValue');
		$this->assertEquals('theValue', $request->getArgument('someArgumentName'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function multipleArgumentsCanBeSetWithSetArgumentsAndRetrievedWithGetArguments() {
		$arguments = array(
			'firstArgument' => 'firstValue',
			'dænishÅrgument' => 'görman välju',
			'3a' => '3v'
		);
		$request = new \F3\FLOW3\MVC\Request();
		$request->setArguments($arguments);
		$this->assertEquals($arguments, $request->getArguments());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasArgumentTellsIfAnArgumentExists() {
		$request = new \F3\FLOW3\MVC\Request();
		$request->setArgument('existingArgument', 'theValue');

		$this->assertTrue($request->hasArgument('existingArgument'));
		$this->assertFalse($request->hasArgument('notExistingArgument'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theControllerNameCanBeSetAndRetrieved() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')
			->with($this->equalTo('f3\testpackage\controller\somecontroller'))
			->will($this->returnValue('F3\TestPackage\Controller\SomeController'));

		$mockPackageManager = $this->getMock('F3\FLOW3\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->once())->method('getCaseSensitivePackageKey')
			->will($this->returnValue('TestPackage'));

		$request = new \F3\FLOW3\MVC\Request();
		$request->injectObjectManager($mockObjectManager);
		$request->injectPackageManager($mockPackageManager);
		$request->setControllerPackageKey('TestPackage');
		$request->setControllerName('Some');
		$this->assertEquals('Some', $request->getControllerName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theControllerNameWillBeExtractedFromTheControllerObjectNameToAssureTheCorrectCase() {
		$request = $this->getMock('F3\FLOW3\MVC\Request', array('getControllerObjectName'), array(), '', FALSE);
		$request->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('F3\MyPackage\Controller\Foo\BarController'));

		$request->setControllerName('foo\bar');
		$this->assertEquals('Foo\Bar', $request->getControllerName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function thePackageKeyOfTheControllerCanBeSetAndRetrieved() {
		$mockPackageManager = $this->getMock('F3\FLOW3\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->once())->method('getCaseSensitivePackageKey')
			->will($this->returnValue('TestPackage'));

		$request = new \F3\FLOW3\MVC\Request();
		$request->injectPackageManager($mockPackageManager);
		$request->setControllerPackageKey('TestPackage');
		$this->assertEquals('TestPackage', $request->getControllerPackageKey());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function invalidPackageKeysAreRejected() {
		$mockPackageManager = $this->getMock('F3\FLOW3\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->once())->method('getCaseSensitivePackageKey')
			->will($this->returnValue(FALSE));

		$request = new \F3\FLOW3\MVC\Request();
		$request->injectPackageManager($mockPackageManager);
		$request->setControllerPackageKey('Some_Invalid_Key');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theActionNameCanBeSetAndRetrieved() {
		$request = $this->getMock('F3\FLOW3\MVC\Request', array('getControllerObjectName'), array(), '', FALSE);
		$request->expects($this->once())->method('getControllerObjectName')->will($this->returnValue(''));

		$request->setControllerActionName('theAction');
		$this->assertEquals('theAction', $request->getControllerActionName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theActionNamesCaseIsFixedIfItIsallLowerCaseAndTheControllerObjectNameIsKnown() {
		$mockControllerClassName = uniqid('Mock');
		eval('
			class ' . $mockControllerClassName . ' extends \F3\FLOW3\MVC\Controller\ActionController {
				public function someGreatAction() {}
			}
     	');

		$mockController = $this->getMock($mockControllerClassName, array('someGreatAction'), array(), '', FALSE);

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getClassNameByObjectName')
			->with('F3\MyControllerObjectName')
			->will($this->returnValue(get_class($mockController)));

		$request = $this->getMock('F3\FLOW3\MVC\Request', array('getControllerObjectName'), array(), '', FALSE);
		$request->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('F3\MyControllerObjectName'));
		$request->injectObjectManager($mockObjectManager);

		$request->setControllerActionName('somegreat');
		$this->assertEquals('someGreat', $request->getControllerActionName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theRepresentationFormatCanBeSetAndRetrieved() {
		$request = new \F3\FLOW3\MVC\Request();
		$request->setFormat('html');
		$this->assertEquals('html', $request->getFormat());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aFlagCanBeSetIfTheRequestNeedsToBeDispatchedAgain() {
		$request = new \F3\FLOW3\MVC\Request();
		$this->assertFalse($request->isDispatched());

		$request->setDispatched(TRUE);
		$this->assertTrue($request->isDispatched());
	}
}

?>