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
use TYPO3\Flow\Http\Request as HttpRequest;
use TYPO3\Flow\Mvc\Exception\NoSuchArgumentException;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Property\PropertyMappingConfiguration;
use TYPO3\Flow\Property\TypeConverter\MediaTypeConverterInterface;
use TYPO3\Flow\Security\Exception\InvalidHashException;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the MVC ActionRequest class
 */
class ActionRequestTest extends UnitTestCase {

	/**
	 * @var ActionRequest
	 */
	protected $actionRequest;

	/**
	 * @var HttpRequest|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockHttpRequest;

	/**
	 * @var PropertyMappingConfiguration|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockPropertyMappingConfiguration;

	/**
	 * @var PropertyMapper|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockPropertyMapper;

	public function setUp() {
		$this->mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$this->actionRequest = new ActionRequest($this->mockHttpRequest);

		$this->mockPropertyMappingConfiguration = $this->getMockBuilder('TYPO3\Flow\Property\PropertyMappingConfiguration')->getMock();
		$this->inject($this->actionRequest, 'propertyMappingConfiguration', $this->mockPropertyMappingConfiguration);

		$this->mockPropertyMapper = $this->getMockBuilder('TYPO3\Flow\Property\PropertyMapper')->getMock();
		$this->inject($this->actionRequest, 'propertyMapper', $this->mockPropertyMapper);
	}

	/**
	 * By design, the root request will always be an HTTP request because it is
	 * the only of the two types which can be instantiated without having to pass
	 * another request as the parent request.
	 *
	 * @test
	 */
	public function anHttpRequestOrActionRequestIsRequiredAsParentRequest() {
		$this->assertSame($this->mockHttpRequest, $this->actionRequest->getParentRequest());

		$anotherActionRequest = new ActionRequest($this->actionRequest);
		$this->assertSame($this->actionRequest, $anotherActionRequest->getParentRequest());
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @test
	 */
	public function constructorThrowsAnExceptionIfNoValidRequestIsPassed() {
		new ActionRequest(new \stdClass());
	}

	/**
	 * @test
	 */
	public function getHttpRequestReturnsTheHttpRequestWhichIsTheRootOfAllActionRequests() {
		$anotherActionRequest = new ActionRequest($this->actionRequest);
		$yetAnotherActionRequest = new ActionRequest($anotherActionRequest);

		$this->assertSame($this->mockHttpRequest, $this->actionRequest->getHttpRequest());
		$this->assertSame($this->mockHttpRequest, $yetAnotherActionRequest->getHttpRequest());
		$this->assertSame($this->mockHttpRequest, $anotherActionRequest->getHttpRequest());
	}

	/**
	 * @test
	 */
	public function getMainRequestReturnsTheTopLevelActionRequestWhoseParentIsTheHttpRequest() {
		$anotherActionRequest = new ActionRequest($this->actionRequest);
		$yetAnotherActionRequest = new ActionRequest($anotherActionRequest);

		$this->assertSame($this->actionRequest, $this->actionRequest->getMainRequest());
		$this->assertSame($this->actionRequest, $yetAnotherActionRequest->getMainRequest());
		$this->assertSame($this->actionRequest, $anotherActionRequest->getMainRequest());
	}

	/**
	 * @test
	 */
	public function isMainRequestChecksIfTheParentRequestIsNotAnHttpRequest() {
		$anotherActionRequest = new ActionRequest($this->actionRequest);
		$yetAnotherActionRequest = new ActionRequest($anotherActionRequest);

		$this->assertTrue($this->actionRequest->isMainRequest());
		$this->assertFalse($anotherActionRequest->isMainRequest());
		$this->assertFalse($yetAnotherActionRequest->isMainRequest());
	}

	/**
	 * @test
	 */
	public function requestIsDispatchable() {
		$mockDispatcher = $this->getMock('TYPO3\Flow\SignalSlot\Dispatcher');

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockDispatcher));
		$this->inject($this->actionRequest, 'objectManager', $mockObjectManager);

