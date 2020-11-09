<?php
namespace Neos\Flow\Tests\Unit\Http\Middleware;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\Unit\Http\Fixtures\SpyRequestHandler;
use Psr\Http\Server\RequestHandlerInterface;
use Neos\Flow\Http\Middleware\TrustedProxiesMiddleware;
use Neos\Flow\Http\ServerRequestAttributes;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Http\Factories\ServerRequestFactory;
use Neos\Http\Factories\UriFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Test case for the TrustedProxiesMiddleware
 */
class TrustedProxiesMiddlewareTest extends UnitTestCase
{
    /**
     * @var TrustedProxiesMiddleware
     */
    protected $trustedProxiesMiddleware;

    /**
     * @var \ReflectionProperty
     */
    protected $trustedProxiesSettings;

    /**
     * @var ServerRequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var ResponseInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockHttpResponse;

    /**
     * @var RequestHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockHttpRequestHandler;

    /**
     * @var ServerRequestFactoryInterface
     */
    protected $serverRequestFactory;

    protected function setUp(): void
    {
        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpResponse = $this->getMockBuilder(ResponseInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpRequestHandler = $this->getMockBuilder(RequestHandlerInterface::class)->disableOriginalConstructor()->getMock();

        $this->serverRequestFactory = new ServerRequestFactory(new UriFactory());
        $this->trustedProxiesMiddleware = new TrustedProxiesMiddleware();
        $middlewareReflection = new \ReflectionClass($this->trustedProxiesMiddleware);
        $this->trustedProxiesSettings = $middlewareReflection->getProperty('settings');
        $this->trustedProxiesSettings->setAccessible(true);
        $this->withTrustedProxiesSettings([
            'proxies' => '*',
            'headers' => [
                'clientIp' => 'Client-Ip,X-Forwarded-For,X-Forwarded,X-Cluster-Client-Ip,Forwarded-For,Forwarded',
                'host' => 'X-Forwarded-Host',
                'port' => 'X-Forwarded-Port',
                'proto' => 'X-Forwarded-Proto',
            ]
        ]);
    }

    /**
     * @param array $settings
     */
    protected function withTrustedProxiesSettings(array $settings)
    {
        $this->trustedProxiesSettings->setValue($this->trustedProxiesMiddleware, $settings);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    protected function callWithRequest($request)
    {
        $spyRequestHandler = new SpyRequestHandler();
        $this->trustedProxiesMiddleware->process($request, $spyRequestHandler);
        return $spyRequestHandler->getHandledRequest();
    }

    /**
     * RFC 2616 / 14.23 (Host)
     * @test
     * @backupGlobals disabled
     */
    public function portInProxyHeaderIsAcknowledged()
    {
        $server = array_merge($_SERVER, [
            'HTTP_HOST' => 'dev.blog.rob',
            'HTTP_X_FORWARDED_PORT' => 2727,
            'SERVER_NAME' => 'dev.blog.rob',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_PORT' => '80',
            'REMOTE_ADDR' => '127.0.0.1',
            'REQUEST_URI' => '/posts/2011/11/28/laboriosam-soluta-est-minus-molestiae?getKey1=getValue1&getKey2=getValue2',
        ]);

        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://dev.blog.rob/foo/bar?baz=quux&coffee=due'), $server);
        $trustedRequest = $this->callWithRequest($request);
        self::assertSame(2727, $trustedRequest->getUri()->getPort());
    }

    /**
     * RFC 2616 / 14.23 (Host)
     *
     * @test_disabled
     * @backupGlobals disabled
     */
    public function portInProxyHeaderIsAcknowledgedWithIpv6()
    {
        $server = array_merge($_SERVER, [
            'HTTP_HOST' => '[2a00:f48:1008::212:183:10]',
            'HTTP_X_FORWARDED_HOST' => '[2a00:f48:1008::212:183:10]',
            'HTTP_X_FORWARDED_PORT' => 2727,
            'SERVER_NAME' => 'dev.blog.rob',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_PORT' => '80',
            'REMOTE_ADDR' => '127.0.0.1',
            'REQUEST_URI' => '/posts/2011/11/28/laboriosam-soluta-est-minus-molestiae?getKey1=getValue1&getKey2=getValue2',
        ]);

        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://[2a00:f48:1008::212:183:10]:2727/foo/bar?baz=quux&coffee=due'), $server);
        $trustedRequest = $this->callWithRequest($request);
        self::assertSame(2727, $trustedRequest->getUri()->getPort());
    }

    /**
     * Data Provider
     */
    public function serverEnvironmentsForClientIpAddresses()
    {
        return [
            [[], '17.172.224.47'],
            [['HTTP_CLIENT_IP' => 'murks'], '17.172.224.47'],
            [['HTTP_CLIENT_IP' => '17.149.160.49'], '17.149.160.49'],
            [['HTTP_CLIENT_IP' => '17.149.160.49', 'HTTP_X_FORWARDED_FOR' => '123.123.123.123'], '17.149.160.49'],
            [['HTTP_X_FORWARDED_FOR' => '123.123.123.123'], '123.123.123.123'],
            [['HTTP_X_FORWARDED_FOR' => '123.123.123.123', 'HTTP_X_FORWARDED' => '209.85.148.101'], '123.123.123.123'],
            [['HTTP_X_FORWARDED_FOR' => '123.123.123', 'HTTP_FORWARDED_FOR' => '209.85.148.101'], '209.85.148.101'],
            [['HTTP_X_FORWARDED_FOR' => '192.168.178.1', 'HTTP_FORWARDED_FOR' => '209.85.148.101'], '209.85.148.101'],
            [['HTTP_X_FORWARDED_FOR' => '123.123.123.123, 209.85.148.101, 209.85.148.102'], '123.123.123.123'],
            [['HTTP_X_CLUSTER_CLIENT_IP' => '209.85.148.101, 209.85.148.102'], '209.85.148.101'],
            [['HTTP_FORWARDED_FOR' => '209.85.148.101'], '209.85.148.101'],
            [['REMOTE_ADDR' => '127.0.0.1'], '127.0.0.1'],
            [['HTTP_X_FORWARDED_FOR' => '2607:ff10:c5:509a::1'], '2607:ff10:c5:509a::1'],
        ];
    }

    /**
     * @test
     * @dataProvider serverEnvironmentsForClientIpAddresses
     */
    public function getClientIpAddressReturnsTheIpAddressDerivedFromSeveralServerEnvironmentVariables(array $serverEnvironment, $expectedIpAddress)
    {
        $defaultServerEnvironment = [
            'HTTP_USER_AGENT' => 'Flow/' . FLOW_VERSION_BRANCH . '.x',
            'HTTP_HOST' => 'flow.neos.io',
            'SERVER_NAME' => 'neos.io',
            'SERVER_ADDR' => '217.29.36.55',
            'SERVER_PORT' => 80,
            'REMOTE_ADDR' => '17.172.224.47',
            'SCRIPT_FILENAME' => FLOW_PATH_WEB . 'index.php',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
        ];

        $server = array_replace($defaultServerEnvironment, $serverEnvironment);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://flow.neos.io'), $server);
        $trustedRequest = $this->callWithRequest($request);
        self::assertSame($expectedIpAddress, $trustedRequest->getAttribute(ServerRequestAttributes::CLIENT_IP));
    }

    /**
     * Data Provider
     */
    public function serverEnvironmentsForForwardedHeader()
    {
        return [
            [['HTTP_FORWARDED' => 'for=209.85.148.101; proto=https; host=www.acme.org'], '209.85.148.101', 'https', 'www.acme.org', null],
            [['HTTP_FORWARDED' => 'For=123.123.123.123, for=209.85.148.101'], '123.123.123.123', 'http', 'flow.neos.io', null],
            [['HTTP_FORWARDED' => 'FOR=192.0.2.60, for=209.85.148.101; proto=https; HOST="123.123.123.123:4711", host=www.acme.org:8080; by=203.0.113.43'], '192.0.2.60', 'https', '123.123.123.123', 4711],
            [['HTTP_FORWARDED' => 'for=192.0.2.60; proto=https; host=www.acme.org:8080; by=203.0.113.43'], '192.0.2.60', 'https', 'www.acme.org', 8080],
        ];
    }

    /**
     * @test
     * @dataProvider serverEnvironmentsForForwardedHeader
     */
    public function trustedProxyCorrectlyParsesForwardedHeaders(array $serverEnvironment, $expectedIpAddress, $expectedProto, $expectedHost, $expectedPort)
    {
        $defaultServerEnvironment = [
            'HTTP_USER_AGENT' => 'Flow/' . FLOW_VERSION_BRANCH . '.x',
            'HTTP_HOST' => 'flow.neos.io',
            'SERVER_NAME' => 'neos.io',
            'SERVER_ADDR' => '217.29.36.55',
            'SERVER_PORT' => 80,
            'REMOTE_ADDR' => '17.172.224.47',
            'SCRIPT_FILENAME' => FLOW_PATH_WEB . 'index.php',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
        ];

        $this->withTrustedProxiesSettings(['proxies' => '*', 'headers' => 'Forwarded']);
        $server = array_replace($defaultServerEnvironment, $serverEnvironment);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://flow.neos.io'), $server);
        $trustedRequest = $this->callWithRequest($request);
        self::assertSame($expectedIpAddress, $trustedRequest->getAttribute(ServerRequestAttributes::CLIENT_IP));
        self::assertSame($expectedProto, $trustedRequest->getUri()->getScheme());
        self::assertSame($expectedHost, $trustedRequest->getUri()->getHost());
        self::assertSame($expectedPort, $trustedRequest->getUri()->getPort());
    }

    /**
     * @test
     */
    public function isSecureReturnsTrueEvenIfTheSchemeIsHttpButTheRequestWasForwardedAndOriginallyWasHttps()
    {
        $server = [
            'REMOTE_ADDR' => '17.172.224.47',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT' => '443'
        ];

        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://acme.com'), $server);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals('https://acme.com', (string)$trustedRequest->getUri());
        self::assertEquals('https', $trustedRequest->getUri()->getScheme());
    }

    /**
     * @test
     */
    public function isSecureReturnsFalseIfTheRequestWasForwardedAndOriginallyWasHttp()
    {
        $server = [
            'REMOTE_ADDR' => '17.172.224.47',
            'HTTP_X_FORWARDED_PROTO' => 'http',
            'HTTP_X_FORWARDED_PORT' => '80',
        ];

        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'), $server);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals('http://acme.com', (string)$trustedRequest->getUri());
        self::assertEquals('http', $trustedRequest->getUri()->getScheme());
    }

    /**
     * @test
     */
    public function isFromTrustedProxyByDefault()
    {
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'));
        $trustedRequest = $this->callWithRequest($request);
        self::assertTrue($trustedRequest->getAttribute(ServerRequestAttributes::TRUSTED_PROXY));
    }

    /**
     * @test
     */
    public function isFromTrustedProxyIfRemoteAddressMatchesRange()
    {
        $this->withTrustedProxiesSettings(['proxies' => ['127.0.0.0/24']]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'));
        $trustedRequest = $this->callWithRequest($request);
        self::assertTrue($trustedRequest->getAttribute(ServerRequestAttributes::TRUSTED_PROXY));
    }

    /**
     * @test
     */
    public function isNotFromTrustedProxyIfNoProxiesAreTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => []]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'));
        $trustedRequest = $this->callWithRequest($request);
        self::assertFalse($trustedRequest->getAttribute(ServerRequestAttributes::TRUSTED_PROXY));
    }

    /**
     * @test
     */
    public function isNotFromTrustedProxyIfRemoteAddressDoesntMatch()
    {
        $this->withTrustedProxiesSettings(['proxies' => ['10.0.0.1/24']]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'));
        $trustedRequest = $this->callWithRequest($request);
        self::assertFalse($trustedRequest->getAttribute(ServerRequestAttributes::TRUSTED_PROXY));
    }

    /**
     * @test
     */
    public function trustedClientIpAddressIsRemoteAddressIfNoProxiesAreTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => [], 'headers' => [TrustedProxiesMiddleware::HEADER_CLIENT_IP => 'X-Forwarded-For']]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'), [], null, '1.1', ['HTTP_X_FORWARDED_FOR' => '10.0.0.1']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals('127.0.0.1', $trustedRequest->getAttribute(ServerRequestAttributes::CLIENT_IP));
    }

