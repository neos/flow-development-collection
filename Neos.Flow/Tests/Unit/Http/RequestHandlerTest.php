<?php

namespace Neos\Flow\Tests\Unit\Http;

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
use Neos\Flow\Http\RequestHandler;
use Neos\Flow\Tests\Unit\Http\Fixtures\HeaderStack;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Test case for the Http RequestHandler
 */
class RequestHandlerTest extends UnitTestCase
{
    public static function setUpBeforeClass(): void
    {
        // replaces PHP's header() function for the RequestHandler
        require_once __DIR__ . '/Fixtures/HeaderFunction.php';
    }

    protected function setUp(): void
    {
        HeaderStack::reset();
    }

    protected function tearDown(): void
    {
        HeaderStack::reset();
    }

    /**
     * @test
     */
    public function sendHeadersSendsTheStatusLineAfterAllOtherHeaders(): void
    {
        $headers = ['WWW-Authenticate' => 'Bearer', 'Set-Cookie' => ['a=1', 'b=2']];
        $response = new Response(403, $headers, null, '1.1', 'Custom Reason');

        $this->sendHeaders($response);

        self::assertSame([
            ['header' => 'WWW-Authenticate: Bearer', 'replace' => false, 'responseCode' => 0],
            ['header' => 'Set-Cookie: a=1', 'replace' => false, 'responseCode' => 0],
            ['header' => 'Set-Cookie: b=2', 'replace' => false, 'responseCode' => 0],
            ['header' => 'HTTP/1.1 403 Custom Reason', 'replace' => true, 'responseCode' => 403],
        ], HeaderStack::stack());
    }

    /**
     * PHP turns the status into a 302 for a "Location" header, unless the status line is sent afterwards
     *
     * @test
     */
    public function sendHeadersSendsTheStatusLineAfterALocationHeader(): void
    {
        $response = new Response(202, ['Location' => '/jobs/1']);

        $this->sendHeaders($response);

        self::assertSame(['Location: /jobs/1', 'HTTP/1.1 202 Accepted'], array_column(HeaderStack::stack(), 'header'));
        self::assertSame(202, HeaderStack::stack()[1]['responseCode']);
    }

    /**
     * Calls the protected RequestHandler::sendHeaders()
     */
    private function sendHeaders(ResponseInterface $response): void
    {
        $requestHandler = (new \ReflectionClass(RequestHandler::class))->newInstanceWithoutConstructor();
        (function () use ($response) {
            $this->sendHeaders($response);
        })->call($requestHandler);
    }
}
