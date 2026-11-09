<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Security\Authentication\EntryPoint;

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
use Neos\Flow\Security\Authentication\EntryPoint\HttpBasic;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Testcase for HTTP Basic Auth authentication entry point
 */
final class HttpBasicTest extends UnitTestCase
{
    #[Test]
    public function startAuthenticationSetsTheCorrectValuesInTheResponseObject()
    {
        $mockHttpRequest = $this->createStub(ServerRequestInterface::class);
        $mockResponse = new Response();

        $entryPoint = new HttpBasic();
        $entryPoint->setOptions(['realm' => 'realm string']);

        $mockResponse = $entryPoint->startAuthentication($mockHttpRequest, $mockResponse);

        $this->assertEquals(401, $mockResponse->getStatusCode());
        self::assertEquals('Basic realm="realm string"', $mockResponse->getHeaderLine('WWW-Authenticate'));
        $this->assertEquals('Authorization required', $mockResponse->getBody()->getContents());
    }
}
