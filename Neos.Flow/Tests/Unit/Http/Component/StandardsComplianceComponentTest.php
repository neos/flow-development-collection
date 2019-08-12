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
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test case for the StandardsComplianceComponent
 */
class StandardsComplianceComponentTest extends UnitTestCase
{
    /**
     * @var Http\Component\StandardsComplianceComponent
     */
    protected $standardsComplianceComponent;

    /**
     * @var Http\Component\ComponentContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockComponentContext;

    /**
     * @var Http\Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var Http\Response|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockHttpResponse;

    protected function setUp(): void
    {
        $this->mockHttpRequest = $this->getMockBuilder(Http\Request::class)->disableOriginalConstructor()->getMock();
        $this->response = new Http\Response();

        $this->mockComponentContext = $this->getMockBuilder(Http\Component\ComponentContext::class)->disableOriginalConstructor()->getMock();
        $this->mockComponentContext->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));
        $this->mockComponentContext->expects($this->any())->method('getHttpResponse')->will($this->returnValue($this->response));

        $this->standardsComplianceComponent = new Http\Component\StandardsComplianceComponent([]);
    }

    /**
     * @test
     */
    public function handleCallsMakeStandardsCompliantOnTheCurrentResponse()
    {
        $this->mockComponentContext->expects(self::once())->method('replaceHttpResponse');
        $this->standardsComplianceComponent->handle($this->mockComponentContext);
    }
}