		$this->assertFalse($this->actionRequest->isDispatched());
		$this->actionRequest->setDispatched(TRUE);
		$this->assertTrue($this->actionRequest->isDispatched());
		$this->actionRequest->setDispatched(FALSE);
		$this->assertFalse($this->actionRequest->isDispatched());
	}

	/**
	 * @test
	 */
	public function getControllerObjectNameReturnsObjectNameDerivedFromPreviouslySetControllerInformation() {
		$mockPackageManager = $this->getMock('TYPO3\Flow\Package\PackageManager');
		$mockPackageManager->expects($this->any())->method('getCaseSensitivePackageKey')->with('somepackage')->will($this->returnValue('SomePackage'));

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('getCaseSensitiveObjectName')->with('SomePackage\Some\Subpackage\Controller\SomeControllerController')
			->will($this->returnValue('SomePackage\Some\SubPackage\Controller\SomeControllerController'));

		$this->inject($this->actionRequest, 'objectManager', $mockObjectManager);
		$this->inject($this->actionRequest, 'packageManager', $mockPackageManager);

		$this->actionRequest->setControllerPackageKey('somepackage');
		$this->actionRequest->setControllerSubPackageKey('Some\Subpackage');
		$this->actionRequest->setControllerName('SomeController');

		$this->assertEquals('SomePackage\Some\SubPackage\Controller\SomeControllerController', $this->actionRequest->getControllerObjectName());
	}

	/**
	 * @test
	 */
	public function getControllerObjectNameReturnsAnEmptyStringIfTheResolvedControllerDoesNotExist() {
		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('getCaseSensitiveObjectName')->with('SomePackage\Some\Subpackage\Controller\SomeControllerController')
			->will($this->returnValue(FALSE));

		$mockPackageManager = $this->getMock('TYPO3\Flow\Package\PackageManager');
		$mockPackageManager->expects($this->any())->method('getCaseSensitivePackageKey')->with('somepackage')->will($this->returnValue('SomePackage'));

		$this->inject($this->actionRequest, 'objectManager', $mockObjectManager);
		$this->inject($this->actionRequest, 'packageManager', $mockPackageManager);

		$this->actionRequest->setControllerPackageKey('somepackage');
		$this->actionRequest->setControllerSubPackageKey('Some\Subpackage');
		$this->actionRequest->setControllerName('SomeController');

		$this->assertEquals('', $this->actionRequest->getControllerObjectName());
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
	 * @dataProvider caseSensitiveObjectNames
	 */
	public function setControllerObjectNameSplitsTheGivenObjectNameIntoItsParts($objectName, array $parts) {
		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->with($objectName)->will($this->returnValue($objectName));
		$mockObjectManager->expects($this->any())->method('getPackageKeyByObjectName')->with($objectName)->will($this->returnValue($parts['controllerPackageKey']));

		$this->inject($this->actionRequest, 'objectManager', $mockObjectManager);

		$this->actionRequest->setControllerObjectName($objectName);
		$this->assertSame($parts['controllerPackageKey'], $this->actionRequest->getControllerPackageKey());
		$this->assertSame($parts['controllerSubpackageKey'], $this->actionRequest->getControllerSubpackageKey());
		$this->assertSame($parts['controllerName'], $this->actionRequest->getControllerName());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Object\Exception\UnknownObjectException
	 */
	public function setControllerObjectNameThrowsExceptionOnUnknownObjectName() {
		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnValue(FALSE));

		$this->inject($this->actionRequest, 'objectManager', $mockObjectManager);

		$this->actionRequest->setControllerObjectName('SomeUnknownControllerObjectName');
	}

	/**
	 * @test
	 */
	public function theControllerNameWillBeExtractedFromTheControllerObjectNameToAssureTheCorrectCase() {
		/** @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject $actionRequest */
		$actionRequest = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array('getControllerObjectName'), array(), '', FALSE);
		$actionRequest->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('TYPO3\MyPackage\Controller\Foo\BarController'));

		$actionRequest->setControllerName('foo\bar');
		$this->assertEquals('Foo\Bar', $actionRequest->getControllerName());
	}

	/**
	 * @test
	 */
	public function ifNoControllerObjectNameCouldBeDeterminedTheUnknownCasesControllerNameIsReturned() {
		/** @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject $actionRequest */
		$actionRequest = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array('getControllerObjectName'), array(), '', FALSE);
		$actionRequest->expects($this->once())->method('getControllerObjectName')->will($this->returnValue(''));

		$actionRequest->setControllerName('foo\bar');
		$this->assertEquals('foo\bar', $actionRequest->getControllerName());
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
	 * @expectedException \TYPO3\Flow\Mvc\Exception\InvalidControllerNameException
	 */
	public function setControllerNameThrowsExceptionOnInvalidControllerNames($invalidControllerName) {
		$this->actionRequest->setControllerName($invalidControllerName);
	}

	/**
	 * @test
	 */
	public function theActionNameCanBeSetAndRetrieved() {
		/** @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject $actionRequest */
		$actionRequest = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array('getControllerObjectName'), array(), '', FALSE);
		$actionRequest->expects($this->once())->method('getControllerObjectName')->will($this->returnValue(''));

		$actionRequest->setControllerActionName('theAction');
		$this->assertEquals('theAction', $actionRequest->getControllerActionName());
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
	 * @expectedException \TYPO3\Flow\Mvc\Exception\InvalidActionNameException
	 */
	public function setControllerActionNameThrowsExceptionOnInvalidActionNames($invalidActionName) {
		$this->actionRequest->setControllerActionName($invalidActionName);
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

		/** @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject $actionRequest */
		$actionRequest = $this->getAccessibleMock('TYPO3\Flow\Mvc\ActionRequest', array('getControllerObjectName'), array(), '', FALSE);
		$actionRequest->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('TYPO3\Flow\MyControllerObjectName'));
		$actionRequest->_set('objectManager', $mockObjectManager);

		$actionRequest->setControllerActionName('somegreat');
		$this->assertEquals('someGreat', $actionRequest->getControllerActionName());
	}

	/**
	 * @test
	 */
	public function aSingleArgumentCanBeSetWithSetArgumentAndRetrievedWithGetArgument() {
		$this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnValue(array()));
		$this->actionRequest->setArgument('someArgumentName', 'theValue');
		$this->assertEquals('theValue', $this->actionRequest->getArgument('someArgumentName'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Mvc\Exception\InvalidArgumentNameException
	 */
	public function setArgumentThrowsAnExceptionOnInvalidArgumentNames() {
		$this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnValue(array()));
		$this->actionRequest->setArgument('', 'theValue');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Mvc\Exception\InvalidArgumentTypeException
	 */
	public function setArgumentDoesNotAllowObjectValuesForRegularArguments() {
		$this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnValue(array()));
		$this->actionRequest->setArgument('foo', new \stdClass());
	}

	/**
	 * @test
	 */
	public function allArgumentsCanBeSetOrRetrievedAtOnce() {
		$arguments = array(
			'foo' => 'fooValue',
			'bar' => 'barValue'
		);

		$this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnValue(array()));

		$this->actionRequest->setArguments($arguments);
		$this->assertEquals($arguments, $this->actionRequest->getArguments());
	}

	/**
	 * @test
	 */
	public function internalArgumentsAreHandledSeparately() {
		$this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnValue(array()));

		$this->actionRequest->setArgument('__someInternalArgument', 'theValue');

		$this->assertFalse($this->actionRequest->hasArgument('__someInternalArgument'));
		$this->assertEquals('theValue', $this->actionRequest->getInternalArgument('__someInternalArgument'));
		$this->assertEquals(array('__someInternalArgument' => 'theValue'), $this->actionRequest->getInternalArguments());
	}

	/**
	 * @test
	 */
	public function internalArgumentsMayHaveObjectValues() {
		$this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnValue(array()));

		$someObject = new \stdClass();

		$this->actionRequest->setArgument('__someInternalArgument', $someObject);

		$this->assertSame($someObject, $this->actionRequest->getInternalArgument('__someInternalArgument'));
	}

	/**
	 * @test
	 */
	public function pluginArgumentsAreHandledSeparately() {
		$this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnValue(array()));

		$this->actionRequest->setArgument('--typo3-flow-foo-viewhelper-paginate', array('@controller' => 'Foo', 'page' => 5));

		$this->assertFalse($this->actionRequest->hasArgument('--typo3-flow-foo-viewhelper-paginate'));
		$this->assertEquals(array('typo3-flow-foo-viewhelper-paginate' => array('@controller' => 'Foo', 'page' => 5)), $this->actionRequest->getPluginArguments());
	}

	/**
	 * @test
	 */
	public function argumentNamespaceCanBeSpecified() {
		$this->assertSame('', $this->actionRequest->getArgumentNamespace());
		$this->actionRequest->setArgumentNamespace('someArgumentNamespace');
		$this->assertSame('someArgumentNamespace', $this->actionRequest->getArgumentNamespace());
	}

	/**
	 * @test
	 */
	public function theRepresentationFormatCanBeSetAndRetrieved() {
		$this->actionRequest->setFormat('html');
		$this->assertEquals('html', $this->actionRequest->getFormat());

		$this->actionRequest->setFormat('doc');
		$this->assertEquals('doc', $this->actionRequest->getFormat());

		$this->actionRequest->setFormat('hTmL');
		$this->assertEquals('html', $this->actionRequest->getFormat());
	}

	/**
	 * @test
	 */
	public function cloneResetsTheStatusToNotDispatched() {
		$this->actionRequest->setDispatched(TRUE);
		$cloneRequest = clone $this->actionRequest;

		$this->assertTrue($this->actionRequest->isDispatched());
		$this->assertFalse($cloneRequest->isDispatched());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Security\Exception\InvalidHashException
	 */
	public function getReferringRequestThrowsAnExceptionIfTheHmacOfTheArgumentsCouldNotBeValid() {
		$this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnValue(array()));

		$serializedArguments = base64_encode('some manipulated arguments string without valid HMAC');
		$referrer = array(
			'@controller' => 'Foo',
			'@action' => 'bar',
			'arguments' => $serializedArguments
		);

		$mockHashService = $this->getMockBuilder('TYPO3\Flow\Security\Cryptography\HashService')->getMock();
		$mockHashService->expects($this->once())->method('validateAndStripHmac')->with($serializedArguments)->will($this->throwException(new InvalidHashException()));
		$this->inject($this->actionRequest, 'hashService', $mockHashService);

		$this->actionRequest->setArgument('__referrer', $referrer);

		$this->actionRequest->getReferringRequest();
	}

	/**
	 * @test
	 */
	public function setDispatchedEmitsSignalIfDispatched() {

		$mockDispatcher = $this->getMock('TYPO3\Flow\SignalSlot\Dispatcher');
		$mockDispatcher->expects($this->once())->method('dispatch')->with('TYPO3\Flow\Mvc\ActionRequest', 'requestDispatched', array($this->actionRequest));

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockDispatcher));
		$this->inject($this->actionRequest, 'objectManager', $mockObjectManager);

		$this->actionRequest->setDispatched(TRUE);
	}

	/**
	 * @test
	 */
	public function setControllerPackageKeyWithLowercasePackageKeyResolvesCorrectly() {
		$mockPackageManager = $this->getMock('TYPO3\Flow\Package\PackageManager');
		$mockPackageManager->expects($this->any())->method('getCaseSensitivePackageKey')->with('acme.testpackage')->will($this->returnValue('Acme.Testpackage'));

		$this->inject($this->actionRequest, 'packageManager', $mockPackageManager);
		$this->actionRequest->setControllerPackageKey('acme.testpackage');

		$this->assertEquals('Acme.Testpackage', $this->actionRequest->getControllerPackageKey());
	}

	/**
	 * @test
	 */
	public function getArgumentInitializesArguments() {
		/** @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject $actionRequest */
		$actionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->setMethods(array('initializeArguments'))->getMock();
		$actionRequest->expects($this->once())->method('initializeArguments');
		try {
			$actionRequest->getArgument('foo');
		} catch (NoSuchArgumentException $exception) {
		}
	}

	/**
	 * @test
	 */
	public function getArgumentsInitializesArguments() {
		/** @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject $actionRequest */
		$actionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->setMethods(array('initializeArguments'))->getMock();
		$actionRequest->expects($this->once())->method('initializeArguments');
		$actionRequest->getArguments();
	}

	/**
	 * @test
	 */
	public function getInternalArgumentInitializesArguments() {
		/** @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject $actionRequest */
		$actionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->setMethods(array('initializeArguments'))->getMock();
		$actionRequest->expects($this->once())->method('initializeArguments');
		try {
			$actionRequest->getInternalArgument('__foo');
		} catch (NoSuchArgumentException $exception) {
		}
	}

	/**
	 * @test
	 */
	public function getInternalArgumentsInitializesArguments() {
		/** @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject $actionRequest */
		$actionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->setMethods(array('initializeArguments'))->getMock();
		$actionRequest->expects($this->once())->method('initializeArguments');
		$actionRequest->getInternalArguments();
	}

	/**
	 * @test
	 */
	public function hasArgumentInitializesArguments() {
		/** @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject $actionRequest */
		$actionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->setMethods(array('initializeArguments'))->getMock();
		$actionRequest->expects($this->once())->method('initializeArguments');
		$actionRequest->hasArgument('foo');
	}

	/**
	 * @return array
	 */
	public function getArgumentsTestsDataProvider() {
		return array(
			array(
				'httpRequestArguments' => array(),
				'httpRequestBodyArguments' => array(),
				'actionRequestArguments' => array(),
				'expectedResult' => array()
			),
			array(
				'httpRequestArguments' => array('httpRequest' => 'arguments'),
				'httpRequestBodyArguments' => array(),
				'actionRequestArguments' => array(),
				'expectedResult' => array('httpRequest' => 'arguments')
			),
			array(
				'httpRequestArguments' => array(),
				'httpRequestBodyArguments' => array('httpRequestBody' => 'arguments'),
				'actionRequestArguments' => array(),
				'expectedResult' => array('httpRequestBody' => 'arguments')
			),
			array(
				'httpRequestArguments' => array(),
				'httpRequestBodyArguments' => array(),
				'actionRequestArguments' => array('actionRequestBody' => 'arguments'),
				'expectedResult' => array('actionRequestBody' => 'arguments')
			),
			array(
				'httpRequestArguments' => array('someArgument' => 'from Request'),
				'httpRequestBodyArguments' => array('someArgument' => 'overridden from body'),
				'actionRequestArguments' => array(),
				'expectedResult' => array('someArgument' => 'overridden from body')
			),
			array(
				'httpRequestArguments' => array('someArgument' => 'from Request'),
				'httpRequestBodyArguments' => array('someArgument' => 'overridden from body'),
				'actionRequestArguments' => array('someArgument' => 'overridden from ActionRequest'),
				'expectedResult' => array('someArgument' => 'overridden from ActionRequest')
			),
			array(
				'httpRequestArguments' => array('someHttpRequestArgument' => 'foo', 'otherArgument' => 'quux'),
				'httpRequestBodyArguments' => array(),
				'actionRequestArguments' => array('someActionRequestArgument' => 'bar', 'otherArgument' => 'shouldBeOverridden'),
				'expectedResult' => array('someHttpRequestArgument' => 'foo', 'otherArgument' => 'shouldBeOverridden', 'someActionRequestArgument' => 'bar')
			),
		);
	}

	/**
	 * @test
	 * @dataProvider getArgumentsTestsDataProvider
	 */
	public function getArgumentsTests(array $httpRequestArguments, array $httpRequestBodyArguments, array $actionRequestArguments, array $expectedResult) {
		$this->mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnValue($httpRequestBodyArguments));
		$this->mockHttpRequest->expects($this->once())->method('getArguments')->will($this->returnValue($httpRequestArguments));
		$this->actionRequest->setArguments($actionRequestArguments);

		$this->assertSame($expectedResult, $this->actionRequest->getArguments());
	}

	/**
	 * @test
	 */
	public function getInternalArgumentReturnsMergedArguments() {
		$this->mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnValue(array('__internalHttpRequestBody' => 'argument')));
		$this->mockHttpRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array('__internalHttpRequest' => 'argument')));
		$this->actionRequest->setArguments(array('__internalActionRequest' => 'argument'));

		$expectedResult = array('__internalHttpRequest' => 'argument', '__internalHttpRequestBody' => 'argument', '__internalActionRequest' => 'argument');
		$this->assertSame($expectedResult, $this->actionRequest->getInternalArguments());
	}

	/**
	 * @test
	 */
	public function internalArgumentsOfActionRequestOverruleThoseOfTheHttpRequest() {
		$this->mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnValue(array()));
		$this->mockHttpRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array('__internalArgument' => 'http request')));
		$this->actionRequest->setArguments(array('__internalArgument' => 'action request'));

		$expectedResult = array('__internalArgument' => 'action request');
		$this->assertSame($expectedResult, $this->actionRequest->getInternalArguments());
	}

	/**
	 * @test
	 */
	public function pluginArgumentsOfActionRequestOverruleThoseOfTheHttpRequest() {
		$this->mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnValue(array()));
		$this->mockHttpRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array('--pluginArgument' => 'http request')));
		$this->actionRequest->setArguments(array('--pluginArgument' => 'action request'));

		$expectedResult = array('pluginArgument' => 'action request');
		$this->assertSame($expectedResult, $this->actionRequest->getPluginArguments());
	}

	/**
	 * @test
	 */
	public function argumentsAreOnlyInitializedOnce() {
		$this->mockPropertyMapper->expects($this->once())->method('convert')->will($this->returnValue(array()));
		$this->mockHttpRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array()));
		$this->actionRequest->getArguments();
		$this->actionRequest->getArguments();
	}

	/**
	 * @test
	 */
	public function initializeArgumentsSetsMediaTypeInTheMediaTypeConverter() {
		/** @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject $actionRequest */
		$actionRequest = $this->getAccessibleMock('TYPO3\Flow\Mvc\ActionRequest', array('dummy'), array($this->mockHttpRequest));

		$this->inject($actionRequest, 'propertyMappingConfiguration', $this->mockPropertyMappingConfiguration);
		$this->inject($actionRequest, 'propertyMapper', $this->mockPropertyMapper);

		$this->mockHttpRequest->expects($this->atLeastOnce())->method('getHeader')->with('Content-Type')->will($this->returnValue('some/media-type'));
		$this->mockHttpRequest->expects($this->atLeastOnce())->method('getContent')->will($this->returnValue('some content'));
		$this->mockHttpRequest->expects($this->atLeastOnce())->method('getArguments')->will($this->returnValue(array()));

		$this->mockPropertyMappingConfiguration->expects($this->atLeastOnce())->method('setTypeConverterOption')->with('TYPO3\Flow\Property\TypeConverter\MediaTypeConverterInterface', MediaTypeConverterInterface::CONFIGURATION_MEDIA_TYPE, 'some/media-type');

		$this->mockPropertyMapper->expects($this->atLeastOnce())->method('convert')->with('some content', 'array', $this->mockPropertyMappingConfiguration)->will($this->returnValue(array()));

		$actionRequest->_call('initializeArguments');
	}

	/**
	 * @test
	 */
	public function argumentsAreInitializedOnSerialization() {
		$this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array('foo' => 'bar', 'bar' => 'baz')));
		$this->mockHttpRequest->expects($this->any())->method('getContent')->will($this->returnValue('foo=baz&bar=foos'));
		$this->mockPropertyMapper->expects($this->any())->method('convert')->with('foo=baz&bar=foos')->will($this->returnValue(array('bar' => 'overridden', 'baz' => 'Foos')));

		$serializedActionRequest = serialize($this->actionRequest);
		$unserializedActionRequest = unserialize($serializedActionRequest);

		$expectedResult = array('foo' => 'bar', 'bar' => 'overridden', 'baz' => 'Foos');
		$this->assertSame($expectedResult, $unserializedActionRequest->getArguments());
	}
}
