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

use TYPO3\Flow\Http\Component\ComponentContext;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test case for the Http Component Context
 */
class ComponentContextTest extends UnitTestCase {

	/**
	 * @var ComponentContext
	 */
	protected $componentContext;

	/**
	 * @var Request|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockHttpRequest;

	/**
	 * @var Response|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockHttpResponse;

	public function setUp() {
		$this->mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$this->mockHttpResponse = $this->getMockBuilder('TYPO3\Flow\Http\Response')->disableOriginalConstructor()->getMock();

		$this->componentContext = new ComponentContext($this->mockHttpRequest, $this->mockHttpResponse);
	}

	/**
	 * @test
	 */
	public function getHttpRequestReturnsTheCurrentRequest() {
		$this->assertSame($this->mockHttpRequest, $this->componentContext->getHttpRequest());
	}

	/**
	 * @test
	 */
	public function replaceHttpRequestReplacesTheCurrentRequest() {
		/** @var Request $mockNewHttpRequest */
		$mockNewHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$this->componentContext->replaceHttpRequest($mockNewHttpRequest);
		$this->assertSame($mockNewHttpRequest, $this->componentContext->getHttpRequest());
	}

	/**
	 * @test
	 */
	public function getHttpResponseReturnsTheCurrentResponse() {
		$this->assertSame($this->mockHttpResponse, $this->componentContext->getHttpResponse());
	}

	/**
	 * @test
	 */
	public function replaceHttpResponseReplacesTheCurrentResponse() {
		/** @var Response $mockNewHttpResponse */
		$mockNewHttpResponse = $this->getMockBuilder('TYPO3\Flow\Http\Response')->disableOriginalConstructor()->getMock();
		$this->componentContext->replaceHttpResponse($mockNewHttpResponse);
		$this->assertSame($mockNewHttpResponse, $this->componentContext->getHttpResponse());
	}


	/**
	 * @test
	 */
	public function getParameterReturnsNullIfTheSpecifiedParameterIsNotDefined() {
		$this->assertNull($this->componentContext->getParameter('Some\Component\ClassName', 'nonExistingParameter'));
	}

	/**
	 * @test
	 */
	public function setParameterStoresTheGivenParameter() {
		$this->componentContext->setParameter('Some\Component\ClassName', 'someParameter', 'someParameterValue');
		$this->assertSame('someParameterValue', $this->componentContext->getParameter('Some\Component\ClassName', 'someParameter'));
	}

}