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
use Neos\Flow\Http\Middleware\StandardsComplianceMiddleware;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Test case for the StandardsComplianceMiddleware
 */
class StandardsComplianceMiddlewareTest extends UnitTestCase
{
    /**
     * @var StandardsComplianceMiddleware
     */
    protected $standardsComplianceMiddleware;

    /**
     * @var RequestHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockRequestHandler;

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
        $this->mockHttpResponse = new Response();

        $this->mockRequestHandler = $this->getMockBuilder(RequestHandlerInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockRequestHandler->method('handle')->willReturn($this->mockHttpResponse);

        $this->standardsComplianceMiddleware = new StandardsComplianceMiddleware([]);
    }

    /**
     * @test
     */
    public function handleCallsMakeStandardsCompliantOnTheCurrentResponse()
    {
        self::markTestSkipped('This test does not test anything at all');
        self::assertNotSame($this->mockHttpResponse, $this->standardsComplianceMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler));
    }
}
