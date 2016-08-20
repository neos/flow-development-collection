<?php
namespace TYPO3\Flow\Tests\Unit\Http;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Uri;
use org\bovigo\vfs\vfsStream;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test case for the Http Request class
 *
 * In some tests backupGlobals is disabled, this is to avoid risky test warnings caused by changed globals
 * that are needed to be changed in those tests.
 *
 * Additionally those tests backup/restore the $_SERVER superglobal to avoid a warning
 * with PHPUnit when it tries to access that in phpunit/phpunit/src/Util/Filter.php on line 29
 */
class RequestTest extends UnitTestCase
{
    /**
     * @test
     * @backupGlobals disabled
     */
    public function createFromEnvironmentCreatesAReasonableRequestObjectFromTheSuperGlobals()
    {
        $server = $_SERVER;

        $_GET = array('getKey1' => 'getValue1', 'getKey2' => 'getValue2');
        $_POST = array();
        $_COOKIE = array();
        $_FILES = array();
        $_SERVER = array(
            'REDIRECT_FLOW_CONTEXT' => 'Development',
            'REDIRECT_FLOW_REWRITEURLS' => '1',
            'REDIRECT_STATUS' => '200',
            'FLOW_CONTEXT' => 'Development',
            'FLOW_REWRITEURLS' => '1',
            'HTTP_HOST' => 'dev.blog.rob',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_2) AppleWebKit/534.52.7 (KHTML, like Gecko) Version/5.1.2 Safari/534.52.7',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us',
            'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
            'HTTP_CONNECTION' => 'keep-alive',
            'PATH' => '/usr/bin:/bin:/usr/sbin:/sbin',
            'SERVER_SIGNATURE' => '',
            'SERVER_SOFTWARE' => 'Apache/2.2.21 (Unix) mod_ssl/2.2.21 OpenSSL/1.0.0e DAV/2 PHP/5.5.1',
            'SERVER_NAME' => 'dev.blog.rob',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_PORT' => '80',
            'REMOTE_ADDR' => '127.0.0.1',
            'DOCUMENT_ROOT' => '/opt/local/apache2/htdocs/Development/Flow/Applications/Blog/Web/',
            'SERVER_ADMIN' => 'rl@robertlemke.de',
            'SCRIPT_FILENAME' => '/opt/local/apache2/htdocs/Development/Flow/Applications/Blog/Web/index.php',
            'REMOTE_PORT' => '51439',
            'REDIRECT_QUERY_STRING' => 'getKey1=getValue1&getKey2=getValue2',
            'REDIRECT_URL' => '/posts/2011/11/28/laboriosam-soluta-est-minus-molestiae',
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING' => 'foo=bar',
            'REQUEST_URI' => '/posts/2011/11/28/laboriosam-soluta-est-minus-molestiae?getKey1=getValue1&getKey2=getValue2',
            'SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
            'REQUEST_TIME' => 1326472534,
        );

        $request = Request::createFromEnvironment();

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('http://dev.blog.rob/posts/2011/11/28/laboriosam-soluta-est-minus-molestiae?getKey1=getValue1&getKey2=getValue2', (string)$request->getUri());

