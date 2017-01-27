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

use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Component\TrustedProxiesComponent;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;
use Neos\Flow\Http\Uri;
use Neos\Flow\Tests\UnitTestCase;
use Zend\Code\Reflection\ClassReflection;

/**
 * Test case for the TrustedProxiesComponent
 */
class TrustedProxiesComponentTest extends UnitTestCase
{
    /**
     * @var TrustedProxiesComponent
     */
    protected $trustedProxiesComponent;

    /**
     * @var \ReflectionProperty
     */
    protected $trustedProxiesSettings;

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
        $this->mockHttpRequest = $this->getMockBuilder(\Neos\Flow\Http\Request::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpResponse = $this->getMockBuilder(\Neos\Flow\Http\Response::class)->disableOriginalConstructor()->getMock();

        $this->mockComponentContext =
        $this->trustedProxiesComponent = new TrustedProxiesComponent(array());
        $componentReflection = new \ReflectionClass($this->trustedProxiesComponent);
        $this->trustedProxiesSettings = $componentReflection->getProperty('settings');
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
        $this->trustedProxiesSettings->setValue($this->trustedProxiesComponent, $settings);
    }

    /**
     * @param Request $request
     * @return Request
     */
    protected function callWithRequest($request)
    {
        $componentContext = new ComponentContext($request, $this->mockHttpResponse);
        $this->trustedProxiesComponent->handle($componentContext);
        return $componentContext->getHttpRequest();
    }

    /**
     * RFC 2616 / 14.23 (Host)
     * @test
     * @backupGlobals disabled
     */
    public function portInProxyHeaderIsAcknowledged()
    {
        $scriptName = $_SERVER['SCRIPT_NAME'];

        $_SERVER = array(
            'HTTP_HOST' => 'dev.blog.rob',
            'HTTP_X_FORWARDED_PORT' => 2727,
            'SERVER_NAME' => 'dev.blog.rob',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_PORT' => '80',
            'REMOTE_ADDR' => '127.0.0.1',
            'REQUEST_URI' => '/posts/2011/11/28/laboriosam-soluta-est-minus-molestiae?getKey1=getValue1&getKey2=getValue2',
            'REQUEST_TIME' => 1326472534
        );

        $request = Request::create(new Uri('https://dev.blog.rob/foo/bar?baz=quux&coffee=due'), array(), array(), array(), $_SERVER);
        $trustedRequest = $this->callWithRequest($request);
        $this->assertSame(2727, $trustedRequest->getPort());

        $_SERVER['SCRIPT_NAME'] = $scriptName;
    }

    /**
     * Data Provider
     */
    public function serverEnvironmentsForClientIpAddresses()
    {
        return array(
            array(array(), '17.172.224.47'),
            array(array('HTTP_CLIENT_IP' => 'murks'), '17.172.224.47'),
            array(array('HTTP_CLIENT_IP' => '17.149.160.49'), '17.149.160.49'),
            array(array('HTTP_CLIENT_IP' => '17.149.160.49', 'HTTP_X_FORWARDED_FOR' => '123.123.123.123'), '17.149.160.49'),
            array(array('HTTP_X_FORWARDED_FOR' => '123.123.123.123'), '123.123.123.123'),
            array(array('HTTP_X_FORWARDED_FOR' => '123.123.123.123', 'HTTP_X_FORWARDED' => '209.85.148.101'), '123.123.123.123'),
            array(array('HTTP_X_FORWARDED_FOR' => '123.123.123', 'HTTP_FORWARDED_FOR' => '209.85.148.101'), '209.85.148.101'),
            array(array('HTTP_X_FORWARDED_FOR' => '192.168.178.1', 'HTTP_FORWARDED_FOR' => '209.85.148.101'), '209.85.148.101'),
            array(array('HTTP_X_FORWARDED_FOR' => '123.123.123.123, 209.85.148.101, 209.85.148.102'), '123.123.123.123'),
            array(array('HTTP_X_CLUSTER_CLIENT_IP' => '209.85.148.101, 209.85.148.102'), '209.85.148.101'),
            array(array('HTTP_FORWARDED_FOR' => '209.85.148.101'), '209.85.148.101'),
            array(array('HTTP_FORWARDED' => '209.85.148.101'), '209.85.148.101'),
            array(array('REMOTE_ADDR' => '127.0.0.1'), '127.0.0.1'),
        );
    }

