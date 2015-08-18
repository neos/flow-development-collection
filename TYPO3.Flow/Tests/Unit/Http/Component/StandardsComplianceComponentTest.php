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
use TYPO3\Flow\Http\Component\StandardsComplianceComponent;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test case for the StandardsComplianceComponent
 */
class StandardsComplianceComponentTest extends UnitTestCase {

	/**
	 * @var StandardsComplianceComponent
	 */
	protected $standardsComplianceComponent;

	/**
	 * @var ComponentContext|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockComponentContext;

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

		$this->mockComponentContext = $this->getMockBuilder('TYPO3\Flow\Http\Component\ComponentContext')->disableOriginalConstructor()->getMock();
		$this->mockComponentContext->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));
		$this->mockComponentContext->expects($this->any())->method('getHttpResponse')->will($this->returnValue($this->mockHttpResponse));

		$this->standardsComplianceComponent = new StandardsComplianceComponent(array());
	}

	/**
	 * @test
	 */
	public function handleCallsMakeStandardsCompliantOnTheCurrentResponse() {
		$this->mockHttpResponse->expects($this->once())->method('makeStandardsCompliant')->with($this->mockHttpRequest);

		$this->standardsComplianceComponent->handle($this->mockComponentContext);
	}

}