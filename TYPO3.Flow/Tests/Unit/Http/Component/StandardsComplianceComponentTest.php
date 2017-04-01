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

use TYPO3\Flow\Http\Component\ComponentContext;
use TYPO3\Flow\Http\Component\StandardsComplianceComponent;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test case for the StandardsComplianceComponent
 */
class StandardsComplianceComponentTest extends UnitTestCase
{
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

    public function setUp()
    {
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
    public function handleCallsMakeStandardsCompliantOnTheCurrentResponse()
    {
        $this->mockHttpResponse->expects($this->once())->method('makeStandardsCompliant')->with($this->mockHttpRequest);

        $this->standardsComplianceComponent->handle($this->mockComponentContext);
    }
}
