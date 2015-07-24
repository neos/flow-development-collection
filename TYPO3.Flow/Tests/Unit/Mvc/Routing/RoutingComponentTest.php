<?php
namespace TYPO3\Flow\Tests\Unit\Mvc\Routing;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Http\Component\ComponentContext;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Mvc\Routing\Router;
use TYPO3\Flow\Mvc\Routing\RoutingComponent;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the MVC RoutingComponent
 */
class RoutingComponentTest extends UnitTestCase {

	/**
	 * @var RoutingComponent
	 */
	protected $routingComponent;

	/**
	 * @var Router|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockRouter;

	/**
	 * @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockConfigurationManager;

	/**
	 * @var ComponentContext|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockComponentContext;

	/**
	 * @var Request|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockHttpRequest;

	/**
	 * Sets up this test case
	 *
	 */
	public function setUp() {
		$this->routingComponent = new RoutingComponent(array());

		$this->mockRouter = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Router')->getMock();
		$this->inject($this->routingComponent, 'router', $this->mockRouter);

		$this->mockConfigurationManager = $this->getMockBuilder('TYPO3\Flow\Configuration\ConfigurationManager')->disableOriginalConstructor()->getMock();
		$this->inject($this->routingComponent, 'configurationManager', $this->mockConfigurationManager);

		$this->mockComponentContext = $this->getMockBuilder('TYPO3\Flow\Http\Component\ComponentContext')->disableOriginalConstructor()->getMock();

		$this->mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$this->mockComponentContext->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));
	}

	/**
	 * @test
	 */
	public function handleInitializesRouterByDefault() {
		$mockRoutesConfiguration = array('someRoutingConfiguration');
		$this->mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_ROUTES)->will($this->returnValue($mockRoutesConfiguration));
		$this->mockRouter->expects($this->atLeastOnce())->method('setRoutesConfiguration')->with($mockRoutesConfiguration);

		$this->routingComponent->handle($this->mockComponentContext);
	}

	/**
	 * @test
	 */
	public function handleDoesNotInitializeRouterIfTheSkipRouterInitializationParameterIsSet() {
		$this->mockComponentContext->expects($this->atLeastOnce())->method('getParameter')->with('TYPO3\Flow\Mvc\Routing\RoutingComponent', 'skipRouterInitialization')->will($this->returnValue(TRUE));

		$this->mockConfigurationManager->expects($this->never())->method('getConfiguration');
		$this->mockRouter->expects($this->never())->method('setRoutesConfiguration');

		$this->routingComponent->handle($this->mockComponentContext);
	}

	/**
	 * @test
	 */
	public function handleStoresRouterMatchResultsInTheComponentContext() {
		$mockMatchResults = array('someRouterMatchResults');

		$this->mockConfigurationManager->expects($this->atLeastOnce())->method('getConfiguration')->will($this->returnValue(array()));

		$this->mockRouter->expects($this->atLeastOnce())->method('route')->with($this->mockHttpRequest)->will($this->returnValue($mockMatchResults));
		$this->mockComponentContext->expects($this->atLeastOnce())->method('setParameter')->with('TYPO3\Flow\Mvc\Routing\RoutingComponent', 'matchResults', $mockMatchResults);

		$this->routingComponent->handle($this->mockComponentContext);
	}

}