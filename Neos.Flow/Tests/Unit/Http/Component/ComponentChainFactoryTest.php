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

use Neos\Flow\Http;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test case for the Http Component Chain Factory
 */
class ComponentChainFactoryTest extends UnitTestCase
{
    /**
     * @var Http\Component\ComponentChainFactory
     */
    protected $componentChainFactory;

    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockObjectManager;

    /**
     * @var Http\Component\ComponentInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockComponent;

    public function setUp()
    {
        $this->componentChainFactory = new Http\Component\ComponentChainFactory();

        $this->mockObjectManager = $this->getMockBuilder(ObjectManagerInterface::class)->getMock();
        $this->inject($this->componentChainFactory, 'objectManager', $this->mockObjectManager);

        $this->mockComponent = $this->getMockBuilder(Http\Component\ComponentInterface::class)->getMock();
    }

    /**
     * @test
     */
    public function createInitializesComponentsInTheRightOrderAccordingToThePositionDirective()
    {
        $chainConfiguration = [
            'foo' => [
                'component' => 'Foo\Component\ClassName',
            ],
            'bar' => [
                'component' => 'Bar\Component\ClassName',
                'position' => 'before foo',
            ],
            'baz' => [
                'component' => 'Baz\Component\ClassName',
                'position' => 'after bar'
            ],
        ];

        $this->mockObjectManager->expects($this->at(0))->method('get')->with('Bar\Component\ClassName')->will($this->returnValue($this->mockComponent));
        $this->mockObjectManager->expects($this->at(1))->method('get')->with('Baz\Component\ClassName')->will($this->returnValue($this->mockComponent));
        $this->mockObjectManager->expects($this->at(2))->method('get')->with('Foo\Component\ClassName')->will($this->returnValue($this->mockComponent));

        $this->componentChainFactory->create($chainConfiguration);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Http\Component\Exception
     */
    public function createThrowsExceptionIfComponentClassNameIsNotConfigured()
    {
        $chainConfiguration = [
            'foo' => [
                'position' => 'start',
            ],
        ];

        $this->componentChainFactory->create($chainConfiguration);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Http\Component\Exception
     */
    public function createThrowsExceptionIfComponentClassNameDoesNotImplementComponentInterface()
    {
        $chainConfiguration = [
            'foo' => [
                'component' => 'Foo\Component\ClassName',
            ],
        ];

        $this->mockObjectManager->expects($this->at(0))->method('get')->with('Foo\Component\ClassName')->will($this->returnValue(new \stdClass()));
        $this->componentChainFactory->create($chainConfiguration);
    }
}
