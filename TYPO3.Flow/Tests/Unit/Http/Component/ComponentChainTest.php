<?php
namespace TYPO3\Flow\Tests\Unit\Http\Component;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
        $mockComponent1 = $this->createMock(\TYPO3\Flow\Http\Component\ComponentInterface::class);
        $mockComponent1->expects($this->once())->method('handle')->with($this->mockComponentContext);
        $mockComponent2 = $this->createMock(\TYPO3\Flow\Http\Component\ComponentInterface::class);
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
        $mockComponent1 = $this->createMock(\TYPO3\Flow\Http\Component\ComponentInterface::class);
        $mockComponent1->expects($this->once())->method('handle')->with($this->mockComponentContext);
        $mockComponent2 = $this->createMock(\TYPO3\Flow\Http\Component\ComponentInterface::class);
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
        $mockComponent1 = $this->createMock(\TYPO3\Flow\Http\Component\ComponentInterface::class);

        $this->mockComponentContext->expects($this->at(0))->method('getParameter')->with(\TYPO3\Flow\Http\Component\ComponentChain::class, 'cancel')->will($this->returnValue(true));
        $this->mockComponentContext->expects($this->at(1))->method('setParameter')->with(\TYPO3\Flow\Http\Component\ComponentChain::class, 'cancel', null);

        $options = array('components' => array($mockComponent1));
        $this->componentChain = new ComponentChain($options);
        $this->componentChain->handle($this->mockComponentContext);
    }
}