    /**
     * @test
     * @dataProvider serverEnvironmentsForClientIpAddresses
     */
    public function getClientIpAddressReturnsTheIpAddressDerivedFromSeveralServerEnvironmentVariables(array $serverEnvironment, $expectedIpAddress)
    {
        $defaultServerEnvironment = array(
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
        );

        $request = Request::create(new Uri('http://flow.neos.io'), 'GET', array(), array(), array_replace($defaultServerEnvironment, $serverEnvironment));
        $trustedRequest = $this->callWithRequest($request);
        $this->assertSame($expectedIpAddress, $trustedRequest->getClientIpAddress());
    }

    /**
     * @test
     */
    public function isSecureReturnsTrueEvenIfTheSchemeIsHttpButTheRequestWasForwardedAndOriginallyWasHttps()
    {
        $server = array(
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT' => '443',
        );

        $request = Request::create(new Uri('http://acme.com'), 'GET', array(), array(), $server);
        $trustedRequest = $this->callWithRequest($request);
        $this->assertEquals('https://acme.com', (string)$trustedRequest->getUri());
        $this->assertEquals('https', $trustedRequest->getUri()->getScheme());
        $this->assertTrue($trustedRequest->isSecure());
    }

    /**
     * @test
     */
    public function isSecureReturnsFalseIfTheRequestWasForwardedAndOriginallyWasHttp()
    {
        $server = array(
            'HTTP_X_FORWARDED_PROTO' => 'http',
            'HTTP_X_FORWARDED_PORT' => '80',
        );

        $request = Request::create(new Uri('https://acme.com'), 'GET', array(), array(), $server);
        $trustedRequest = $this->callWithRequest($request);
        $this->assertEquals('http://acme.com', (string)$trustedRequest->getUri());
        $this->assertEquals('http', $trustedRequest->getUri()->getScheme());
        $this->assertFalse($trustedRequest->isSecure());
    }

    /**
     * @test
     */
    public function isFromTrustedProxyByDefault()
    {
        $request = Request::create(new Uri('https://acme.com'), 'GET');
        $trustedRequest = $this->callWithRequest($request);
        $this->assertTrue($trustedRequest->getAttribute(Request::ATTRIBUTE_TRUSTED_PROXY));
    }

    /**
     * @test
     */
    public function isFromTrustedProxyIfRemoteAddressMatchesRange()
    {
        $this->withTrustedProxiesSettings(['proxies' => ['127.0.0.0/24']]);
        $request = Request::create(new Uri('https://acme.com'), 'GET');
        $trustedRequest = $this->callWithRequest($request);
        $this->assertTrue($trustedRequest->getAttribute(Request::ATTRIBUTE_TRUSTED_PROXY));
    }