        $_SERVER = $server;
    }

    /**
     * @test
     * @backupGlobals disabled
     */
    public function createFromEnvironmentWithEmptyServerVariableWorks()
    {
        $server = $_SERVER;

        $_GET = array();
        $_POST = array();
        $_COOKIE = array();
        $_FILES = array();
        $_SERVER = array();

        $request = Request::createFromEnvironment();

        $this->assertEquals('http://localhost/', (string)$request->getUri());

        $_SERVER = $server;
    }

    /**
     * @test
     */
    public function constructRecognizesSslSessionIdAsIndicatorForSsl()
    {
        $get = array('getKey1' => 'getValue1', 'getKey2' => 'getValue2');
        $post = array();
        $files = array();
        $server = array(
            'HTTP_HOST' => 'dev.blog.rob',
            'SERVER_NAME' => 'dev.blog.rob',
            'SERVER_ADDR' => '127.0.0.1',
            'REMOTE_ADDR' => '127.0.0.1',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING' => 'foo=bar',
            'REQUEST_URI' => '/posts/2011/11/28/laboriosam-soluta-est-minus-molestiae?getKey1=getValue1&getKey2=getValue2',
            'SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
            'SSL_SESSION_ID' => '12345'
        );

        $request = new Request($get, $post, $files, $server);
        $this->assertEquals('https', $request->getUri()->getScheme());
        $this->assertTrue($request->isSecure());
    }

    /**
     * @test
     */
    public function createUsesReasonableDefaultsForCreatingANewRequest()
    {
        $uri = new Uri('http://flow.typo3.org/foo/bar?baz=1&quux=true#at-the-very-bottom');
        $request = Request::create($uri);

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals($uri, $request->getUri());
        $this->assertEquals('HTTP/1.1', $request->getVersion());

        $uri = new Uri('https://flow.typo3.org/foo/bar?baz=1&quux=true#at-the-very-bottom');
        $request = Request::create($uri);

        $this->assertEquals($uri, $request->getUri());

        $uri = new Uri('http://flow.typo3.org/foo/bar?baz=1&quux=true#at-the-very-bottom');
        $request = Request::create($uri, 'POST');

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals($uri, $request->getUri());
    }

    /**
     * @test
     */
    public function settingVersionHasExpectedImplications()
    {
        $uri = new Uri('http://flow.typo3.org/foo/bar?baz=1&quux=true#at-the-very-bottom');
        $request = Request::create($uri);
        $request->setVersion('HTTP/1.0');

        $this->assertEquals('HTTP/1.0', $request->getVersion());
        $this->assertStringEndsWith("HTTP/1.0\r\n", $request->getRequestLine());
    }

    /**
     * @return array
     */
    public function methodCanBeOverriddenDataProvider()
    {
        return array(
            array(
                'originalMethod' => 'GET',
                'arguments' => array(),
                'server' => array(),
                'expectedMethod' => 'GET'
            ),
            array(
                'originalMethod' => 'GET',
                'arguments' => array('__method' => 'POST'),
                'server' => array(),
                'expectedMethod' => 'GET'
            ),
            array(
                'originalMethod' => 'PUT',
                'arguments' => array('__method' => 'POST'),
                'server' => array(),
                'expectedMethod' => 'PUT'
            ),
            array(
                'originalMethod' => 'POST',
                'arguments' => array('__method' => 'PUT'),
                'server' => array(),
                'expectedMethod' => 'PUT'
            ),
            // __method argument overrules HTTP_X_HTTP_METHOD_* headers
            array(
                'originalMethod' => 'POST',
                'arguments' => array('__method' => 'DELETE'),
                'server' => array('HTTP_X_HTTP_METHOD_OVERRIDE' => 'PUT'),
                'expectedMethod' => 'DELETE'
            ),
            // HTTP_X_HTTP_METHOD_OVERRIDE header overrules HTTP_X_HTTP_METHOD header
            array(
                'originalMethod' => 'POST',
                'arguments' => array(),
                'server' => array('HTTP_X_HTTP_METHOD' => 'DELETE', 'HTTP_X_HTTP_METHOD_OVERRIDE' => 'PUT'),
                'expectedMethod' => 'PUT'
            ),
        );
    }

    /**
     * @param string $originalMethod
     * @param array $arguments
     * @param array $server
     * @param string $expectedMethod
     * @test
     * @dataProvider methodCanBeOverriddenDataProvider
     */
    public function methodCanBeOverridden($originalMethod, array $arguments, array $server, $expectedMethod)
    {
        $uri = new Uri('http://flow.typo3.org');
        $request = Request::create($uri, $originalMethod, $arguments, array(), $server);
        $this->assertEquals($expectedMethod, $request->getMethod());
    }

    /**
     * HTML 2.0 and up
     * (see also HTML5, section 4.10.22.5 "URL-encoded form data")
     *
     * @test
     */
    public function createSetsTheContentTypeHeaderToFormUrlEncodedByDefaultIfRequestMethodSuggestsIt()
    {
        $uri = new Uri('http://flow.typo3.org/foo');
        $request = Request::create($uri, 'POST');

        $this->assertEquals('application/x-www-form-urlencoded', $request->getHeaders()->get('Content-Type'));
    }

    /**
     * @test
     */
    public function createActionRequestCreatesAnMvcRequestConnectedToTheParentRequest()
    {
        $uri = new Uri('http://flow.typo3.org');
        $request = Request::create($uri);

        $subRequest = $request->createActionRequest();
        $this->assertInstanceOf(\TYPO3\Flow\Mvc\ActionRequest::class, $subRequest);
        $this->assertSame($request, $subRequest->getParentRequest());
    }

    /**
     * @return array
     */
    public function requestMethods()
    {
        return array(
            array('GET'),
            array('HEAD'),
            array('POST'),
            array('Anything')
        );
    }

    /**
     * @test
     * @dataProvider requestMethods
     */
    public function setMethodAcceptsAnyRequestMethod($validMethod)
    {
        $request = Request::create(new Uri('http://flow.typo3.org'));
        $request->setMethod($validMethod);
        $this->assertSame($validMethod, $request->getMethod());
    }

    /**
     * RFC 2616 / 5.1.2
     *
     * @test
     */
    public function getReturnsTheRequestUri()
    {
        $server = array(
            'HTTP_HOST' => 'dev.blog.rob',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/foo/bar',
            'SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
        );

        $uri = new Uri('http://dev.blog.rob/foo/bar');
        $request = new Request(array(), array(), array(), $server);
        $this->assertEquals($uri, $request->getUri());
    }

    /**
     * @test
     */
    public function getContentReturnsTheRequestBodyContent()
    {
        vfsStream::setup('Foo');

        $expectedContent = 'userid=joe&password=joh316';
        file_put_contents('vfs://Foo/content.txt', $expectedContent);

        $request = Request::create(new Uri('http://flow.typo3.org'));
        $request->setContent(null);
        $this->inject($request, 'inputStreamUri', 'vfs://Foo/content.txt');

        $actualContent = $request->getContent();
        $this->assertEquals($expectedContent, $actualContent);
    }

    /**
     * @test
     */
    public function getContentReturnsTheRequestBodyContentAsResourcePointerIfRequested()
    {
        vfsStream::setup('Foo');

        $expectedContent = 'userid=joe&password=joh316';
        file_put_contents('vfs://Foo/content.txt', $expectedContent);

        $request = Request::create(new Uri('http://flow.typo3.org'));
        $request->setContent(null);
        $this->inject($request, 'inputStreamUri', 'vfs://Foo/content.txt');

        $resource = $request->getContent(true);
        $actualContent = fread($resource, strlen($expectedContent));

        $this->assertSame($expectedContent, $actualContent);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Http\Exception
     */
    public function getContentThrowsAnExceptionOnTryingToRetrieveContentAsResourceAlthoughItHasBeenRetrievedPreviously()
    {
        vfsStream::setup('Foo');

        file_put_contents('vfs://Foo/content.txt', 'xy');

        $request = Request::create(new Uri('http://flow.typo3.org'));
        $this->inject($request, 'inputStreamUri', 'vfs://Foo/content.txt');

        $request->getContent(true);
        $request->getContent(true);
    }

    /**
     * @test
     */
    public function renderHeadersReturnsRawHttpHeadersAccordingToTheRequestProperties()
    {
        $server = array(
                'HTTP_HOST' => 'dev.blog.rob',
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/foo/bar',
                'SCRIPT_NAME' => '/index.php',
                'PHP_SELF' => '/index.php',
        );

        $request = Request::create(new Uri('http://dev.blog.rob/?foo=bar'), 'PUT', array(), array(), $server);

        $expectedHeaders =
            "PUT /?foo=bar HTTP/1.1\r\n" .
            'User-Agent: Flow/' . FLOW_VERSION_BRANCH . ".x\r\n" .
            "Host: dev.blog.rob\r\n" .
            "Content-Type: application/x-www-form-urlencoded\r\n";

        $this->assertEquals($expectedHeaders, $request->renderHeaders());
    }

    /**
     * @test
     */
    public function toStringReturnsRawHttpRequestAccordingToTheRequestProperties()
    {
        $server = array(
                'HTTP_HOST' => 'dev.blog.rob',
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/foo/bar',
                'SCRIPT_NAME' => '/index.php',
                'PHP_SELF' => '/index.php',
        );

        $request = Request::create(new Uri('http://dev.blog.rob/?foo=bar'), 'PUT', array(), array(), $server);
        $request->setContent('putArgument=first value');
        $expectedRawRequest =
            "PUT /?foo=bar HTTP/1.1\r\n" .
            'User-Agent: Flow/' . FLOW_VERSION_BRANCH . ".x\r\n" .
            "Host: dev.blog.rob\r\n" .
            "Content-Type: application/x-www-form-urlencoded\r\n" .
            "\r\n" .
            'putArgument=first value';

        $this->assertEquals($expectedRawRequest, (string)$request);
    }

    /**
     * Data Provider
     */
    public function acceptHeaderValuesAndCorrespondingListOfMediaTypes()
    {
        return array(
            array(null, array('*/*')),
            array('', array('*/*')),
            array('text/html', array('text/html')),
            array('application/json; q=0.5, application/json; charset=UTF-8', array('application/json; charset=UTF-8', 'application/json')),
            array('audio/*; q=0.2, audio/basic', array('audio/basic', 'audio/*')),
            array('text/plain; q=0.5, text/html, text/x-dvi; q=0.8, text/x-c', array('text/html', 'text/x-c', 'text/x-dvi', 'text/plain')),
            array('text/*, text/html, text/html;level=1, */*', array('text/html;level=1', 'text/html', 'text/*', '*/*')),
            array('text/html;level=1, text/*, text/html, text/html;level=2, */*', array('text/html;level=1', 'text/html;level=2', 'text/html', 'text/*', '*/*')),
        );
    }

    /**
     * RFC 2616 / 14.1 (Accept)
     *
     * @test
     * @dataProvider acceptHeaderValuesAndCorrespondingListOfMediaTypes
     */
    public function getAcceptedMediaTypesReturnsAnOrderedListOfMediaTypesDefinedInTheAcceptHeader($rawValues, $expectedMediaTypes)
    {
        $request = Request::create(new Uri('http://localhost'));
        if ($rawValues !== null) {
            $request->setHeader('Accept', $rawValues);
        }
        $this->assertSame($expectedMediaTypes, $request->getAcceptedMediaTypes());
    }

    /**
     * Data Provider
     */
    public function preferedSupportedAndNegotiatedMediaTypes()
    {
        return array(
            array('text/html', array(), null),
            array('text/plain', array('text/html', 'application/json'), null),
            array('application/json; charset=UTF-8', array('text/html', 'application/json'), 'application/json'),
            array(null, array('text/plain'), 'text/plain'),
            array('', array('text/html', 'application/json'), 'text/html'),
            array('application/flow, application/json', array('text/html', 'application/json'), 'application/json'),
        );
    }

    /**
     * RFC 2616 / 14.1 (Accept)
     *
     * @param string $preferredTypes
     * @param array $supportedTypes
     * @param string $negotiatedType
     * @test
     * @dataProvider preferedSupportedAndNegotiatedMediaTypes()
     */
    public function getNegotiatedMediaTypeReturnsMediaTypeBasedOnContentNegotiation($preferredTypes, array $supportedTypes, $negotiatedType)
    {
        $request = Request::create(new Uri('http://localhost'));
        if ($preferredTypes !== null) {
            $request->setHeader('Accept', $preferredTypes);
        }
        $this->assertSame($negotiatedType, $request->getNegotiatedMediaType($supportedTypes));
    }

    /**
     * @test
     */
    public function getBaseUriReturnsTheDetectedBaseUri()
    {
        $server = array(
            'HTTP_HOST' => 'dev.blog.rob',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/foo/bar',
            'SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
        );

        $request = new Request(array(), array(), array(), $server);
        $this->assertEquals('http://dev.blog.rob/', (string)$request->getBaseUri());

        $server = array(
            'HTTP_HOST' => 'dev.blog.rob',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/foo/bar',
            'ORIG_SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
        );

        $request = new Request(array(), array(), array(), $server);
        $this->assertEquals('http://dev.blog.rob/', (string)$request->getBaseUri());

        $server = array(
            'HTTP_HOST' => 'dev.blog.rob',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/foo/bar',
            'PHP_SELF' => '/index.php',
        );

        $request = new Request(array(), array(), array(), $server);
        $this->assertEquals('http://dev.blog.rob/', (string)$request->getBaseUri());
    }

    /**
     * @test
     */
    public function getBaseUriReturnsThePresetBaseUriIfItHasBeenSet()
    {
        $server = array(
            'HTTP_HOST' => 'dev.blog.rob',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/foo/bar',
            'SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
        );

        $request = new Request(array(), array(), array(), $server);

        $baseUri = new \TYPO3\Flow\Http\Uri('http://prod.blog.rob/');
        $request->setBaseUri($baseUri);
        $this->assertEquals('http://prod.blog.rob/', (string)$request->getBaseUri());
    }

    /**
     * Data Provider
     */
    public function variousArguments()
    {
        return array(
            array('GET', 'http://dev.blog.rob/foo/bar?baz=quux&coffee=due', array(), array(), array('baz' => 'quux', 'coffee' => 'due')),
            array('GET', 'http://dev.blog.rob/foo/bar?baz=quux&coffee=due', array('post' => 'var'), array(), array('baz' => 'quux', 'coffee' => 'due', 'post' => 'var')),
            array('POST', 'http://dev.blog.rob/foo/bar?baz=quux&coffee=due', array('post' => 'var'), array(), array('baz' => 'quux', 'coffee' => 'due', 'post' => 'var')),
        );
    }

    /**
     * @test
     * @dataProvider variousArguments
     */
    public function getArgumentsReturnsGetAndPostArguments($method, $uriString, $postArguments, $filesArguments, $expectedArguments)
    {
        $request = Request::create(new Uri($uriString), $method, $postArguments, array(), $filesArguments);
        $this->assertEquals($expectedArguments, $request->getArguments());
    }

    /**
     * @test
     */
    public function singleArgumentsCanBeCheckedAndRetrieved()
    {
        $request = Request::create(new Uri('http://dev.blog.rob/foo/bar?baz=quux&coffee=due'));
        $this->assertTrue($request->hasArgument('baz'));
        $this->assertEquals('quux', $request->getArgument('baz'));
        $this->assertFalse($request->hasArgument('tea'));
        $this->assertNull($request->getArgument('tea'));
    }

    /**
     * @test
     */
    public function httpHostIsNotAppendedByColonIfNoExplicitPortIsGiven()
    {
        $request = Request::create(new Uri('http://dev.blog.rob/noPort/isGivenHere'));
        $this->assertEquals('dev.blog.rob', $request->getHeader('Host'));
    }

    /**
     * RFC 2616 / 14.23 (Host)
     * @test
     */
    public function standardPortsAreRecognizedCorrectly()
    {
        $request = Request::create(new Uri('http://dev.blog.rob:80/foo/bar?baz=quux&coffee=due'));
        $this->assertSame(80, $request->getUri()->getPort());
        $this->assertSame(80, $request->getPort());
    }

    /**
     * RFC 2616 / 14.23 (Host)
     * @test
     */
    public function nonStandardPortIsRecognizedCorrectly()
    {
        $request = Request::create(new Uri('http://dev.blog.rob:8080/foo/bar?baz=quux&coffee=due'));
        $this->assertSame(8080, $request->getUri()->getPort());
        $this->assertSame(8080, $request->getPort());
    }

    /**
     * RFC 2616 / 14.23 (Host)
     * @test
     */
    public function nonStandardPortIsAddedToServerPort()
    {
        $request = Request::create(new Uri('http://dev.blog.rob:8080/foo/bar?baz=quux&coffee=due'));
        $reflectedServerProperty = new \ReflectionProperty(get_class($request), 'server');
        $reflectedServerProperty->setAccessible(true);
        $serverValue = $reflectedServerProperty->getValue($request);
        $this->assertSame(8080, $serverValue['SERVER_PORT']);
    }

    /**
     * RFC 2616 / 14.23 (Host)
     * @test
     */
    public function nonStandardHttpsPortIsAddedToHttpHost()
    {
        $request = Request::create(new Uri('https://dev.blog.rob:44343/foo/bar?baz=quux&coffee=due'));
        $this->assertSame(44343, $request->getUri()->getPort());
    }

    /**
     * RFC 2616 / 14.23 (Host)
     * @test
     */
    public function standardHttpsPortIsRecognizedCorrectly()
    {
        $request = Request::create(new Uri('https://dev.blog.rob/foo/bar?baz=quux&coffee=due'));
        $this->assertSame(443, $request->getUri()->getPort());
    }

    /**
     * RFC 2616 / 14.23 (Host)
     * @test
     */
    public function nonStandardHttpsPortIsAddedToServerPort()
    {
        $request = Request::create(new Uri('https://dev.blog.rob:44343/foo/bar?baz=quux&coffee=due'));
        $reflectedServerProperty = new \ReflectionProperty(get_class($request), 'server');
        $reflectedServerProperty->setAccessible(true);
        $serverValue = $reflectedServerProperty->getValue($request);
        $this->assertSame(44343, $serverValue['SERVER_PORT']);
    }

    /**
     * @test
     */
    public function setContentAlsoAcceptsAFileHandleAsInput()
    {
        $fileHandler = fopen(__FILE__, 'r');

        $request = Request::create(new Uri('http://dev.blog.rob/?foo=bar'), 'POST');
        $request->setContent($fileHandler);

        $this->assertSame($fileHandler, $request->getContent());
    }

    /**
     * @test
     */
    public function setContentAlsoAcceptsAStreamAsInputAndSetsContentLengthAndTypeAccordingly()
    {
        $streamHandler = fopen('file://' . __FILE__, 'r');

        $request = Request::create(new Uri('http://dev.blog.rob/?foo=bar'), 'POST');
        $request->setContent($streamHandler);

        $this->assertSame($streamHandler, $request->getContent());
        $this->assertEquals('application/octet-stream', $request->getHeader('Content-Type'));
        $this->assertEquals(filesize(__FILE__), $request->getHeader('Content-Length'));
    }

    /**
     * RFC 2616 / 9.1.1
     *
     * @test
     */
    public function isMethodSafeReturnsTrueIfTheRequestMethodIsGetOrHead()
    {
        $request = Request::create(new Uri('http://acme.com'), 'GET');
        $this->assertTrue($request->isMethodSafe());

        $request = Request::create(new Uri('http://acme.com'), 'HEAD');
        $this->assertTrue($request->isMethodSafe());

        $request = Request::create(new Uri('http://acme.com'), 'POST');
        $this->assertFalse($request->isMethodSafe());

        $request = Request::create(new Uri('http://acme.com'), 'PUT');
        $this->assertFalse($request->isMethodSafe());

        $request = Request::create(new Uri('http://acme.com'), 'DELETE');
        $this->assertFalse($request->isMethodSafe());
    }

    /**
     * @test
     */
    public function untangleFilesArrayTransformsTheFilesSuperglobalIntoAMangeableForm()
    {
        $convolutedFiles = array(
            'a0' => array(
                'name' => array(
                    'a1' => 'a.txt',
                ),
                'type' => array(
                    'a1' => 'text/plain',
                ),
                'tmp_name' => array(
                    'a1' => '/private/var/tmp/phpbqXsYt',
                ),
                'error' => array(
                    'a1' => 0,
                ),
                'size' => array(
                    'a1' => 100,
                ),
            ),
            'b0' => array(
                'name' => array(
                    'b1' => 'b.txt',
                ),
                'type' => array(
                    'b1' => 'text/plain',
                ),
                'tmp_name' => array(
                    'b1' => '/private/var/tmp/phpvZ6oUD',
                ),
                'error' => array(
                    'b1' => 0,
                ),
                'size' => array(
                    'b1' => 200,
                ),
            ),
            'c' => array(
                'name' => 'c.txt',
                'type' => 'text/plain',
                'tmp_name' => '/private/var/tmp/phpS9KMNw',
                'error' => 0,
                'size' => 300,
            ),
            'd0' => array(
                'name' => array(
                    'd1' => array(
                        'd2' => array(
                            'd3' => 'd.txt',
                        ),
                    ),
                ),
                'type' => array(
                    'd1' => array(
                        'd2' => array(
                            'd3' => 'text/plain',
                        ),
                    ),
                ),
                'tmp_name' => array(
                    'd1' => array(
                        'd2' => array(
                            'd3' => '/private/var/tmp/phprR3fax',
                        ),
                    ),
                ),
                'error' => array(
                    'd1' => array(
                        'd2' => array(
                            'd3' => 0,
                        ),
                    ),
                ),
                'size' => array(
                    'd1' => array(
                        'd2' => array(
                            'd3' => 400,
                        ),
                    ),
                ),
            ),
            'e0' => array(
                'name' => array(
                    'e1' => array(
                        'e2' => array(
                            0 => 'e_one.txt',
                            1 => 'e_two.txt',
                        ),
                    ),
                ),
                'type' => array(
                    'e1' => array(
                        'e2' => array(
                            0 => 'text/plain',
                            1 => 'text/plain',
                        ),
                    ),
                ),
                'tmp_name' => array(
                    'e1' => array(
                        'e2' => array(
                            0 => '/private/var/tmp/php01fitB',
                            1 => '/private/var/tmp/phpUUB2cv',
                        ),
                    ),
                ),
                'error' => array(
                    'e1' => array(
                        'e2' => array(
                            0 => 0,
                            1 => 0,
                        ),
                    ),
                ),
                'size' => array(
                    'e1' => array(
                        'e2' => array(
                            0 => 510,
                            1 => 520,
                        )
                    )
                )
            )
        );

        $untangledFiles = array(
            'a0' => array(
                'a1' => array(
                    'name' => 'a.txt',
                    'type' => 'text/plain',
                    'tmp_name' => '/private/var/tmp/phpbqXsYt',
                    'error' => 0,
                    'size' => 100,
                ),
            ),
            'b0' => array(
                'b1' => array(
                    'name' => 'b.txt',
                    'type' => 'text/plain',
                    'tmp_name' => '/private/var/tmp/phpvZ6oUD',
                    'error' => 0,
                    'size' => 200,
                )
            ),
            'c' => array(
                'name' => 'c.txt',
                'type' => 'text/plain',
                'tmp_name' => '/private/var/tmp/phpS9KMNw',
                'error' => 0,
                'size' => 300,
            ),
            'd0' => array(
                'd1' => array(
                    'd2' => array(
                        'd3' => array(
                            'name' => 'd.txt',
                            'type' => 'text/plain',
                            'tmp_name' => '/private/var/tmp/phprR3fax',
                            'error' => 0,
                            'size' => 400,
                        ),
                    ),
                ),
            ),
            'e0' => array(
                'e1' => array(
                    'e2' => array(
                        0 => array(
                            'name' => 'e_one.txt',
                            'type' => 'text/plain',
                            'tmp_name' => '/private/var/tmp/php01fitB',
                            'error' => 0,
                            'size' => 510,
                        ),
                        1 => array(
                            'name' => 'e_two.txt',
                            'type' => 'text/plain',
                            'tmp_name' => '/private/var/tmp/phpUUB2cv',
                            'error' => 0,
                            'size' => 520,
                        )
                    )
                )
            )
        );

        $request = $this->getAccessibleMock(\TYPO3\Flow\Http\Request::class, array('dummy'), array(), '', false);
        $result = $request->_call('untangleFilesArray', $convolutedFiles);

        $this->assertSame($untangledFiles, $result);
    }

    /**
     * @test
     */
    public function untangleFilesArrayDoesNotChangeArgumentsIfNoFileWasUploaded()
    {
        $convolutedFiles = array(
            'a0' => array(
                'name' => array(
                    'a1' => '',
                ),
                'type' => array(
                    'a1' => '',
                ),
                'tmp_name' => array(
                    'a1' => '',
                ),
                'error' => array(
                    'a1' => \UPLOAD_ERR_NO_FILE,
                ),
                'size' => array(
                    'a1' => 0,
                ),
            ),
            'b0' => array(
                'name' => array(
                    'b1' => 'b.txt',
                ),
                'type' => array(
                    'b1' => 'text/plain',
                ),
                'tmp_name' => array(
                    'b1' => '/private/var/tmp/phpvZ6oUD',
                ),
                'error' => array(
                    'b1' => 0,
                ),
                'size' => array(
                    'b1' => 200,
                ),
            ),
        );

        $untangledFiles = array(
            'b0' => array(
                'b1' => array(
                    'name' => 'b.txt',
                    'type' => 'text/plain',
                    'tmp_name' => '/private/var/tmp/phpvZ6oUD',
                    'error' => 0,
                    'size' => 200,
                )
            ),
        );

        $request = $this->getAccessibleMock(\TYPO3\Flow\Http\Request::class, array('dummy'), array(), '', false);
        $result = $request->_call('untangleFilesArray', $convolutedFiles);

        $this->assertSame($untangledFiles, $result);
    }

    /**
     * Data provider with valid quality value strings and the expected parse output
     *
     * @return array
     */
    public function qualityValues()
    {
        return array(
            array('text/html', array('text/html')),
            array('audio/*; q=0.2, audio/basic', array('audio/basic', 'audio/*')),
            array('application/json; charset=UTF-8, text/html; q=0.8', array('application/json; charset=UTF-8', 'text/html')),
            array('text/plain; q=0.5, text/html, text/x-dvi; q=0.8, text/x-c', array('text/html', 'text/x-c', 'text/x-dvi', 'text/plain')),
            array('text/html,application/xml;q=0.9,application/xhtml+xml,*/*;q=0.8', array('text/html', 'application/xhtml+xml', 'application/xml', '*/*')),
            array('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', array('text/html', 'application/xhtml+xml', 'application/xml', '*/*')),
        );
    }

    /**
     * @param string $rawValues The unparsed header field
     * @param array $expectedValues The expected parse result
     * @test
     * @dataProvider qualityValues
     */
    public function parseContentNegotiationQualityValuesReturnsNormalizedAndOrderListOfPreferredValues($rawValues, $expectedValues)
    {
        $request = $this->getAccessibleMock(\TYPO3\Flow\Http\Request::class, array('dummy'), array(), '', false);
        $actualValues = $request->_call('parseContentNegotiationQualityValues', $rawValues);
        $this->assertSame($expectedValues, $actualValues);
    }

    /**
     * @test
     */
    public function getRelativePathCorrectlyTrimsBaseUri()
    {
        $request = Request::create(new Uri('http://dev.blog.rob/amnesia/spray'), 'GET');
        $relativePath = $request->getRelativePath();

        $this->assertSame($relativePath, 'amnesia/spray');
    }

    /**
     * @test
     */
    public function getRelativePathReturnsEmptyStringForHomepage()
    {
        $request = Request::create(new Uri('http://dev.blog.rob/'), 'GET');
        $relativePath = $request->getRelativePath();

        $this->assertSame($relativePath, '');
    }

    /**
     * @return array
     */
    public function constructorCorrectlyStripsOffIndexPhpFromRequestUriDataProvider()
    {
        return array(
            array('host' => null, 'requestUri' => null, 'expectedUri' => 'http://localhost/'),
            array('host' => null, 'requestUri' => '/index.php', 'expectedUri' => 'http://localhost/'),
            array('host' => 'localhost', 'requestUri' => '/foo/bar/index.php', 'expectedUri' => 'http://localhost/foo/bar/index.php'),
            array('host' => 'dev.blog.rob', 'requestUri' => '/index.phpx', 'expectedUri' => 'http://dev.blog.rob/x'),
            array('host' => 'dev.blog.rob', 'requestUri' => '/index.php?someParameter=someValue', 'expectedUri' => 'http://dev.blog.rob/?someParameter=someValue'),
        );
    }

    /**
     * @param string $host
     * @param string $requestUri
     * @param string $expectedUri
     * @test
     * @dataProvider constructorCorrectlyStripsOffIndexPhpFromRequestUriDataProvider
     */
    public function constructorCorrectlyStripsOffIndexPhpFromRequestUri($host, $requestUri, $expectedUri)
    {
        $server = array(
            'HTTP_HOST' => $host,
            'REQUEST_URI' => $requestUri
        );
        $request = new Request(array(), array(), array(), $server);
        $this->assertEquals($expectedUri, (string)$request->getUri());
    }

    /**
     * @test
     *
     * Note: This is a fix for https://jira.neos.io/browse/FLOW-324 (see https://code.google.com/p/chromium/issues/detail?id=501095)
     */
    public function constructorIgnoresHttpsHeader()
    {
        $server = array(
            'HTTP_HTTPS' => '1',
        );
        new Request(array(), array(), array(), $server);

        // dummy assertion to avoid PHPUnit warning
        $this->assertTrue(true);
    }
}
