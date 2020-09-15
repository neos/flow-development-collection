<?php
namespace Neos\Flow\Tests\Unit\Mvc\Routing;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Request;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Router;
use Neos\Flow\Mvc\Routing\RoutingComponent;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Http\Message\UriInterface;

/**
 * Testcase for the MVC RoutingComponent
 */
class RoutingComponentTest extends UnitTestCase
{
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
     * @var UriInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockRequestUri;

    /**
     * Sets up this test case
     *
     */
    public function setUp()
    {
        $this->routingComponent = new RoutingComponent([]);

        $this->mockRouter = $this->getMockBuilder(Router::class)->getMock();
        $this->mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->mockRouter, 'configurationManager', $this->mockConfigurationManager);

        $this->inject($this->routingComponent, 'router', $this->mockRouter);

        $this->mockComponentContext = $this->getMockBuilder(ComponentContext::class)->disableOriginalConstructor()->getMock();

        $this->mockHttpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->mockComponentContext->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));

        $this->mockRequestUri = $this->getMockBuilder(UriInterface::class)->getMock();
        $this->mockHttpRequest->expects($this->any())->method('getUri')->will($this->returnValue($this->mockRequestUri));
    }

    /**
     * @test
     */
    public function handleStoresRouterMatchResultsInTheComponentContext()
    {
        $mockMatchResults = ['someRouterMatchResults'];
        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());

        $this->mockRouter->expects($this->atLeastOnce())->method('route')->with($routeContext)->will($this->returnValue($mockMatchResults));
        $this->mockComponentContext->expects($this->atLeastOnce())->method('setParameter')->with(RoutingComponent::class, 'matchResults', $mockMatchResults);

        $this->routingComponent->handle($this->mockComponentContext);
    }
}
