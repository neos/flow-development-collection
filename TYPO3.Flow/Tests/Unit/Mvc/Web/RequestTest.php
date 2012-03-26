<?php
namespace TYPO3\FLOW3\Tests\Unit\Mvc\Web;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the MVC Web Request class
 *
 */
class RequestTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Mvc\ActionRequest
	 */
	protected $request;

	/**
	 * @var \TYPO3\FLOW3\Http\Uri
	 */
	protected $requestUri;

	/**
	 * @var ArrayObject
	 */
	protected $SERVER;

	/**
	 * Sets up this test case
	 *
	 */
	protected function setUp() {
		$this->mockEnvironment = $this->getAccessibleMock('TYPO3\FLOW3\Utility\Environment', array(), array(), '', FALSE);

		$this->SERVER = array();
		$this->mockEnvironment->_set('SERVER', $this->SERVER);

		$uriString = 'http://username:password@subdomain.domain.com:8080/path1/path2/index.php?argument1=value1&argument2=value2#anchor';
		$this->requestUri = new \TYPO3\FLOW3\Http\Uri($uriString);
	}

	/**
	 * @test
	 */
	public function getControllerObjectNameReturnsAnEmptyStringIfTheResolvedControllerDoesNotExist() {
		$mockRouter = $this->getMock('TYPO3\FLOW3\Mvc\Routing\RouterInterface');
		$mockRouter->expects($this->once())->method('getControllerObjectName')
			->with('SomePackage', 'Some\Subpackage', 'SomeController')
			->will($this->returnValue(NULL));

		$mockPackageManager = $this->getMock('TYPO3\FLOW3\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->once())->method('getCaseSensitivePackageKey')
			->with($this->equalTo('Somepackage'))
			->will($this->returnValue('SomePackage'));

		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$request->injectRouter($mockRouter);
		$request->injectPackageManager($mockPackageManager);
		$request->setControllerPackageKey('Somepackage');
		$request->setControllerSubPackageKey('Some\Subpackage');
		$request->setControllerName('SomeController');

		$this->assertEquals('', $request->getControllerObjectName());
	}

	/**
	 * @test
	 * @dataProvider caseSensitiveObjectNames
	 */
	public function setControllerObjectNameSplitsTheGivenObjectNameIntoItsParts($objectName, array $parts) {
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')->will($this->returnValue($objectName));
		$mockObjectManager->expects($this->once())->method('getPackageKeyByObjectName')->with($objectName)->will($this->returnValue($parts['controllerPackageKey']));

		$request = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\ActionRequest', array('dummy'));
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
	 */
	public function aSingleArgumentCanBeSetWithSetArgumentAndRetrievedWithGetArgument() {
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$request->setArgument('someArgumentName', 'theValue');
		$this->assertEquals('theValue', $request->getArgument('someArgumentName'));
	}

	/**
	 * Test for Bug #31773 http://forge.typo3.org/issues/31773
	 * @test
	 */
	public function setArgumentWorksWithASingleUndercoreToo() {
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$request->setArgument('_', 'theValue');
		$this->assertEquals('theValue', $request->getArgument('_'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\InvalidArgumentNameException
	 */
	public function setArgumentThrowsExceptionIfTheGivenArgumentNameIsNoString() {
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$request->setArgument(123, 'theValue');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\InvalidArgumentNameException
	 */
	public function setArgumentThrowsExceptionIfTheGivenArgumentNameIsAnEmptyString() {
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$request->setArgument('', 'theValue');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\InvalidArgumentTypeException
	 */
	public function setArgumentThrowsExceptionIfTheGivenArgumentValueIsAnObject() {
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$request->setArgument('theKey', new \stdClass());
	}

	/**
	 * @test
	 */
	public function setArgumentsOverridesAllExistingArguments() {
		$arguments = array('key1' => 'value1', 'key2' => 'value2');
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$request->setArgument('someKey', 'shouldBeOverridden');
		$request->setArguments($arguments);

		$actualResult = $request->getArguments();
		$this->assertEquals($arguments, $actualResult);
	}

	/**
	 * @test
	 */
	public function setArgumentsCallsSetArgumentForEveryArrayEntry() {
		$request = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array('setArgument'));
		$request->expects($this->at(0))->method('setArgument')->with('key1', 'value1');
		$request->expects($this->at(1))->method('setArgument')->with('key2', 'value2');
		$request->setArguments(array('key1' => 'value1', 'key2' => 'value2'));
	}

	/**
	 * @test
	 */
	public function setArgumentShouldSetControllerPackageKeyIfPackageKeyIsGiven() {
		$request = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array('setControllerPackageKey'));
		$request->expects($this->any())->method('setControllerPackageKey')->with('MyPackage');
		$request->setArgument('@package', 'MyPackage');
		$this->assertFalse($request->hasArgument('@package'));
	}

	/**
	 * @test
	 */
	public function setArgumentShouldSetControllerSubpackageKeyIfSubpackageKeyIsGiven() {
		$request = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array('setControllerSubpackageKey'));
		$request->expects($this->any())->method('setControllerSubpackageKey')->with('MySubPackage');
		$request->setArgument('@subpackage', 'MySubPackage');
		$this->assertFalse($request->hasArgument('@subpackage'));
	}

	/**
	 * @test
	 */
	public function setArgumentShouldSetControllerNameIfControllerIsGiven() {
		$request = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array('setControllerName'));
		$request->expects($this->any())->method('setControllerName')->with('MyController');
		$request->setArgument('@controller', 'MyController');
		$this->assertFalse($request->hasArgument('@controller'));
	}

	/**
	 * @test
	 */
	public function setArgumentShouldSetControllerActionNameIfActionIsGiven() {
		$request = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array('setControllerActionName'));
		$request->expects($this->any())->method('setControllerActionName')->with('foo');
		$request->setArgument('@action', 'foo');
		$this->assertFalse($request->hasArgument('@action'));
	}

	/**
	 * @test
	 */
	public function setArgumentShouldSetFormatIfFormatIsGiven() {
		$request = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array('setFormat'));
		$request->expects($this->any())->method('setFormat')->with('txt');
		$request->setArgument('@format', 'txt');
		$this->assertFalse($request->hasArgument('@format'));
	}

	/**
	 * @test
	 */
	public function internalArgumentsShouldNotBeReturnedAsNormalArgument() {
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$request->setArgument('__referrer', 'foo');
		$this->assertFalse($request->hasArgument('__referrer'));
	}

	/**
	 * @test
	 */
	public function internalArgumentsShouldBeStoredAsInternalArguments() {
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$request->setArgument('__referrer', 'foo');
		$this->assertSame('foo', $request->getInternalArgument('__referrer'));
	}

	/**
	 * @test
	 */
	public function hasInternalArgumentShouldReturnNullIfArgumentNotFound() {
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$this->assertNull($request->getInternalArgument('__nonExistingInternalArgument'));
	}

	/**
	 * @test
	 */
	public function setArgumentAcceptsObjectIfArgumentIsInternal() {
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$object = new \stdClass();
		$request->setArgument('__theKey', $object);
		$this->assertSame($object, $request->getInternalArgument('__theKey'));
	}

	/**
	 * @test
	 */
	public function multipleArgumentsCanBeSetWithSetArgumentsAndRetrievedWithGetArguments() {
		$arguments = array(
			'firstArgument' => 'firstValue',
			'dænishÅrgument' => 'görman välju',
			'3a' => '3v'
		);
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$request->setArguments($arguments);
		$this->assertEquals($arguments, $request->getArguments());
	}

	/**
	 * @test
	 */
	public function hasArgumentTellsIfAnArgumentExists() {
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$request->setArgument('existingArgument', 'theValue');

		$this->assertTrue($request->hasArgument('existingArgument'));
		$this->assertFalse($request->hasArgument('notExistingArgument'));
	}

	/**
	 * @test
	 */
	public function theControllerNameCanBeSetAndRetrieved() {
		$mockRouter = $this->getMock('TYPO3\FLOW3\Mvc\Routing\RouterInterface');
		$mockRouter->expects($this->once())->method('getControllerObjectName')
			->with('TestPackage', '', 'Some')
			->will($this->returnValue('TYPO3\TestPackage\Controller\SomeController'));

		$mockPackageManager = $this->getMock('TYPO3\FLOW3\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->once())->method('getCaseSensitivePackageKey')
			->will($this->returnValue('TestPackage'));

		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$request->injectRouter($mockRouter);
		$request->injectPackageManager($mockPackageManager);
		$request->setControllerPackageKey('TestPackage');
		$request->setControllerName('Some');
		$this->assertEquals('Some', $request->getControllerName());
	}

	/**
	 * @test
	 */
	public function theControllerNameWillBeExtractedFromTheControllerObjectNameToAssureTheCorrectCase() {
		$request = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array('getControllerObjectName'), array(), '', FALSE);
		$request->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('TYPO3\MyPackage\Controller\Foo\BarController'));

		$request->setControllerName('foo\bar');
		$this->assertEquals('Foo\Bar', $request->getControllerName());
	}

	/**
	 * @test
	 */
	public function thePackageKeyOfTheControllerCanBeSetAndRetrieved() {
		$mockPackageManager = $this->getMock('TYPO3\FLOW3\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->once())->method('getCaseSensitivePackageKey')
			->will($this->returnValue('TestPackage'));

		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$request->injectPackageManager($mockPackageManager);
		$request->setControllerPackageKey('TestPackage');
		$this->assertEquals('TestPackage', $request->getControllerPackageKey());
	}

	/**
	 * @test
	 */
	public function invalidPackageKeysAreRejected() {
		$mockPackageManager = $this->getMock('TYPO3\FLOW3\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->once())->method('getCaseSensitivePackageKey')
			->will($this->returnValue(FALSE));

		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$request->injectPackageManager($mockPackageManager);
		$request->setControllerPackageKey('Some_Invalid_Key');
	}

	/**
	 * @test
	 */
	public function theActionNameCanBeSetAndRetrieved() {
		$request = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array('getControllerObjectName'), array(), '', FALSE);
		$request->expects($this->once())->method('getControllerObjectName')->will($this->returnValue(''));

		$request->setControllerActionName('theAction');
		$this->assertEquals('theAction', $request->getControllerActionName());
	}

	/**
	 * @test
	 */
	public function theActionNamesCaseIsFixedIfItIsallLowerCaseAndTheControllerObjectNameIsKnown() {
		$mockControllerClassName = 'Mock' . md5(uniqid(mt_rand(), TRUE));
		eval('
			class ' . $mockControllerClassName . ' extends \TYPO3\FLOW3\Mvc\Controller\ActionController {
				public function someGreatAction() {}
			}
     	');

		$mockController = $this->getMock($mockControllerClassName, array('someGreatAction'), array(), '', FALSE);

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getClassNameByObjectName')
			->with('TYPO3\FLOW3\MyControllerObjectName')
			->will($this->returnValue(get_class($mockController)));

		$request = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array('getControllerObjectName'), array(), '', FALSE);
		$request->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('TYPO3\FLOW3\MyControllerObjectName'));
		$request->injectObjectManager($mockObjectManager);

		$request->setControllerActionName('somegreat');
		$this->assertEquals('someGreat', $request->getControllerActionName());
	}

	/**
	 * @test
	 */
	public function theRepresentationFormatCanBeSetAndRetrieved() {
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$request->setFormat('html');
		$this->assertEquals('html', $request->getFormat());
	}

	/**
	 * @test
	 */
	public function theRepresentationFormatIsAutomaticallyLowercased() {
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$request->setFormat('hTmL');
		$this->assertEquals('html', $request->getFormat());
	}

	/**
	 * @test
	 */
	public function aFlagCanBeSetIfTheRequestNeedsToBeDispatchedAgain() {
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$this->assertFalse($request->isDispatched());

		$request->setDispatched(TRUE);
		$this->assertTrue($request->isDispatched());
	}

	/**
	 * @test
	 */
	public function controllerNameDefaultsToNull() {
		$mockRouter = $this->getMock('TYPO3\FLOW3\Mvc\Routing\RouterInterface');
		$mockRouter->expects($this->once())->method('getControllerObjectName')
			->with('', '', '')
			->will($this->returnValue(NULL));
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$request->injectRouter($mockRouter);
		$this->assertNull($request->getControllerName());
	}

	/**
	 * @test
	 */
	public function controllerActionNameDefaultsToNull() {
		$mockRouter = $this->getMock('TYPO3\FLOW3\Mvc\Routing\RouterInterface');
		$mockRouter->expects($this->once())->method('getControllerObjectName')
			->with('', '', '')
			->will($this->returnValue(NULL));
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$request->injectRouter($mockRouter);
		$this->assertNull($request->getControllerActionName());
	}

	/**
	 * @test
	 */
	public function getArgumentsReturnsProperlyInitializedArgumentsArrayForNewRequest() {
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$this->assertInternalType('array', $request->getArguments(), 'getArguments() does not return an array for a virgin request object.');
	}

	/**
	 * Checks if the request URI is returned as expected.
	 *
	 * @test
	 */
	public function getRequestUriReturnsTheBaseUriDetectedByTheEnvironmentClass() {
		$expectedRequestUri = new \TYPO3\FLOW3\Http\Uri('http://www.server.com/foo/bar');

		$request = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\ActionRequest', array('dummy'));
		$request->_set('requestUri', $expectedRequestUri);

		$this->assertEquals($expectedRequestUri, $request->getRequestUri());
	}

	/**
	 * Returns the base URI of the current request.
	 *
	 * @test
	 */
	public function getBaseUriReturnsTheBaseUriDetectedByTheEnvironmentClass() {
		$expectedBaseUri = new \TYPO3\FLOW3\Http\Uri('http://www.server.com/');

		$request = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\ActionRequest', array('dummy'));
		$request->_set('baseUri', $expectedBaseUri);

		$this->assertEquals($expectedBaseUri, $request->getBaseUri(), 'The returned baseUri is not as expected.');
	}

	/**
	 * @test
	 */
	public function theRequestMethodCanBeSetAndRetrieved() {
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();

		$request->setMethod('GET');
		$this->assertEquals('GET', $request->getMethod());

		$request->setMethod('POST');
		$this->assertEquals('POST', $request->getMethod());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\InvalidRequestMethodException
	 */
	public function requestMethodsWhichAreNotCompletelyUpperCaseAreRejected() {
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$request->setMethod('sOmEtHing');
	}

	/**
	 * @test
	 */
	public function getReferringRequestShouldReturnNullByDefault() {
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$this->assertNull($request->getReferringRequest());
	}

	/**
	 * @test
	 */
	public function getReferringRequestShouldReturnCorrectlyBuiltReferringRequest() {
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$request->setArgument('__referrer', array(
			'@controller' => 'Foo',
			'@action' => 'bar'
		));
		$referringRequest = $request->getReferringRequest();
		$this->assertNotNull($referringRequest);

		$this->assertAttributeEquals('Foo', 'controllerName', $referringRequest);
		$this->assertAttributeEquals('bar', 'controllerActionName', $referringRequest);
	}
}
?>