    /**
     * @test
     */
    public function trustedClientIpAddressIsRemoteAddressIfHeaderNotTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => ['127.0.0.1'], 'headers' => []]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'), ['HTTP_X_FORWARDED_FOR' => '10.0.0.1']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals('127.0.0.1', $trustedRequest->getAttribute(ServerRequestAttributes::CLIENT_IP));
    }

    /**
     * @test
     */
    public function trustedClientIpAddressIsForwardedForAddressIfProxyTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => ['127.0.0.1'], 'headers' => [TrustedProxiesMiddleware::HEADER_CLIENT_IP => 'X-Forwarded-For']]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'), ['HTTP_X_FORWARDED_FOR' => '13.0.0.1']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals('13.0.0.1', $trustedRequest->getAttribute(ServerRequestAttributes::CLIENT_IP));
    }

    /**
     * @test
     */
    public function trustedClientIpAddressIsFirstForwardedForAddressIfAllProxiesTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => '*', 'headers' => [TrustedProxiesMiddleware::HEADER_CLIENT_IP => 'X-Forwarded-For']]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'), ['HTTP_X_FORWARDED_FOR' => '13.0.0.1, 13.0.0.2, 13.0.0.3']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals('13.0.0.1', $trustedRequest->getAttribute(ServerRequestAttributes::CLIENT_IP));
    }

    /**
     * @test
     */
    public function trustedClientIpAddressIsRightMostForwardedForAddressThatIsNotTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => ['127.0.0.1','10.0.0.1/24'], 'headers' => [TrustedProxiesMiddleware::HEADER_CLIENT_IP => 'X-Forwarded-For']]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'), ['HTTP_X_FORWARDED_FOR' => '198.155.23.17, 215.0.0.1, 10.0.0.1, 10.0.0.2']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals('215.0.0.1', $trustedRequest->getAttribute(ServerRequestAttributes::CLIENT_IP));
    }

    /**
     * @test
     */
    public function trustedClientIpAddressIsRemoteAddressIfTheHeaderIsNotTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => '*', 'headers' => [TrustedProxiesMiddleware::HEADER_CLIENT_IP => 'X-Forwarded-Ip']]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'), ['HTTP_X_FORWARDED_FOR' => '10.0.0.1']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals('127.0.0.1', $trustedRequest->getAttribute(ServerRequestAttributes::CLIENT_IP));
    }

    /**
     * @test
     */
    public function portIsNotOverridenIfTheHeaderIsNotTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => '*', 'headers' => []]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://acme.com'), ['HTTP_X_FORWARDED_PORT' => '443']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals(null, $trustedRequest->getUri()->getPort());
    }

    /**
     * @test
     */
    public function protocolIsNotOverridenIfTheHeaderIsNotTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => '*', 'headers' => []]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://acme.com'), ['HTTP_X_FORWARDED_PROTO' => 'https']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals('http', $trustedRequest->getUri()->getScheme());
    }

    /**
     * @test
     */
    public function hostIsNotOverridenIfTheHeaderIsNotTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => '*', 'headers' => []]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://acme.com'), ['HTTP_X_FORWARDED_HOST' => 'neos.io']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals('acme.com', $trustedRequest->getUri()->getHost());
    }

    /**
     * @test
     */
    public function hostIsOverridenIfTheHeaderIsTrusted()
    {
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://acme.com'), ['HTTP_X_FORWARDED_HOST' => 'neos.io']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals('neos.io', $trustedRequest->getUri()->getHost());
    }

    /**
     * @test
     */
    public function portIsOverridenIfTheHostHeaderContainsPort()
    {
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://acme.com'), ['HTTP_X_FORWARDED_HOST' => 'neos.io:443']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals(443, $trustedRequest->getUri()->getPort());
    }

    /**
     * @test
     */
    public function portIsOverridenIfTheHostHeaderContainsJustThePort()
    {
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://acme.com'), ['HTTP_X_FORWARDED_HOST' => ':443']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals(443, $trustedRequest->getUri()->getPort());
    }

    /**
     * @test
     */
    public function portIsOverridenIfTheHostHeaderContainsPortAlsoIfProtocolHeaderIsSet()
    {
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://acme.com'), ['HTTP_X_FORWARDED_HOST' => 'neos.io:443', 'HTTP_X_FORWARDED_PROTO' => 'http']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals(443, $trustedRequest->getUri()->getPort());
    }

    /**
     * @test
     */
    public function portFromHostHeaderIsOverriddenByPortHeader()
    {
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://acme.com'), ['HTTP_X_FORWARDED_PORT' => 8080, 'HTTP_X_FORWARDED_HOST' => 'neos.io:443']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals(8080, $trustedRequest->getUri()->getPort());
    }

    /**
     * @return array
     */
    public function forwardHeaderTestsDataProvider()
    {
        return [
            [
                'forwardedProtocol' => null,
                'forwardedPort' => null,
                'requestUri' => 'http://acme.com',
                'expectedUri' => 'http://acme.com',
            ],

            // forwarded protocol overrules requested protocol
            [
                'forwardedProtocol' => 'https',
                'forwardedPort' => null,
                'requestUri' => 'http://acme.com',
                'expectedUri' => 'https://acme.com',
            ],
            [
                'forwardedProtocol' => 'https',
                'forwardedPort' => null,
                'requestUri' => 'https://acme.com',
                'expectedUri' => 'https://acme.com',
            ],
            [
                'forwardedProtocol' => 'http',
                'forwardedPort' => null,
                'requestUri' => 'https://acme.com',
                'expectedUri' => 'http://acme.com',
            ],
            [
                'forwardedProtocol' => 'http',
                'forwardedPort' => null,
                'requestUri' => 'http://acme.com',
                'expectedUri' => 'http://acme.com',
            ],

            // forwarded port overrules requested port
            [
                'forwardedProtocol' => null,
                'forwardedPort' => 80,
                'requestUri' => 'http://acme.com',
                'expectedUri' => 'http://acme.com',
            ],
            [
                'forwardedProtocol' => null,
                'forwardedPort' => '8080',
                'requestUri' => 'http://acme.com',
                'expectedUri' => 'http://acme.com:8080',
            ],
            [
                'forwardedProtocol' => null,
                'forwardedPort' => 8080,
                'requestUri' => 'http://acme.com:8000',
                'expectedUri' => 'http://acme.com:8080',
            ],
            [
                'forwardedProtocol' => null,
                'forwardedPort' => '443',
                'requestUri' => 'https://acme.com',
                'expectedUri' => 'https://acme.com',
            ],

            // forwarded protocol & port
            [
                'forwardedProtocol' => 'http',
                'forwardedPort' => 80,
                'requestUri' => 'http://acme.com',
                'expectedUri' => 'http://acme.com',
            ],
            [
                'forwardedProtocol' => 'http',
                'forwardedPort' => 8080,
                'requestUri' => 'http://acme.com',
                'expectedUri' => 'http://acme.com:8080',
            ],
            [
                'forwardedProtocol' => 'http',
                'forwardedPort' => 443,
                'requestUri' => 'https://acme.com',
                'expectedUri' => 'http://acme.com:443',
            ],
            [
                'forwardedProtocol' => 'https',
                'forwardedPort' => 443,
                'requestUri' => 'http://acme.com',
                'expectedUri' => 'https://acme.com',
            ],
            [
                'forwardedProtocol' => 'https',
                'forwardedPort' => 443,
                'requestUri' => 'https://acme.com',
                'expectedUri' => 'https://acme.com',
            ],
            [
                'forwardedProtocol' => 'https',
                'forwardedPort' => 80,
                'requestUri' => 'https://acme.com',
                'expectedUri' => 'https://acme.com:80',
            ],
            [
                'forwardedProtocol' => 'HTTPS',
                'forwardedPort' => null,
                'requestUri' => 'http://acme.com',
                'expectedUri' => 'https://acme.com',
            ],
            [
                'forwardedProtocol' => 'http',
                'forwardedPort' => 80,
                'requestUri' => 'http://[2a00:f48:1008::212:183:10]',
                'expectedUri' => 'http://[2a00:f48:1008::212:183:10]',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider forwardHeaderTestsDataProvider
     */
    public function forwardHeaderTests(?string $forwardedProtocol, $forwardedPort, string $requestUri, string $expectedUri)
    {
        $server = [];
        if ($forwardedProtocol !== null) {
            $server['HTTP_X_FORWARDED_PROTO'] = $forwardedProtocol;
        }
        if ($forwardedPort !== null) {
            $server['HTTP_X_FORWARDED_PORT'] = $forwardedPort;
        }
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri($requestUri), $server);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals($expectedUri, (string)$trustedRequest->getUri());
    }
}
