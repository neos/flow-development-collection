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
     * @var Http\Component\ComponentContext|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockComponentContext;

    protected function setUp(): void
    {
        $this->mockComponentContext = $this->getMockBuilder(Http\Component\ComponentContext::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function handleReturnsIfNoComponentsAreConfigured()
    {
        $options = [];
        $this->componentChain = new Http\Component\ComponentChain($options);
        $this->componentChain->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleProcessesConfiguredComponents()
    {
        $mockComponent1 = $this->getMockBuilder(Http\Component\ComponentInterface::class)->getMock();
        $mockComponent1->expects(self::once())->method('handle')->with($this->mockComponentContext);
        $mockComponent2 = $this->getMockBuilder(Http\Component\ComponentInterface::class)->getMock();
        $mockComponent2->expects(self::once())->method('handle')->with($this->mockComponentContext);

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
        $mockComponent1->expects(self::once())->method('handle')->with($this->mockComponentContext);
        $mockComponent2 = $this->getMockBuilder(Http\Component\ComponentInterface::class)->getMock();
        $mockComponent2->expects(self::never())->method('handle');

        $this->mockComponentContext->expects(self::once())->method('getParameter')->with(Http\Component\ComponentChain::class, 'cancel')->will(self::returnValue(true));

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

        $this->mockComponentContext->expects(self::at(1))->method('getParameter')->with(Http\Component\ComponentChain::class, 'cancel')->will(self::returnValue(true));
        $this->mockComponentContext->expects(self::at(2))->method('setParameter')->with(Http\Component\ComponentChain::class, 'cancel', null);

        $options = ['components' => [$mockComponent1]];
        $this->componentChain = new Http\Component\ComponentChain($options);
        $this->componentChain->handle($this->mockComponentContext);
    }
}
