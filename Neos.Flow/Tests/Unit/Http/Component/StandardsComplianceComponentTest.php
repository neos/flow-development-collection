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

use GuzzleHttp\Psr7\Response;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Component\StandardsComplianceComponent;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
     * @var ServerRequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var ResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockHttpResponse;

    protected function setUp(): void
    {
        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->response = new Response();

        $this->mockComponentContext = $this->getMockBuilder(ComponentContext::class)->disableOriginalConstructor()->getMock();
        $this->mockComponentContext->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));
        $this->mockComponentContext->expects($this->any())->method('getHttpResponse')->will($this->returnValue($this->response));

        $this->standardsComplianceComponent = new StandardsComplianceComponent([]);
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
