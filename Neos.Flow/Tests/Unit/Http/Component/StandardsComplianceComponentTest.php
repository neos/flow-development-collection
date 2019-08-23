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
     * @var ComponentContext|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockComponentContext;

    /**
     * @var ServerRequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var ResponseInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockHttpResponse;

    protected function setUp(): void
    {
        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->response = new Response();

        $this->mockComponentContext = $this->getMockBuilder(ComponentContext::class)->disableOriginalConstructor()->getMock();
        $this->mockComponentContext->expects(self::any())->method('getHttpRequest')->will(self::returnValue($this->mockHttpRequest));
        $this->mockComponentContext->expects(self::any())->method('getHttpResponse')->will(self::returnValue($this->response));

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
