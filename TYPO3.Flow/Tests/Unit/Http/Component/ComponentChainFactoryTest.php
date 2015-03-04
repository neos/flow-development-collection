<?php
namespace TYPO3\Flow\Tests\Unit\Http\Component;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Http\Component\ComponentChainFactory;
use TYPO3\Flow\Http\Component\ComponentInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test case for the Http Component Chain Factory
 */
class ComponentChainFactoryTest extends UnitTestCase {

	/**
	 * @var ComponentChainFactory
	 */
	protected $componentChainFactory;

	/**
	 * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockObjectManager;

	/**
	 * @var ComponentInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockComponent;

	public function setUp() {
		$this->componentChainFactory = new ComponentChainFactory();

		$this->mockObjectManager = $this->getMockBuilder('TYPO3\Flow\Object\ObjectManagerInterface')->getMock();
		$this->inject($this->componentChainFactory, 'objectManager', $this->mockObjectManager);

		$this->mockComponent = $this->getMockBuilder('TYPO3\Flow\Http\Component\ComponentInterface')->getMock();
	}

	/**
	 * @test
	 */
	public function createInitializesComponentsInTheRightOrderAccordingToThePositionDirective() {
		$chainConfiguration = array(
			'foo' => array(
				'component' => 'Foo\Component\ClassName',
			),
			'bar' => array(
				'component' => 'Bar\Component\ClassName',
				'position' => 'before foo',
			),
			'baz' => array(
				'component' => 'Baz\Component\ClassName',
				'position' => 'after bar'
			),
		);

		$this->mockObjectManager->expects($this->at(0))->method('get')->with('Bar\Component\ClassName')->will($this->returnValue($this->mockComponent));
		$this->mockObjectManager->expects($this->at(1))->method('get')->with('Baz\Component\ClassName')->will($this->returnValue($this->mockComponent));
		$this->mockObjectManager->expects($this->at(2))->method('get')->with('Foo\Component\ClassName')->will($this->returnValue($this->mockComponent));

		$this->componentChainFactory->create($chainConfiguration);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Http\Component\Exception
	 */
	public function createThrowsExceptionIfComponentClassNameIsNotConfigured() {
		$chainConfiguration = array(
			'foo' => array(
				'position' => 'start',
			),
		);

		$this->componentChainFactory->create($chainConfiguration);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Http\Component\Exception
	 */
	public function createThrowsExceptionIfComponentClassNameDoesNotImplementComponentInterface() {
		$chainConfiguration = array(
			'foo' => array(
				'component' => 'Foo\Component\ClassName',
			),
		);

		$this->mockObjectManager->expects($this->at(0))->method('get')->with('Foo\Component\ClassName')->will($this->returnValue(new \stdClass()));
		$this->componentChainFactory->create($chainConfiguration);
	}

}