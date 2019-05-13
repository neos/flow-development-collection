<?php
namespace Neos\Flow\Tests\Unit\Mvc;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\DispatchComponent;
use Neos\Flow\Mvc\Dispatcher;
use Neos\Flow\Mvc\Routing\RoutingComponent;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\MediaTypeConverterInterface;
use Neos\Flow\Security;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Test case for the MVC Dispatcher Component
 */
class DispatchComponentTest extends UnitTestCase
{
    /**
     * @var DispatchComponent
     */
    protected $dispatchComponent;

    /**
     * @var Security\Context|\PHPUnit_Framework_MockObject_MockObject
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
    public function setUp()
    {
        $this->dispatchComponent = new DispatchComponent();

        $this->mockComponentContext = $this->getMockBuilder(ComponentContext::class)->disableOriginalConstructor()->getMock();

        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpRequest->expects($this->any())->method('withParsedBody')->willReturn($this->mockHttpRequest);
        $this->mockComponentContext->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));

        $httpResponse = new Response();
        $this->mockComponentContext->expects($this->any())->method('getHttpResponse')->willReturn($httpResponse);

        $this->mockDispatcher = $this->getMockBuilder(Dispatcher::class)->getMock();
        $this->inject($this->dispatchComponent, 'dispatcher', $this->mockDispatcher);

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
//
//        $mockMediaTypeConverter = $this->createMock(MediaTypeConverterInterface::class);
//        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
//        $this->mockObjectManager->expects($this->any())->method('get')->willReturnCallback(function ($className) use ($mockMediaTypeConverter) {
//            switch ($className) {
//                case ActionRequest::class:
//                    return $this->mockActionRequest;
//                case MediaTypeConverterInterface::class:
//                    return $mockMediaTypeConverter;
//            }
//
//            return null;
//        });

//        $this->inject($this->dispatchComponent, 'objectManager', $this->mockObjectManager);

//        $this->mockSecurityContext = $this->getMockBuilder(Security\Context::class)->getMock();
//        $this->inject($this->dispatchComponent, 'securityContext', $this->mockSecurityContext);
//
//        $this->mockPropertyMapper = $this->getMockBuilder(PropertyMapper::class)->disableOriginalConstructor()->getMock();
//        $this->inject($this->dispatchComponent, 'propertyMapper', $this->mockPropertyMapper);
    }

    /**
     * @test
     */
    public function handleDispatchesTheRequest()
    {
        $this->mockDispatcher->expects($this->once())->method('dispatch')->with($this->mockActionRequest);

        $componentContext = new ComponentContext($this->mockHttpRequest, new Response());
        $componentContext->setParameter(RoutingComponent::class, 'matchResults', []);
        $componentContext->setParameter(DispatchComponent::class, 'actionRequest', $this->mockActionRequest);
        $this->dispatchComponent->handle($componentContext);
        $this->assertInstanceOf(ResponseInterface::class, $componentContext->getHttpResponse());
    }
}
