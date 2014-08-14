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

use TYPO3\Flow\Http\Component\ComponentContext;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\DispatchComponent;
use TYPO3\Flow\Mvc\Dispatcher;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Property\PropertyMappingConfiguration;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test case for the MVC Dispatcher Component
 */
class DispatchComponentTest extends UnitTestCase {

	/**
	 * @var DispatchComponent
	 */
	protected $dispatchComponent;

	/**
	 * @var Context|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockSecurityContext;

	/**
	 * @var ComponentContext|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockComponentContext;

	/**
	 * @var Request|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockHttpRequest;

	/**
	 * @var Response|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockHttpResponse;

	/**
	 * @var Dispatcher|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockDispatcher;

	/**
	 * @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockActionRequest;

	/**
	 * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockObjectManager;

	/**
	 * @var PropertyMapper|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockPropertyMapper;

	/**
	 * @var PropertyMappingConfiguration|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockPropertyMappingConfiguration;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->dispatchComponent = new DispatchComponent();

		$this->mockComponentContext = $this->getMockBuilder('TYPO3\Flow\Http\Component\ComponentContext')->disableOriginalConstructor()->getMock();

		$this->mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$this->mockComponentContext->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));

		$this->mockHttpResponse = $this->getMockBuilder('TYPO3\Flow\Http\Response')->disableOriginalConstructor()->getMock();
		$this->mockComponentContext->expects($this->any())->method('getHttpResponse')->will($this->returnValue($this->mockHttpResponse));

		$this->mockDispatcher = $this->getMockBuilder('TYPO3\Flow\Mvc\Dispatcher')->getMock();
		$this->inject($this->dispatchComponent, 'dispatcher', $this->mockDispatcher);

		$this->mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();

		$this->mockObjectManager = $this->getMockBuilder('TYPO3\Flow\Object\ObjectManagerInterface')->getMock();
		$this->mockObjectManager->expects($this->any())->method('get')->with('TYPO3\Flow\Mvc\ActionRequest', $this->mockHttpRequest)->will($this->returnValue($this->mockActionRequest));
		$this->inject($this->dispatchComponent, 'objectManager', $this->mockObjectManager);

		$this->mockSecurityContext = $this->getMockBuilder('TYPO3\Flow\Security\Context')->getMock();
		$this->inject($this->dispatchComponent, 'securityContext', $this->mockSecurityContext);

		$this->mockPropertyMappingConfiguration = $this->getMockBuilder('TYPO3\Flow\Property\PropertyMappingConfiguration')->disableOriginalConstructor()->getMock();
		$this->inject($this->dispatchComponent, 'propertyMappingConfiguration', $this->mockPropertyMappingConfiguration);

		$this->mockPropertyMapper = $this->getMockBuilder('TYPO3\Flow\Property\PropertyMapper')->disableOriginalConstructor()->getMock();
		$this->inject($this->dispatchComponent, 'propertyMapper', $this->mockPropertyMapper);
	}

	/**
	 * @test
	 */
	public function handleSetsRequestInSecurityContext() {
		$this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockPropertyMapper->expects($this->any())->method('convert')->with('', 'array', $this->mockPropertyMappingConfiguration)->will($this->returnValue(array()));
		$this->mockSecurityContext->expects($this->once())->method('setRequest')->with($this->mockActionRequest);

		$this->dispatchComponent->handle($this->mockComponentContext);
	}

	/**
	 * @test
	 */
	public function handleSetsDefaultControllerAndActionNameIfTheyAreNotSetYet() {
		$this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockPropertyMapper->expects($this->any())->method('convert')->with('', 'array', $this->mockPropertyMappingConfiguration)->will($this->returnValue(array()));

		$this->mockActionRequest->expects($this->once())->method('getControllerName')->will($this->returnValue(NULL));
		$this->mockActionRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue(NULL));
		$this->mockActionRequest->expects($this->once())->method('setControllerName')->with('Standard');
		$this->mockActionRequest->expects($this->once())->method('setControllerActionName')->with('index');

