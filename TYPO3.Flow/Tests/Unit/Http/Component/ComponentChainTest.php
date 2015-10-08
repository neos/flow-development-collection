<?php
namespace TYPO3\Flow\Tests\Unit\Http\Component;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Http\Component\ComponentChain;
use TYPO3\Flow\Http\Component\ComponentContext;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test case for the Http Component Chain
 */
class ComponentChainTest extends UnitTestCase
{
    /**
     * @var ComponentChain
     */
    protected $componentChain;

    /**
     * @var ComponentContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockComponentContext;

    public function setUp()
    {
        $this->mockComponentContext = $this->getMockBuilder(\TYPO3\Flow\Http\Component\ComponentContext::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function handleReturnsIfNoComponentsAreConfigured()
    {
        $options = array();
        $this->componentChain = new ComponentChain($options);
        $this->componentChain->handle($this->mockComponentContext);

        // dummy assertion to silence PHPUnit warning
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function handleProcessesConfiguredComponents()
    {
        $mockComponent1 = $this->getMockBuilder(\TYPO3\Flow\Http\Component\ComponentInterface::class)->getMock();
        $mockComponent1->expects($this->once())->method('handle')->with($this->mockComponentContext);
        $mockComponent2 = $this->getMockBuilder(\TYPO3\Flow\Http\Component\ComponentInterface::class)->getMock();
        $mockComponent2->expects($this->once())->method('handle')->with($this->mockComponentContext);

        $options = array('components' => array($mockComponent1, $mockComponent2));
        $this->componentChain = new ComponentChain($options);
        $this->componentChain->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleStopsProcessingIfAComponentCancelsTheCurrentChain()
    {
        $mockComponent1 = $this->getMockBuilder(\TYPO3\Flow\Http\Component\ComponentInterface::class)->getMock();
        $mockComponent1->expects($this->once())->method('handle')->with($this->mockComponentContext);
        $mockComponent2 = $this->getMockBuilder(\TYPO3\Flow\Http\Component\ComponentInterface::class)->getMock();
        $mockComponent2->expects($this->never())->method('handle');

        $this->mockComponentContext->expects($this->once())->method('getParameter')->with(\TYPO3\Flow\Http\Component\ComponentChain::class, 'cancel')->will($this->returnValue(true));

        $options = array('components' => array($mockComponent1, $mockComponent2));
        $this->componentChain = new ComponentChain($options);
        $this->componentChain->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleResetsTheCancelParameterIfItWasTrue()
    {
        $mockComponent1 = $this->getMockBuilder(\TYPO3\Flow\Http\Component\ComponentInterface::class)->getMock();

        $this->mockComponentContext->expects($this->at(0))->method('getParameter')->with(\TYPO3\Flow\Http\Component\ComponentChain::class, 'cancel')->will($this->returnValue(true));
        $this->mockComponentContext->expects($this->at(1))->method('setParameter')->with(\TYPO3\Flow\Http\Component\ComponentChain::class, 'cancel', null);

        $options = array('components' => array($mockComponent1));
        $this->componentChain = new ComponentChain($options);
        $this->componentChain->handle($this->mockComponentContext);
    }
}
