<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 */

/**
 * Testcase for the MVC Generic Request
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class RequestTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theDefaultPatternForBuildingTheControllerObjectNameIsPackageKeyControllerControllerNameController() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')
			->with($this->equalTo('f3\testpackage\controller\foocontroller'))
			->will($this->returnValue('F3\TestPackage\Controller\FooController'));

		$mockPackageManager = $this->getMock('F3\FLOW3\Package\ManagerInterface');
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
	public function thePatternForBuildingTheControllerObjectNameCanBeCustomized() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')
			->with($this->equalTo('f3\testpackage\bar\baz\foo'))
			->will($this->returnValue('F3\TestPackage\Bar\Baz\Foo'));

		$mockPackageManager = $this->getMock('F3\FLOW3\Package\ManagerInterface');
		$mockPackageManager->expects($this->once())->method('getCaseSensitivePackageKey')
			->will($this->returnValue('TestPackage'));

		$request = new \F3\FLOW3\MVC\Request();
		$request->injectObjectManager($mockObjectManager);
		$request->injectPackageManager($mockPackageManager);
		$request->setControllerPackageKey('TestPackage');
		$request->setControllerName('Foo');
		$request->setControllerObjectNamePattern('F3\@package\Bar\Baz\@controller');

		$this->assertEquals('F3\TestPackage\Bar\Baz\Foo', $request->getControllerObjectName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function lowerCasePackageKeysAndObjectNamesAreConvertedToTheRealObjectName() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')
			->with($this->equalTo('f3\testpackage\bar\baz\foo'))
			->will($this->returnValue('F3\TestPackage\Bar\Baz\Foo'));

		$mockPackageManager = $this->getMock('F3\FLOW3\Package\ManagerInterface');
		$mockPackageManager->expects($this->once())->method('getCaseSensitivePackageKey')
			->with($this->equalTo('testpackage'))
			->will($this->returnValue('TestPackage'));

		$request = new \F3\FLOW3\MVC\Request();
		$request->injectObjectManager($mockObjectManager);
		$request->injectPackageManager($mockPackageManager);
		$request->setControllerPackageKey('testpackage');
		$request->setControllerName('foo');
		$request->setControllerObjectNamePattern('f3\@package\bar\baz\@controller');

		$this->assertEquals('F3\TestPackage\Bar\Baz\Foo', $request->getControllerObjectName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function thePatternForBuildingTheViewObjectNameCanBeCustomized() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')
			->with($this->equalTo('F3\TestPackage\Vista\FooXbarYxmlZ'))
			->will($this->returnValue('F3\TestPackage\Vista\FooXBarYXMLZ'));

		$mockPackageManager = $this->getMock('F3\FLOW3\Package\ManagerInterface');
		$mockPackageManager->expects($this->once())->method('getCaseSensitivePackageKey')
			->will($this->returnValue('TestPackage'));

		$request = new \F3\FLOW3\MVC\Request();
		$request->injectObjectManager($mockObjectManager);
		$request->injectPackageManager($mockPackageManager);

		$request->setControllerPackageKey('TestPackage');
		$request->setControllerName('Foo');
		$request->setControllerActionName('bar');
		$request->setFormat('xml');
		$request->setViewObjectNamePattern('F3\@package\Vista\@controllerX@actionY@formatZ');

		$this->assertEquals('F3\TestPackage\Vista\FooXBarYXMLZ', $request->getViewObjectName());
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
		$arguments = new \ArrayObject(array(
			'firstArgument' => 'firstValue',
			'dænishÅrgument' => 'görman välju',
			'3a' => '3v'
		));
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
		$request = new \F3\FLOW3\MVC\Request();
		$request->setControllerName('Some');
		$this->assertEquals('Some', $request->getControllerName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function thePackageKeyOfTheControllerCanBeSetAndRetrieved() {
		$mockPackageManager = $this->getMock('F3\FLOW3\Package\ManagerInterface');
		$mockPackageManager->expects($this->once())->method('getCaseSensitivePackageKey')
			->will($this->returnValue('TestPackage'));

		$request = new \F3\FLOW3\MVC\Request();
		$request->injectPackageManager($mockPackageManager);
		$request->setControllerPackageKey('TestPackage');
		$this->assertEquals('TestPackage', $request->getControllerPackageKey());
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\InvalidPackageKey
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invalidPackageKeysAreRejected() {
		$mockPackageManager = $this->getMock('F3\FLOW3\Package\ManagerInterface');
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
		$request = new \F3\FLOW3\MVC\Request();
		$request->setControllerActionName('theAction');
		$this->assertEquals('theAction', $request->getControllerActionName());
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
	 * @expectedException \F3\FLOW3\MVC\Exception\InvalidFormat
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invalidFormatsAreRejected() {
		$request = new \F3\FLOW3\MVC\Request();
		$request->setFormat('.xml');
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