		$this->dispatchComponent->handle($this->mockComponentContext);
	}

	/**
	 * @test
	 */
	public function handleDoesNotSetDefaultControllerAndActionNameIfTheyAreSetAlready() {
		$this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockPropertyMapper->expects($this->any())->method('convert')->with('', 'array', $this->mockPropertyMappingConfiguration)->will($this->returnValue(array()));

		$this->mockActionRequest->expects($this->once())->method('getControllerName')->will($this->returnValue('SomeController'));
		$this->mockActionRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('someAction'));
		$this->mockActionRequest->expects($this->never())->method('setControllerName');
		$this->mockActionRequest->expects($this->never())->method('setControllerActionName');

		$this->dispatchComponent->handle($this->mockComponentContext);
	}

	/**
	 * @test
	 */
	public function handleSetsActionRequestArgumentsIfARouteMatches() {
		$this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockPropertyMapper->expects($this->any())->method('convert')->with('', 'array', $this->mockPropertyMappingConfiguration)->will($this->returnValue(array()));

		$matchResults = array(
			'product' => array('name' => 'Some product', 'price' => 123.45),
			'toBeOverridden' => 'from route',
			'newValue' => 'new value from route'
		);

		$this->mockActionRequest->expects($this->once())->method('setArguments')->with($matchResults);
		$this->mockComponentContext->expects($this->atLeastOnce())->method('getParameter')->with('TYPO3\Flow\Mvc\Routing\RoutingComponent', 'matchResults')->will($this->returnValue($matchResults));
		$this->dispatchComponent->handle($this->mockComponentContext);
	}

	/**
	 * @test
	 */
	public function handleDispatchesTheRequest() {
		$this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockPropertyMapper->expects($this->any())->method('convert')->with('', 'array', $this->mockPropertyMappingConfiguration)->will($this->returnValue(array()));

		$this->mockDispatcher->expects($this->once())->method('dispatch')->with($this->mockActionRequest, $this->mockHttpResponse);

		$this->dispatchComponent->handle($this->mockComponentContext);
	}

	/**
	 * @test
	 */
	public function handleStoresTheActionRequestInTheComponentContext() {
		$this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockPropertyMapper->expects($this->any())->method('convert')->with('', 'array', $this->mockPropertyMappingConfiguration)->will($this->returnValue(array()));

		$this->mockComponentContext->expects($this->atLeastOnce())->method('setParameter')->with('TYPO3\Flow\Mvc\DispatchComponent', 'actionRequest', $this->mockActionRequest);

		$this->dispatchComponent->handle($this->mockComponentContext);
	}

	/**
	 * @return array
	 */
	public function handleMergesArgumentsWithRoutingMatchResultsDataProvider() {
		return array(
			array(
				'requestArguments' => array(),
				'requestBodyArguments' => array(),
				'routingMatchResults' => NULL,
				'expectedArguments' => array()
			),
			array(
				'requestArguments' => array(),
				'requestBodyArguments' => array('bodyArgument' => 'foo'),
				'routingMatchResults' => NULL,
				'expectedArguments' => array('bodyArgument' => 'foo')
			),
			array(
				'requestArguments' => array('requestArgument' => 'bar'),
				'requestBodyArguments' => array('bodyArgument' => 'foo'),
				'routingMatchResults' => NULL,
				'expectedArguments' => array('bodyArgument' => 'foo', 'requestArgument' => 'bar')
			),
			array(
				'requestArguments' => array('someArgument' => 'foo'),
				'requestBodyArguments' => array('someArgument' => 'overridden'),
				'routingMatchResults' => array(),
				'expectedArguments' => array('someArgument' => 'overridden')
			),
			array(
				'requestArguments' => array('product' => array('property1' => 'request', 'property2' => 'request', 'property3' => 'request')),
				'requestBodyArguments' => array('product' => array('property2' => 'requestBody', 'property3' => 'requestBody')),
				'routingMatchResults' => array('product' => array('property3' => 'routing')),
				'expectedArguments' => array('product' => array('property1' => 'request', 'property2' => 'requestBody', 'property3' => 'routing'))
			),
			array(
				'requestArguments' => array(),
				'requestBodyArguments' => array('someObject' => array('someProperty' => 'someValue')),
				'routingMatchResults' => array('someObject' => array('__identity' => 'someIdentifier')),
				'expectedArguments' => array('someObject' => array('someProperty' => 'someValue', '__identity' => 'someIdentifier'))
			),
		);
	}

	/**
	 * @test
	 * @dataProvider handleMergesArgumentsWithRoutingMatchResultsDataProvider()
	 */
	public function handleMergesArgumentsWithRoutingMatchResults(array $requestArguments, array $requestBodyArguments, array $routingMatchResults = NULL, array $expectedArguments) {
		$this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue($requestArguments));
		$this->mockPropertyMapper->expects($this->any())->method('convert')->with('', 'array', $this->mockPropertyMappingConfiguration)->will($this->returnValue($requestBodyArguments));
		$this->mockComponentContext->expects($this->atLeastOnce())->method('getParameter')->with('TYPO3\Flow\Mvc\Routing\RoutingComponent', 'matchResults')->will($this->returnValue($routingMatchResults));

		$this->mockActionRequest->expects($this->once())->method('setArguments')->with($expectedArguments);

		$this->dispatchComponent->handle($this->mockComponentContext);
	}

	/**
	 * @test
	 */
	public function handleMergesInternalArgumentsWithRoutingMatchResults() {
		$this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array('__internalArgument1' => 'request', '__internalArgument2' => 'request', '__internalArgument3' => 'request')));
		$this->mockPropertyMapper->expects($this->any())->method('convert')->with('', 'array', $this->mockPropertyMappingConfiguration)->will($this->returnValue(array('__internalArgument2' => 'requestBody', '__internalArgument3' => 'requestBody')));
		$this->mockComponentContext->expects($this->atLeastOnce())->method('getParameter')->with('TYPO3\Flow\Mvc\Routing\RoutingComponent', 'matchResults')->will($this->returnValue(array('__internalArgument3' => 'routing')));

		$this->mockActionRequest->expects($this->once())->method('setArguments')->with(array('__internalArgument1' => 'request', '__internalArgument2' => 'requestBody', '__internalArgument3' => 'routing'));

		$this->dispatchComponent->handle($this->mockComponentContext);
	}
}