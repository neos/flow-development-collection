<?php
namespace TYPO3\Flow\Tests\Unit\Mvc;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Http\Request as HttpRequest;

/**
 * Testcase for the MVC Request class
 */
class ActionRequestTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * By design, the root request will always be an HTTP request because it is
	 * the only of the two types which can be instantiated without having to pass
	 * another request as the parent request.
	 *
	 * @test
	 */
	public function anHttpRequestOrActionRequestIsRequiredAsParentRequest() {
		$httpRequest = HttpRequest::create(new Uri('http://robertlemke.com/blog'));

		$actionRequest = new ActionRequest($httpRequest);
		$this->assertSame($httpRequest, $actionRequest->getParentRequest());

		$anotherActionRequest = new ActionRequest($actionRequest);
		$this->assertSame($actionRequest,$anotherActionRequest->getParentRequest());
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @test
	 */
	public function constructorThrowsAnExceptionIfNoValidRequestIsPassed() {
		new ActionRequest(new \stdClass());
	}

	/**
	 * @test
	 */
	public function getHttpRequestReturnsTheHttpRequestWhichIsTheRootOfAllActionRequests() {
		$httpRequest = HttpRequest::create(new Uri('http://robertlemke.com/blog'));
		$actionRequest = new ActionRequest($httpRequest);
		$anotherActionRequest = new ActionRequest($actionRequest);
		$yetAnotherActionRequest = new ActionRequest($anotherActionRequest);

		$this->assertSame($httpRequest, $actionRequest->getHttpRequest());
		$this->assertSame($httpRequest, $yetAnotherActionRequest->getHttpRequest());
		$this->assertSame($httpRequest, $anotherActionRequest->getHttpRequest());
	}

	/**
	 * @test
	 */
	public function getMainRequestReturnsTheTopLevelActionRequestWhoseParentIsTheHttpRequest() {
		$httpRequest = HttpRequest::create(new Uri('http://robertlemke.com/blog'));
		$actionRequest = new ActionRequest($httpRequest);
		$anotherActionRequest = new ActionRequest($actionRequest);
		$yetAnotherActionRequest = new ActionRequest($anotherActionRequest);

		$this->assertSame($actionRequest, $actionRequest->getMainRequest());
		$this->assertSame($actionRequest, $yetAnotherActionRequest->getMainRequest());
		$this->assertSame($actionRequest, $anotherActionRequest->getMainRequest());
	}

	/**
	 * @test
	 */
	public function isMainRequestChecksIfTheParentRequestIsNotAnHttpRequest() {
		$httpRequest = HttpRequest::create(new Uri('http://robertlemke.com/blog'));
		$actionRequest = new ActionRequest($httpRequest);
		$anotherActionRequest = new ActionRequest($actionRequest);
		$yetAnotherActionRequest = new ActionRequest($anotherActionRequest);

		$this->assertTrue($actionRequest->isMainRequest());
		$this->assertFalse($anotherActionRequest->isMainRequest());
		$this->assertFalse($yetAnotherActionRequest->isMainRequest());
	}

	/**
	 * @test
	 */
	public function requestIsDispatchable() {
		$httpRequest = HttpRequest::create(new Uri('http://robertlemke.com/blog'));
		$actionRequest = new ActionRequest($httpRequest);

		$mockDispatcher = $this->getMock('TYPO3\Flow\SignalSlot\Dispatcher');

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockDispatcher));
		$this->inject($actionRequest, 'objectManager', $mockObjectManager);

		$this->assertFalse($actionRequest->isDispatched());
		$actionRequest->setDispatched(TRUE);
		$this->assertTrue($actionRequest->isDispatched());
		$actionRequest->setDispatched(FALSE);
		$this->assertFalse($actionRequest->isDispatched());
	}

	/**
	 * @test
	 */
	public function getControllerObjectNameReturnsObjectNameDerivedFromPreviouslySetControllerInformation() {
		$httpRequest = HttpRequest::create(new Uri('http://robertlemke.com/blog'));

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('getCaseSensitiveObjectName')->with('somepackage\Package')
				->will($this->returnValue('SomePackage\Package'));
		$mockObjectManager->expects($this->at(1))->method('getCaseSensitiveObjectName')->with('SomePackage\Some\Subpackage\Controller\SomeControllerController')
				->will($this->returnValue('SomePackage\Some\SubPackage\Controller\SomeControllerController'));

		$actionRequest = $httpRequest->createActionRequest();
		$this->inject($actionRequest, 'objectManager', $mockObjectManager);

		$actionRequest->setControllerPackageKey('somepackage');
		$actionRequest->setControllerSubPackageKey('Some\Subpackage');
		$actionRequest->setControllerName('SomeController');

		$this->assertEquals('SomePackage\Some\SubPackage\Controller\SomeControllerController', $actionRequest->getControllerObjectName());
	}

	/**
	 * @test
	 */
	public function getControllerObjectNameReturnsAnEmptyStringIfTheResolvedControllerDoesNotExist() {
		$httpRequest = HttpRequest::create(new Uri('http://robertlemke.com/blog'));

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('getCaseSensitiveObjectName')->with('somepackage\Package')
				->will($this->returnValue('SomePackage\Package'));
		$mockObjectManager->expects($this->at(1))->method('getCaseSensitiveObjectName')->with('SomePackage\Some\Subpackage\Controller\SomeControllerController')
				->will($this->returnValue(FALSE));

		$actionRequest = $this->getAccessibleMock('TYPO3\Flow\Mvc\ActionRequest', array('dummy'), array($httpRequest));
		$actionRequest->_set('objectManager', $mockObjectManager);

		$actionRequest = $httpRequest->createActionRequest();
		$this->inject($actionRequest, 'objectManager', $mockObjectManager);

		$actionRequest->setControllerPackageKey('somepackage');
		$actionRequest->setControllerSubPackageKey('Some\Subpackage');
		$actionRequest->setControllerName('SomeController');

		$this->assertEquals('', $actionRequest->getControllerObjectName());
	}

	/**
	 * @test
	 * @dataProvider caseSensitiveObjectNames
	 */
	public function setControllerObjectNameSplitsTheGivenObjectNameIntoItsParts($objectName, array $parts) {
		$httpRequest = HttpRequest::create(new Uri('http://robertlemke.com/blog'));

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->with($objectName)->will($this->returnValue($objectName));
		$mockObjectManager->expects($this->any())->method('getPackageKeyByObjectName')->with($objectName)->will($this->returnValue($parts['controllerPackageKey']));

		$actionRequest = $this->getAccessibleMock('TYPO3\Flow\Mvc\ActionRequest', array('dummy'), array($httpRequest));
		$actionRequest->_set('objectManager', $mockObjectManager);

		$actionRequest->setControllerObjectName($objectName);
		$this->assertSame($parts['controllerPackageKey'], $actionRequest->getControllerPackageKey());
		$this->assertSame($parts['controllerSubpackageKey'], $actionRequest->getControllerSubpackageKey());
		$this->assertSame($parts['controllerName'], $actionRequest->getControllerName());
	}

	/**
	 * Data Provider
	 */
	public function caseSensitiveObjectNames() {
		return array(
			array(
				'TYPO3\Foo\Controller\BarController',
				array(
					'controllerPackageKey' => 'TYPO3.Foo',
					'controllerSubpackageKey' => '',
					'controllerName' => 'Bar',
				)
			),
			array(
				'TYPO3\Foo\Bar\Controller\BazController',
				array(
					'controllerPackageKey' => 'TYPO3.Foo',
					'controllerSubpackageKey' => 'Bar',
					'controllerName' => 'Baz',
				)
			),
			array(
				'TYPO3\Foo\Bar\Bla\Controller\Baz\QuuxController',
				array(
					'controllerPackageKey' => 'TYPO3.Foo',
					'controllerSubpackageKey' => 'Bar\Bla',
					'controllerName' => 'Baz\Quux',
				)
			),
			array(
				'TYPO3\Foo\Controller\Bar\BazController',
				array(
					'controllerPackageKey' => 'TYPO3.Foo',
					'controllerSubpackageKey' => '',
					'controllerName' => 'Bar\Baz',
				)
			),
			array(
				'TYPO3\Foo\Controller\Bar\Baz\QuuxController',
				array(
					'controllerPackageKey' => 'TYPO3.Foo',
					'controllerSubpackageKey' => '',
					'controllerName' => 'Bar\Baz\Quux',
				)
			)
		);
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Object\Exception\UnknownObjectException
	 */
	public function setControllerObjectNameThrowsExceptionOnUnknownObjectName() {
		$httpRequest = HttpRequest::create(new Uri('http://robertlemke.com/blog'));

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnValue(FALSE));

		$actionRequest = $this->getAccessibleMock('TYPO3\Flow\Mvc\ActionRequest', array('dummy'), array($httpRequest));
		$actionRequest->_set('objectManager', $mockObjectManager);

		$actionRequest->setControllerObjectName('SomeUnknownControllerObjectName');
	}

	/**
	 * @test
	 */
	public function theControllerNameWillBeExtractedFromTheControllerObjectNameToAssureTheCorrectCase() {
		$request = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array('getControllerObjectName'), array(), '', FALSE);
		$request->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('TYPO3\MyPackage\Controller\Foo\BarController'));

		$request->setControllerName('foo\bar');
		$this->assertEquals('Foo\Bar', $request->getControllerName());
	}

	/**
	 * @test
	 */
	public function ifNoControllerObjectNameCouldBeDeterminedTheUnknownCasesControllerNameIsReturned() {
		$request = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array('getControllerObjectName'), array(), '', FALSE);
		$request->expects($this->once())->method('getControllerObjectName')->will($this->returnValue(''));

		$request->setControllerName('foo\bar');
		$this->assertEquals('foo\bar', $request->getControllerName());
	}

	/**
	 * Data Provider
	 */
	public function invalidControllerNames() {
		return array(
			array(42),
			array(FALSE),
			array('foo_bar_baz'),
		);
	}

	/**
	 * @test
	 * @dataProvider invalidControllerNames
	 * @expectedException TYPO3\Flow\Mvc\Exception\InvalidControllerNameException
	 */
	public function setControllerNameThrowsExceptionOnInvalidControllerNames($invalidControllerName) {
		$httpRequest = HttpRequest::create(new Uri('http://robertlemke.com/blog'));
		$actionRequest = new ActionRequest($httpRequest);

		$actionRequest->setControllerName($invalidControllerName);
	}

	/**
	 * @test
	 */
	public function theActionNameCanBeSetAndRetrieved() {
		$request = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array('getControllerObjectName'), array(), '', FALSE);
		$request->expects($this->once())->method('getControllerObjectName')->will($this->returnValue(''));

		$request->setControllerActionName('theAction');
		$this->assertEquals('theAction', $request->getControllerActionName());
	}

	/**
	 * Data Provider
	 */
	public function invalidActionNames() {
		return array(
			array(42),
			array(''),
			array('FooBar'),
		);
	}

	/**
	 * @test
	 * @dataProvider invalidActionNames
	 * @expectedException TYPO3\Flow\Mvc\Exception\InvalidActionNameException
	 */
	public function setControllerActionNameThrowsExceptionOnInvalidActionNames($invalidActionName) {
		$httpRequest = HttpRequest::create(new Uri('http://robertlemke.com/blog'));
		$actionRequest = new ActionRequest($httpRequest);

		$actionRequest->setControllerActionName($invalidActionName);
	}

	/**
	 * @test
	 */
	public function theActionNamesCaseIsFixedIfItIsAllLowerCaseAndTheControllerObjectNameIsKnown() {
		$mockControllerClassName = 'Mock' . md5(uniqid(mt_rand(), TRUE));
		eval('
			class ' . $mockControllerClassName . ' extends \TYPO3\Flow\Mvc\Controller\ActionController {
				public function someGreatAction() {}
			}
		');

		$mockController = $this->getMock($mockControllerClassName, array('someGreatAction'), array(), '', FALSE);

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getClassNameByObjectName')
			->with('TYPO3\Flow\MyControllerObjectName')
			->will($this->returnValue(get_class($mockController)));

		$request = $this->getAccessibleMock('TYPO3\Flow\Mvc\ActionRequest', array('getControllerObjectName'), array(), '', FALSE);
		$request->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('TYPO3\Flow\MyControllerObjectName'));
		$request->_set('objectManager', $mockObjectManager);

		$request->setControllerActionName('somegreat');
		$this->assertEquals('someGreat', $request->getControllerActionName());
	}

	/**
	 * @test
	 */
	public function aSingleArgumentCanBeSetWithSetArgumentAndRetrievedWithGetArgument() {
		$httpRequest = HttpRequest::create(new Uri('http://robertlemke.com/blog'));

		$actionRequest = new ActionRequest($httpRequest);
		$actionRequest->setArgument('someArgumentName', 'theValue');
		$this->assertEquals('theValue', $actionRequest->getArgument('someArgumentName'));
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Mvc\Exception\InvalidArgumentNameException
	 */
	public function setArgumentThrowsAnExceptionOnInvalidArgumentNames() {
		$httpRequest = HttpRequest::create(new Uri('http://robertlemke.com/blog'));

		$actionRequest = new ActionRequest($httpRequest);
		$actionRequest->setArgument('', 'theValue');
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Mvc\Exception\InvalidArgumentTypeException
	 */
	public function setArgumentDoesNotAllowObjectValuesForRegularArguments() {
		$httpRequest = HttpRequest::create(new Uri('http://robertlemke.com/blog'));

		$actionRequest = new ActionRequest($httpRequest);
		$actionRequest->setArgument('foo', new \stdClass());
	}

	/**
	 * @test
	 */
	public function allArgumentsCanBeSetOrRetrievedAtOnce() {
		$httpRequest = HttpRequest::create(new Uri('http://robertlemke.com/blog'));

		$arguments = array(
			'foo' => 'fooValue',
			'bar' => 'barValue'
		);

		$actionRequest = new ActionRequest($httpRequest);
		$actionRequest->setArguments($arguments);
		$this->assertEquals($arguments, $actionRequest->getArguments());
	}

	/**
	 * @test
	 */
	public function internalArgumentsAreHandledSeparately() {
		$httpRequest = HttpRequest::create(new Uri('http://robertlemke.com/blog'));

		$actionRequest = new ActionRequest($httpRequest);
		$actionRequest->setArgument('__someInternalArgument', 'theValue');

		$this->assertFalse($actionRequest->hasArgument('__someInternalArgument'));
		$this->assertEquals('theValue', $actionRequest->getInternalArgument('__someInternalArgument'));
		$this->assertEquals(array('__someInternalArgument' => 'theValue'), $actionRequest->getInternalArguments());
	}

	/**
	 * @test
	 */
	public function internalArgumentsMayHaveObjectValues() {
		$httpRequest = HttpRequest::create(new Uri('http://robertlemke.com/blog'));

		$someObject = new \stdClass();

		$actionRequest = new ActionRequest($httpRequest);
		$actionRequest->setArgument('__someInternalArgument', $someObject);

		$this->assertSame($someObject, $actionRequest->getInternalArgument('__someInternalArgument'));
	}

	/**
	 * @test
	 */
	public function pluginArgumentsAreHandledSeparately() {
		$httpRequest = HttpRequest::create(new Uri('http://robertlemke.com/blog'));

		$actionRequest = new ActionRequest($httpRequest);
		$actionRequest->setArgument('--typo3-flow-foo-viewhelper-paginate', array('@controller' => 'Foo', 'page' => 5));

		$this->assertFalse($actionRequest->hasArgument('--typo3-flow-foo-viewhelper-paginate'));
		$this->assertEquals(array('typo3-flow-foo-viewhelper-paginate' => array('@controller' => 'Foo', 'page' => 5)), $actionRequest->getPluginArguments());
	}

	/**
	 * @test
	 */
	public function argumentNamespaceCanBeSpecified() {
		$httpRequest = HttpRequest::create(new Uri('http://localhost'));
		$actionRequest = new ActionRequest($httpRequest);

		$this->assertSame('', $actionRequest->getArgumentNamespace());
		$actionRequest->setArgumentNamespace('someArgumentNamespace');
		$this->assertSame('someArgumentNamespace', $actionRequest->getArgumentNamespace());
	}

	/**
	 * @test
	 */
	public function theRepresentationFormatCanBeSetAndRetrieved() {
		$httpRequest = HttpRequest::create(new Uri('http://foo.com'));
		$actionRequest = new ActionRequest($httpRequest);

		$actionRequest->setFormat('html');
		$this->assertEquals('html', $actionRequest->getFormat());

		$actionRequest->setFormat('doc');
		$this->assertEquals('doc', $actionRequest->getFormat());

		$actionRequest->setFormat('hTmL');
		$this->assertEquals('html', $actionRequest->getFormat());
	}

	/**
	 * @test
	 */
	public function cloneResetsTheStatusToNotDispatched() {
		$httpRequest = HttpRequest::create(new Uri('http://foo.com'));
		$originalRequest = new ActionRequest($httpRequest);

		$originalRequest->setDispatched(TRUE);
		$cloneRequest = clone $originalRequest;

		$this->assertTrue($originalRequest->isDispatched());
		$this->assertFalse($cloneRequest->isDispatched());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Security\Exception\InvalidHashException
	 */
	public function getReferringRequestThrowsAnExceptionIfTheHmacOfTheArgumentsCouldNotBeValid() {
		$referrer = array(
			'@controller' => 'Foo',
			'@action' => 'bar',
			'arguments' => base64_encode('some manipulated arguments string without valid HMAC')
		);

		$httpRequest = HttpRequest::create(new Uri('http://acme.com', 'GET'));
		$request = new ActionRequest($httpRequest);
		$request->setArgument('__referrer', $referrer);
		$this->inject($request, 'hashService', new \TYPO3\Flow\Security\Cryptography\HashService());
		$request->getReferringRequest();
	}

	/**
	 * @test
	 */
	public function setDispatchedEmitsSignalIfDispatched() {
		$httpRequest = HttpRequest::create(new Uri('http://robertlemke.com/blog'));
		$actionRequest = new ActionRequest($httpRequest);

		$mockDispatcher = $this->getMock('TYPO3\Flow\SignalSlot\Dispatcher');
		$mockDispatcher->expects($this->once())->method('dispatch')->with('TYPO3\Flow\Mvc\ActionRequest', 'requestDispatched', array($actionRequest));

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockDispatcher));
		$this->inject($actionRequest, 'objectManager', $mockObjectManager);

		$actionRequest->setDispatched(TRUE);
	}

}

?>