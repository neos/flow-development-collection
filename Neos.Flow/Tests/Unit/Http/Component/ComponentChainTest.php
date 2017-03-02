<?php
namespace Neos\Flow\Tests\Unit\Http\Component;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Http;

/**
 * Test case for the Http Component Chain
 */
class ComponentChainTest extends UnitTestCase
{
    /**
     * @var Http\Component\ComponentChain
     */
    protected $componentChain;

    /**
     * @var Http\Component\ComponentContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockComponentContext;

    public function setUp()
    {
        $this->mockComponentContext = $this->getMockBuilder(Http\Component\ComponentContext::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function handleReturnsIfNoComponentsAreConfigured()
    {
        $options = [];
        $this->componentChain = new Http\Component\ComponentChain($options);
        $this->componentChain->handle($this->mockComponentContext);

        // dummy assertion to silence PHPUnit warning
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function handleProcessesConfiguredComponents()
    {
        $mockComponent1 = $this->getMockBuilder(Http\Component\ComponentInterface::class)->getMock();
        $mockComponent1->expects($this->once())->method('handle')->with($this->mockComponentContext);
        $mockComponent2 = $this->getMockBuilder(Http\Component\ComponentInterface::class)->getMock();
        $mockComponent2->expects($this->once())->method('handle')->with($this->mockComponentContext);

        $options = ['components' => [$mockComponent1, $mockComponent2]];
        $this->componentChain = new Http\Component\ComponentChain($options);
        $this->componentChain->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleStopsProcessingIfAComponentCancelsTheCurrentChain()
    {
        $mockComponent1 = $this->getMockBuilder(Http\Component\ComponentInterface::class)->getMock();
        $mockComponent1->expects($this->once())->method('handle')->with($this->mockComponentContext);
        $mockComponent2 = $this->getMockBuilder(Http\Component\ComponentInterface::class)->getMock();
        $mockComponent2->expects($this->never())->method('handle');

        $this->mockComponentContext->expects($this->once())->method('getParameter')->with(Http\Component\ComponentChain::class, 'cancel')->will($this->returnValue(true));

        $options = ['components' => [$mockComponent1, $mockComponent2]];
        $this->componentChain = new Http\Component\ComponentChain($options);
        $this->componentChain->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleResetsTheCancelParameterIfItWasTrue()
    {
        $mockComponent1 = $this->getMockBuilder(Http\Component\ComponentInterface::class)->getMock();

        $this->mockComponentContext->expects($this->at(1))->method('getParameter')->with(Http\Component\ComponentChain::class, 'cancel')->will($this->returnValue(true));
        $this->mockComponentContext->expects($this->at(2))->method('setParameter')->with(Http\Component\ComponentChain::class, 'cancel', null);

        $options = ['components' => [$mockComponent1]];
        $this->componentChain = new Http\Component\ComponentChain($options);
        $this->componentChain->handle($this->mockComponentContext);
    }
}
