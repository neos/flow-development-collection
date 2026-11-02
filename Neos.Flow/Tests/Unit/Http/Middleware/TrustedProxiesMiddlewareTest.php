<?php

declare(strict_types=1);

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
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Http\Middleware\TrustedProxiesMiddleware;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Tests\Unit\Http\Fixtures\SpyRequestHandler;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Http\Factories\ServerRequestFactory;
use Neos\Http\Factories\UriFactory;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Test case for the TrustedProxiesMiddleware
 */
final class TrustedProxiesMiddlewareTest extends UnitTestCase
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
     * @var ServerRequestInterface|MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var ResponseInterface|MockObject
     */
    protected $mockHttpResponse;

    /**
     * @var RequestHandlerInterface|MockObject
     */
    protected $mockHttpRequestHandler;

    /**
     * @var ServerRequestFactoryInterface
     */
    protected $serverRequestFactory;

    protected function setUp(): void
    {
        $this->mockHttpRequest = $this->createMock(ServerRequestInterface::class);
        $this->mockHttpResponse = $this->createMock(ResponseInterface::class);
        $this->mockHttpRequestHandler = $this->createMock(RequestHandlerInterface::class);

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
     */
    #[BackupGlobals(false)]
    #[Test]
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
     */
    #[BackupGlobals(false)]
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
    public static function serverEnvironmentsForClientIpAddresses(): \Iterator
    {
        yield [[], '17.172.224.47'];
        yield [['HTTP_CLIENT_IP' => 'murks'], '17.172.224.47'];
        yield [['HTTP_CLIENT_IP' => '17.149.160.49'], '17.149.160.49'];
        yield [['HTTP_CLIENT_IP' => '17.149.160.49', 'HTTP_X_FORWARDED_FOR' => '123.123.123.123'], '17.149.160.49'];
        yield [['HTTP_X_FORWARDED_FOR' => '123.123.123.123'], '123.123.123.123'];
        yield [['HTTP_X_FORWARDED_FOR' => '123.123.123.123', 'HTTP_X_FORWARDED' => '209.85.148.101'], '123.123.123.123'];
        yield [['HTTP_X_FORWARDED_FOR' => '123.123.123', 'HTTP_FORWARDED_FOR' => '209.85.148.101'], '209.85.148.101'];
        yield [['HTTP_X_FORWARDED_FOR' => '192.168.178.1', 'HTTP_FORWARDED_FOR' => '209.85.148.101'], '209.85.148.101'];
        yield [['HTTP_X_FORWARDED_FOR' => '123.123.123.123, 209.85.148.101, 209.85.148.102'], '123.123.123.123'];
        yield [['HTTP_X_CLUSTER_CLIENT_IP' => '209.85.148.101, 209.85.148.102'], '209.85.148.101'];
        yield [['HTTP_FORWARDED_FOR' => '209.85.148.101'], '209.85.148.101'];
        yield [['REMOTE_ADDR' => '127.0.0.1'], '127.0.0.1'];
        yield [['HTTP_X_FORWARDED_FOR' => '2607:ff10:c5:509a::1'], '2607:ff10:c5:509a::1'];
    }

    #[DataProvider('serverEnvironmentsForClientIpAddresses')]
    #[Test]
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
    public static function serverEnvironmentsForForwardedHeader(): \Iterator
    {
        yield [['HTTP_FORWARDED' => 'for=209.85.148.101; proto=https; host=www.acme.org'], '209.85.148.101', 'https', 'www.acme.org', null];
        yield [['HTTP_FORWARDED' => 'For=123.123.123.123, for=209.85.148.101'], '123.123.123.123', 'http', 'flow.neos.io', null];
        yield [['HTTP_FORWARDED' => 'FOR=192.0.2.60, for=209.85.148.101; proto=https; HOST="123.123.123.123:4711", host=www.acme.org:8080; by=203.0.113.43'], '192.0.2.60', 'https', '123.123.123.123', 4711];
        yield [['HTTP_FORWARDED' => 'for=192.0.2.60; proto=https; host=www.acme.org:8080; by=203.0.113.43'], '192.0.2.60', 'https', 'www.acme.org', 8080];
    }

    #[DataProvider('serverEnvironmentsForForwardedHeader')]
    #[Test]
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

    #[Test]
    public function isSecureReturnsTrueEvenIfTheSchemeIsHttpButTheRequestWasForwardedAndOriginallyWasHttps()
    {
        $server = [
            'REMOTE_ADDR' => '17.172.224.47',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT' => '443'
        ];

        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://acme.com'), $server);
        $trustedRequest = $this->callWithRequest($request);
        self::assertSame('https://acme.com', (string)$trustedRequest->getUri());
        self::assertEquals('https', $trustedRequest->getUri()->getScheme());
    }

    #[Test]
    public function isSecureReturnsFalseIfTheRequestWasForwardedAndOriginallyWasHttp()
    {
        $server = [
            'REMOTE_ADDR' => '17.172.224.47',
            'HTTP_X_FORWARDED_PROTO' => 'http',
            'HTTP_X_FORWARDED_PORT' => '80',
        ];

        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'), $server);
        $trustedRequest = $this->callWithRequest($request);
        self::assertSame('http://acme.com', (string)$trustedRequest->getUri());
        self::assertEquals('http', $trustedRequest->getUri()->getScheme());
    }

    #[Test]
    public function isFromTrustedProxyByDefault()
    {
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'));
        $trustedRequest = $this->callWithRequest($request);
        self::assertTrue($trustedRequest->getAttribute(ServerRequestAttributes::TRUSTED_PROXY));
    }

    #[Test]
    public function isFromTrustedProxyIfRemoteAddressMatchesRange()
    {
        $this->withTrustedProxiesSettings(['proxies' => ['127.0.0.0/24']]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'));
        $trustedRequest = $this->callWithRequest($request);
        self::assertTrue($trustedRequest->getAttribute(ServerRequestAttributes::TRUSTED_PROXY));
    }

    #[Test]
    public function isNotFromTrustedProxyIfNoProxiesAreTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => []]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'));
        $trustedRequest = $this->callWithRequest($request);
        self::assertFalse($trustedRequest->getAttribute(ServerRequestAttributes::TRUSTED_PROXY));
    }

    #[Test]
    public function isNotFromTrustedProxyIfRemoteAddressDoesntMatch()
    {
        $this->withTrustedProxiesSettings(['proxies' => ['10.0.0.1/24']]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'));
        $trustedRequest = $this->callWithRequest($request);
        self::assertFalse($trustedRequest->getAttribute(ServerRequestAttributes::TRUSTED_PROXY));
    }

    #[Test]
    public function trustedClientIpAddressIsRemoteAddressIfNoProxiesAreTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => [], 'headers' => [TrustedProxiesMiddleware::HEADER_CLIENT_IP => 'X-Forwarded-For']]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'), [], null, '1.1', ['HTTP_X_FORWARDED_FOR' => '10.0.0.1']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals('127.0.0.1', $trustedRequest->getAttribute(ServerRequestAttributes::CLIENT_IP));
    }

    #[Test]
    public function trustedClientIpAddressIsRemoteAddressIfHeaderNotTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => ['127.0.0.1'], 'headers' => []]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'), ['HTTP_X_FORWARDED_FOR' => '10.0.0.1']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals('127.0.0.1', $trustedRequest->getAttribute(ServerRequestAttributes::CLIENT_IP));
    }

    #[Test]
    public function trustedClientIpAddressIsForwardedForAddressIfProxyTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => ['127.0.0.1'], 'headers' => [TrustedProxiesMiddleware::HEADER_CLIENT_IP => 'X-Forwarded-For']]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'), ['HTTP_X_FORWARDED_FOR' => '13.0.0.1']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals('13.0.0.1', $trustedRequest->getAttribute(ServerRequestAttributes::CLIENT_IP));
    }

    #[Test]
    public function trustedClientIpV6AddressIsForwardedForAddressIfProxyTrusted()
    {
        if (PHP_VERSION_ID < 80316 || (PHP_VERSION_ID >= 80400 && PHP_VERSION_ID < 80403)) {
            $this->markTestSkipped('This test requires PHP 8.3.16+ or 8.4.3+, see https://github.com/neos/flow-development-collection/pull/3491#issuecomment-3423451375');
        }
        $this->withTrustedProxiesSettings(['proxies' => ['127.0.0.1'], 'headers' => [TrustedProxiesMiddleware::HEADER_CLIENT_IP => 'X-Forwarded-For']]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'), ['HTTP_X_FORWARDED_FOR' => '2001:db8:cafe::17']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals('2001:db8:cafe::17', $trustedRequest->getAttribute(ServerRequestAttributes::CLIENT_IP));
    }

    #[Test]
    public function trustedClientIpAddressIsFirstForwardedForAddressIfAllProxiesTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => '*', 'headers' => [TrustedProxiesMiddleware::HEADER_CLIENT_IP => 'X-Forwarded-For']]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'), ['HTTP_X_FORWARDED_FOR' => '13.0.0.1, 13.0.0.2, 13.0.0.3']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals('13.0.0.1', $trustedRequest->getAttribute(ServerRequestAttributes::CLIENT_IP));
    }

    #[Test]
    public function trustedClientIpAddressIsRightMostForwardedForAddressThatIsNotTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => ['127.0.0.1','10.0.0.1/24'], 'headers' => [TrustedProxiesMiddleware::HEADER_CLIENT_IP => 'X-Forwarded-For']]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'), ['HTTP_X_FORWARDED_FOR' => '198.155.23.17, 215.0.0.1, 10.0.0.1, 10.0.0.2']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals('215.0.0.1', $trustedRequest->getAttribute(ServerRequestAttributes::CLIENT_IP));
    }

    #[Test]
    public function trustedClientIpAddressIsRemoteAddressIfTheHeaderIsNotTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => '*', 'headers' => [TrustedProxiesMiddleware::HEADER_CLIENT_IP => 'X-Forwarded-Ip']]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('https://acme.com'), ['HTTP_X_FORWARDED_FOR' => '10.0.0.1']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals('127.0.0.1', $trustedRequest->getAttribute(ServerRequestAttributes::CLIENT_IP));
    }

    #[Test]
    public function portIsNotOverridenIfTheHeaderIsNotTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => '*', 'headers' => []]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://acme.com'), ['HTTP_X_FORWARDED_PORT' => '443']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals(null, $trustedRequest->getUri()->getPort());
    }

    #[Test]
    public function protocolIsNotOverridenIfTheHeaderIsNotTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => '*', 'headers' => []]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://acme.com'), ['HTTP_X_FORWARDED_PROTO' => 'https']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals('http', $trustedRequest->getUri()->getScheme());
    }

    #[Test]
    public function hostIsNotOverridenIfTheHeaderIsNotTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => '*', 'headers' => []]);
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://acme.com'), ['HTTP_X_FORWARDED_HOST' => 'neos.io']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals('acme.com', $trustedRequest->getUri()->getHost());
    }

    #[Test]
    public function hostIsOverridenIfTheHeaderIsTrusted()
    {
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://acme.com'), ['HTTP_X_FORWARDED_HOST' => 'neos.io']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals('neos.io', $trustedRequest->getUri()->getHost());
    }

    #[Test]
    public function portIsOverridenIfTheHostHeaderContainsPort()
    {
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://acme.com'), ['HTTP_X_FORWARDED_HOST' => 'neos.io:443']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals(443, $trustedRequest->getUri()->getPort());
    }

    #[Test]
    public function portIsOverridenIfTheHostHeaderContainsJustThePort()
    {
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://acme.com'), ['HTTP_X_FORWARDED_HOST' => ':443']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals(443, $trustedRequest->getUri()->getPort());
    }

    #[Test]
    public function portIsOverridenIfTheHostHeaderContainsPortAlsoIfProtocolHeaderIsSet()
    {
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://acme.com'), ['HTTP_X_FORWARDED_HOST' => 'neos.io:443', 'HTTP_X_FORWARDED_PROTO' => 'http']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals(443, $trustedRequest->getUri()->getPort());
    }

    #[Test]
    public function portFromHostHeaderIsOverriddenByPortHeader()
    {
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://acme.com'), ['HTTP_X_FORWARDED_PORT' => 8080, 'HTTP_X_FORWARDED_HOST' => 'neos.io:443']);
        $trustedRequest = $this->callWithRequest($request);
        self::assertEquals(8080, $trustedRequest->getUri()->getPort());
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function forwardHeaderTestsDataProvider(): \Iterator
    {
        yield [
            'forwardedProtocol' => null,
            'forwardedPort' => null,
            'requestUri' => 'http://acme.com',
            'expectedUri' => 'http://acme.com',
        ];
        // forwarded protocol overrules requested protocol
        yield [
            'forwardedProtocol' => 'https',
            'forwardedPort' => null,
            'requestUri' => 'http://acme.com',
            'expectedUri' => 'https://acme.com',
        ];
        yield [
            'forwardedProtocol' => 'https',
            'forwardedPort' => null,
            'requestUri' => 'https://acme.com',
            'expectedUri' => 'https://acme.com',
        ];
        yield [
            'forwardedProtocol' => 'http',
            'forwardedPort' => null,
            'requestUri' => 'https://acme.com',
            'expectedUri' => 'http://acme.com',
        ];
        yield [
            'forwardedProtocol' => 'http',
            'forwardedPort' => null,
            'requestUri' => 'http://acme.com',
            'expectedUri' => 'http://acme.com',
        ];
        // forwarded port overrules requested port
        yield [
            'forwardedProtocol' => null,
            'forwardedPort' => 80,
            'requestUri' => 'http://acme.com',
            'expectedUri' => 'http://acme.com',
        ];
        yield [
            'forwardedProtocol' => null,
            'forwardedPort' => '8080',
            'requestUri' => 'http://acme.com',
            'expectedUri' => 'http://acme.com:8080',
        ];
        yield [
            'forwardedProtocol' => null,
            'forwardedPort' => 8080,
            'requestUri' => 'http://acme.com:8000',
            'expectedUri' => 'http://acme.com:8080',
        ];
        yield [
            'forwardedProtocol' => null,
            'forwardedPort' => '443',
            'requestUri' => 'https://acme.com',
            'expectedUri' => 'https://acme.com',
        ];
        // forwarded protocol & port
        yield [
            'forwardedProtocol' => 'http',
            'forwardedPort' => 80,
            'requestUri' => 'http://acme.com',
            'expectedUri' => 'http://acme.com',
        ];
        yield [
            'forwardedProtocol' => 'http',
            'forwardedPort' => 8080,
            'requestUri' => 'http://acme.com',
            'expectedUri' => 'http://acme.com:8080',
        ];
        yield [
            'forwardedProtocol' => 'http',
            'forwardedPort' => 443,
            'requestUri' => 'https://acme.com',
            'expectedUri' => 'http://acme.com:443',
        ];
        yield [
            'forwardedProtocol' => 'https',
            'forwardedPort' => 443,
            'requestUri' => 'http://acme.com',
            'expectedUri' => 'https://acme.com',
        ];
        yield [
            'forwardedProtocol' => 'https',
            'forwardedPort' => 443,
            'requestUri' => 'https://acme.com',
            'expectedUri' => 'https://acme.com',
        ];
        yield [
            'forwardedProtocol' => 'https',
            'forwardedPort' => 80,
            'requestUri' => 'https://acme.com',
            'expectedUri' => 'https://acme.com:80',
        ];
        yield [
            'forwardedProtocol' => 'HTTPS',
            'forwardedPort' => null,
            'requestUri' => 'http://acme.com',
            'expectedUri' => 'https://acme.com',
        ];
        yield [
            'forwardedProtocol' => 'http',
            'forwardedPort' => 80,
            'requestUri' => 'http://[2a00:f48:1008::212:183:10]',
            'expectedUri' => 'http://[2a00:f48:1008::212:183:10]',
        ];
    }

    #[DataProvider('forwardHeaderTestsDataProvider')]
    #[Test]
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
        self::assertSame($expectedUri, (string)$trustedRequest->getUri());
    }
}