    /**
     * @test
     */
    public function isNotFromTrustedProxyIfNoProxiesAreTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => []]);
        $request = Request::create(new Uri('https://acme.com'), 'GET');
        $trustedRequest = $this->callWithRequest($request);
        $this->assertFalse($trustedRequest->getAttribute(Request::ATTRIBUTE_TRUSTED_PROXY));
    }

    /**
     * @test
     */
    public function isNotFromTrustedProxyIfRemoteAddressDoesntMatch()
    {
        $this->withTrustedProxiesSettings(['proxies' => ['10.0.0.1/24']]);
        $request = Request::create(new Uri('https://acme.com'), 'GET');
        $trustedRequest = $this->callWithRequest($request);
        $this->assertFalse($trustedRequest->getAttribute(Request::ATTRIBUTE_TRUSTED_PROXY));
    }

    /**
     * @test
     */
    public function trustedClientIpAddressIsRemoteAddressIfNoProxiesAreTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => [], 'headers' => [TrustedProxiesComponent::HEADER_CLIENT_IP => 'X-Forwarded-For']]);
        $request = Request::create(new Uri('https://acme.com'), 'GET', array(), array(), array('HTTP_X_FORWARDED_FOR' => '10.0.0.1'));
        $trustedRequest = $this->callWithRequest($request);
        $this->assertEquals('127.0.0.1', $trustedRequest->getAttribute(Request::ATTRIBUTE_CLIENT_IP));
    }

    /**
     * @test
     */
    public function trustedClientIpAddressIsRemoteAddressIfHeaderNotTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => ['127.0.0.1'], 'headers' => []]);
        $request = Request::create(new Uri('https://acme.com'), 'GET', array(), array(), array('HTTP_X_FORWARDED_FOR' => '10.0.0.1'));
        $trustedRequest = $this->callWithRequest($request);
        $this->assertEquals('127.0.0.1', $trustedRequest->getAttribute(Request::ATTRIBUTE_CLIENT_IP));
    }

    /**
     * @test
     */
    public function trustedClientIpAddressIsForwardedForAddressIfProxyTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => ['127.0.0.1'], 'headers' => [TrustedProxiesComponent::HEADER_CLIENT_IP => 'X-Forwarded-For']]);
        $request = Request::create(new Uri('https://acme.com'), 'GET', array(), array(), array('HTTP_X_FORWARDED_FOR' => '13.0.0.1'));
        $trustedRequest = $this->callWithRequest($request);
        $this->assertEquals('13.0.0.1', $trustedRequest->getAttribute(Request::ATTRIBUTE_CLIENT_IP));
    }

    /**
     * @test
     */
    public function trustedClientIpAddressIsFirstForwardedForAddressIfAllProxiesTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => '*', 'headers' => [TrustedProxiesComponent::HEADER_CLIENT_IP => 'X-Forwarded-For']]);
        $request = Request::create(new Uri('https://acme.com'), 'GET', array(), array(), array('HTTP_X_FORWARDED_FOR' => '13.0.0.1, 13.0.0.2, 13.0.0.3'));
        $trustedRequest = $this->callWithRequest($request);
        $this->assertEquals('13.0.0.1', $trustedRequest->getAttribute(Request::ATTRIBUTE_CLIENT_IP));
    }

    /**
     * @test
     */
    public function trustedClientIpAddressIsRightMostForwardedForAddressThatIsNotTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => ['127.0.0.1','10.0.0.1/24'], 'headers' => [TrustedProxiesComponent::HEADER_CLIENT_IP => 'X-Forwarded-For']]);
        $request = Request::create(new Uri('https://acme.com'), 'GET', array(), array(), array('HTTP_X_FORWARDED_FOR' => '198.155.23.17, 215.0.0.1, 10.0.0.1, 10.0.0.2'));
        $trustedRequest = $this->callWithRequest($request);
        $this->assertEquals('215.0.0.1', $trustedRequest->getAttribute(Request::ATTRIBUTE_CLIENT_IP));
    }

    /**
     * @test
     */
    public function trustedClientIpAddressIsRemoteAddressIfTheHeaderIsNotTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => '*', 'headers' => [TrustedProxiesComponent::HEADER_CLIENT_IP => 'X-Forwarded-Ip']]);
        $request = Request::create(new Uri('https://acme.com'), 'GET', array(), array(), array('HTTP_X_FORWARDED_FOR' => '10.0.0.1'));
        $trustedRequest = $this->callWithRequest($request);
        $this->assertEquals('127.0.0.1', $trustedRequest->getAttribute(Request::ATTRIBUTE_CLIENT_IP));
    }

    /**
     * @test
     */
    public function portIsNotOverridenIfTheHeaderIsNotTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => '*', 'headers' => []]);
        $request = Request::create(new Uri('http://acme.com'), 'GET', array(), array(), array('HTTP_X_FORWARDED_PORT' => '443'));
        $trustedRequest = $this->callWithRequest($request);
        $this->assertEquals(80, $trustedRequest->getPort());
    }

    /**
     * @test
     */
    public function protocolIsNotOverridenIfTheHeaderIsNotTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => '*', 'headers' => []]);
        $request = Request::create(new Uri('http://acme.com'), 'GET', array(), array(), array('HTTP_X_FORWARDED_PROTO' => 'https'));
        $trustedRequest = $this->callWithRequest($request);
        $this->assertEquals('http', $trustedRequest->getUri()->getScheme());
    }

    /**
     * @test
     */
    public function hostIsNotOverridenIfTheHeaderIsNotTrusted()
    {
        $this->withTrustedProxiesSettings(['proxies' => '*', 'headers' => []]);
        $request = Request::create(new Uri('http://acme.com'), 'GET', array(), array(), array('HTTP_X_FORWARDED_HOST' => 'neos.io'));
        $trustedRequest = $this->callWithRequest($request);
        $this->assertEquals('acme.com', $trustedRequest->getUri()->getHost());
    }

    /**
     * @return array
     */
    public function forwardHeaderTestsDataProvider()
    {
        return array(
            array(
                'forwardedProtocol' => null,
                'forwardedPort' => null,
                'requestUri' => 'http://acme.com',
                'expectedUri' => 'http://acme.com',
            ),

            // forwarded protocol overrules requested protocol
            array(
                'forwardedProtocol' => 'https',
                'forwardedPort' => null,
                'requestUri' => 'http://acme.com',
                'expectedUri' => 'https://acme.com',
            ),
            array(
                'forwardedProtocol' => 'https',
                'forwardedPort' => null,
                'requestUri' => 'https://acme.com',
                'expectedUri' => 'https://acme.com',
            ),
            array(
                'forwardedProtocol' => 'http',
                'forwardedPort' => null,
                'requestUri' => 'https://acme.com',
                'expectedUri' => 'http://acme.com',
            ),
            array(
                'forwardedProtocol' => 'http',
                'forwardedPort' => null,
                'requestUri' => 'http://acme.com',
                'expectedUri' => 'http://acme.com',
            ),

            // forwarded port overrules requested port
            array(
                'forwardedProtocol' => null,
                'forwardedPort' => 80,
                'requestUri' => 'http://acme.com',
                'expectedUri' => 'http://acme.com',
            ),
            array(
                'forwardedProtocol' => null,
                'forwardedPort' => '8080',
                'requestUri' => 'http://acme.com',
                'expectedUri' => 'http://acme.com:8080',
            ),
            array(
                'forwardedProtocol' => null,
                'forwardedPort' => 8080,
                'requestUri' => 'http://acme.com:8000',
                'expectedUri' => 'http://acme.com:8080',
            ),
            array(
                'forwardedProtocol' => null,
                'forwardedPort' => '443',
                'requestUri' => 'https://acme.com',
                'expectedUri' => 'https://acme.com',
            ),

            // forwarded protocol & port
            array(
                'forwardedProtocol' => 'http',
                'forwardedPort' => 80,
                'requestUri' => 'http://acme.com',
                'expectedUri' => 'http://acme.com',
            ),
            array(
                'forwardedProtocol' => 'http',
                'forwardedPort' => 8080,
                'requestUri' => 'http://acme.com',
                'expectedUri' => 'http://acme.com:8080',
            ),
            array(
                'forwardedProtocol' => 'http',
                'forwardedPort' => 443,
                'requestUri' => 'https://acme.com',
                'expectedUri' => 'http://acme.com:443',
            ),
            array(
                'forwardedProtocol' => 'https',
                'forwardedPort' => 443,
                'requestUri' => 'http://acme.com',
                'expectedUri' => 'https://acme.com',
            ),
            array(
                'forwardedProtocol' => 'https',
                'forwardedPort' => 443,
                'requestUri' => 'https://acme.com',
                'expectedUri' => 'https://acme.com',
            ),
            array(
                'forwardedProtocol' => 'https',
                'forwardedPort' => 80,
                'requestUri' => 'https://acme.com',
                'expectedUri' => 'https://acme.com:80',
            ),
            array(
                'forwardedProtocol' => 'HTTPS',
                'forwardedPort' => null,
                'requestUri' => 'http://acme.com',
                'expectedUri' => 'https://acme.com',
            ),
        );
    }

    /**
     * @test
     * @dataProvider forwardHeaderTestsDataProvider
     */
    public function forwardHeaderTests($forwardedProtocol, $forwardedPort, $requestUri, $expectedUri)
    {
        $server = array();
        if ($forwardedProtocol !== null) {
            $server['HTTP_X_FORWARDED_PROTO'] = $forwardedProtocol;
        }
        if ($forwardedPort !== null) {
            $server['HTTP_X_FORWARDED_PORT'] = $forwardedPort;
        }
        $request = Request::create(new Uri($requestUri), 'GET', array(), array(), $server);
        $trustedRequest = $this->callWithRequest($request);
        $this->assertEquals($expectedUri, (string)$trustedRequest->getUri());
    }
}
