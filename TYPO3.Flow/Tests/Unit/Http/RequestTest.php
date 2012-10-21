<?php
namespace TYPO3\Flow\Tests\Unit\Http;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Uri;
use org\bovigo\vfs\vfsStream;

/**
 * Testcase for the Http Request class
 */
class RequestTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function createFromEnvironmentCreatesAReasonableRequestObjectFromTheSuperGlobals() {
		$_GET = array('getKey1' => 'getValue1', 'getKey2' => 'getValue2');
		$_POST = array();
		$_COOKIE = array();
		$_FILES = array();
		$_SERVER = array (
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
			'SERVER_SOFTWARE' => 'Apache/2.2.21 (Unix) mod_ssl/2.2.21 OpenSSL/1.0.0e DAV/2 PHP/5.3.8',
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
	}

	/**
	 * @test
	 */
	public function createFromEnvironmentWithEmptyServerVariableWorks() {
		$_GET = array();
		$_POST = array();
		$_COOKIE = array();
		$_FILES = array();
		$_SERVER = array();

		$request = Request::createFromEnvironment();

		$this->assertEquals('http://localhost/', (string)$request->getUri());
	}

	/**
	 * @test
	 */
	public function constructRecognizesSslSessionIdAsIndicatorForSsl() {
		$get = array('getKey1' => 'getValue1', 'getKey2' => 'getValue2');
		$post = array();
		$cookie = array();
		$files = array();
		$server = array (
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
	public function createUsesReasonableDefaultsForCreatingANewRequest() {
		$uri = new Uri('http://flow.typo3.org/foo/bar?baz=1&quux=true#at-the-very-bottom');
		$request = Request::create($uri);

		$this->assertEquals('GET', $request->getMethod());
		$this->assertEquals($uri, $request->getUri());

		$uri = new Uri('https://flow.typo3.org/foo/bar?baz=1&quux=true#at-the-very-bottom');
		$request = Request::create($uri);

		$this->assertEquals($uri, $request->getUri());

		$uri = new Uri('http://flow.typo3.org/foo/bar?baz=1&quux=true#at-the-very-bottom');
		$request = Request::create($uri, 'POST');

		$this->assertEquals('POST', $request->getMethod());
		$this->assertEquals($uri, $request->getUri());
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @test
	 */
	public function createRejectsInvalidMethods() {
		$uri = new Uri('http://flow.typo3.org/foo/bar?baz=1&quux=true#at-the-very-bottom');
		Request::create($uri, 'STEAL');
	}

	/**
	 * HTML 2.0 and up
	 * (see also HTML5, section 4.10.22.5 "URL-encoded form data")
	 *
	 * @test
	 */
	public function createSetsTheContentTypeHeaderToFormUrlEncodedByDefaultIfRequestMethodSuggestsIt() {
		$uri = new Uri('http://flow.typo3.org/foo');
		$request = Request::create($uri, 'POST');

		$this->assertEquals('application/x-www-form-urlencoded', $request->getHeaders()->get('Content-Type'));
	}

	/**
	 * @test
	 */
	public function createSubRequestCreatesAnMvcRequestConnectedToTheParentRequest() {
		$uri = new Uri('http://flow.typo3.org');
		$request = Request::create($uri);

		$subRequest = $request->createActionRequest();
		$this->assertInstanceOf('TYPO3\Flow\Mvc\ActionRequest', $subRequest);
		$this->assertSame($request, $subRequest->getParentRequest());
	}

	/**
	 * @test
	 */
	public function createSubRequestMapsTheArgumentsOfTheHttpRequestToTheNewActionRequest() {
		$uri = new Uri('http://flow.typo3.org/page.html?foo=bar&__baz=quux');
		$request = Request::create($uri);

		$subRequest = $request->createActionRequest();
		$this->assertEquals('bar', $subRequest->getArgument('foo'));
		$this->assertEquals('quux', $subRequest->getInternalArgument('__baz'));
	}

	/**
	 * @return array
	 */
	public function invalidMethods() {
		return array(
			array('get'),
			array('mett'),
			array('post'),
		);
	}

	/**
	 * RFC 2616 / 5.1.1
	 *
	 * @test
	 * @dataProvider invalidMethods
	 * @expectedException InvalidArgumentException
	 */
	public function setMethodDoesNotAcceptInvalidRequestMethods($invalidMethod) {
		$request = Request::create(new Uri('http://flow.typo3.org'));
		$request->setMethod($invalidMethod);
	}

	/**
	 * @return array
	 */
	public function validMethods() {
		return array(
			array('GET'),
			array('HEAD'),
			array('POST'),
		);
	}

	/**
	 * RFC 2616 / 5.1.1
	 *
	 * @test
	 * @dataProvider validMethods
	 */
	public function setMethodAcceptsValidRequestMethods($validMethod) {
		$request = Request::create(new Uri('http://flow.typo3.org'));
		$request->setMethod($validMethod);
		$this->assertSame($validMethod, $request->getMethod());
	}

	/**
	 * RFC 2616 / 5.1.2
	 *
	 * @test
	 */
	public function getReturnsTheRequestUri() {
		$server = array (
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
	public function getContentReturnsTheRequestBodyContent() {
		vfsStream::setup('Foo');

		$expectedContent = 'userid=joe&password=joh316';
		file_put_contents('vfs://Foo/content.txt', $expectedContent);

		$request = Request::create(new Uri('http://flow.typo3.org'));
		$this->inject($request, 'inputStreamUri', 'vfs://Foo/content.txt');

		$actualContent = $request->getContent();
		$this->assertEquals($expectedContent, $actualContent);
	}

	/**
	 * @test
	 */
	public function getContentReturnsTheRequestBodyContentAsResourcePointerIfRequested() {
		vfsStream::setup('Foo');

		$expectedContent = 'userid=joe&password=joh316';
		file_put_contents('vfs://Foo/content.txt', $expectedContent);

		$request = Request::create(new Uri('http://flow.typo3.org'));
		$this->inject($request, 'inputStreamUri', 'vfs://Foo/content.txt');

		$resource = $request->getContent(TRUE);
		$actualContent = fread($resource, strlen($expectedContent));

		$this->assertSame($expectedContent, $actualContent);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Http\Exception
	 */
	public function getContentThrowsAnExceptionOnTryingToRetrieveContentAsResourceAlthoughItHasBeenRetrievedPreviously() {
		vfsStream::setup('Foo');

		file_put_contents('vfs://Foo/content.txt', 'xy');

		$request = Request::create(new Uri('http://flow.typo3.org'));
		$this->inject($request, 'inputStreamUri', 'vfs://Foo/content.txt');

		$request->getContent(TRUE);
		$request->getContent(TRUE);
	}

	/**
	 * Data Provider
	 */
	public function serverEnvironmentsForClientIpAddresses() {
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
	public function getClientIpAddressReturnsTheIpAddressDerivedFromSeveralServerEnvironmentVariables(array $serverEnvironment, $expectedIpAddress) {
		$defaultServerEnvironment = array(
			'HTTP_USER_AGENT' => 'Flow/' . FLOW_VERSION_BRANCH . '.x',
			'HTTP_HOST' => 'flow.typo3.org',
			'SERVER_NAME' => 'typo3.org',
			'SERVER_ADDR' => '217.29.36.55',
			'SERVER_PORT' => 80,
			'REMOTE_ADDR' => '17.172.224.47',
			'SCRIPT_FILENAME' => FLOW_PATH_WEB . 'index.php',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'SCRIPT_NAME' => '/index.php',
			'PHP_SELF' => '/index.php',
		);

		$request = Request::create(new Uri('http://flow.typo3.org'), 'GET', array(), array(), array_replace($defaultServerEnvironment, $serverEnvironment));
		$this->assertSame($expectedIpAddress, $request->getClientIpAddress());
	}

	/**
	 * Data Provider
	 */
	public function acceptHeaderValuesAndCorrespondingListOfMediaTypes() {
		return array(
			array(NULL, array('*/*')),
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
	public function getAcceptedMediaTypesReturnsAnOrderedListOfMediaTypesDefinedInTheAcceptHeader($rawValues, $expectedMediaTypes) {
		$request = Request::create(new Uri('http://localhost'));
		if ($rawValues !== NULL) {
			$request->setHeader('Accept', $rawValues);
		}
		$this->assertSame($expectedMediaTypes, $request->getAcceptedMediaTypes());
	}

	/**
	 * Data Provider
	 */
	public function preferedSupportedAndNegotiatedMediaTypes() {
		return array(
			array('text/html', array(), NULL),
			array('text/plain', array('text/html', 'application/json'), NULL),
			array('application/json; charset=UTF-8', array('text/html', 'application/json'), 'application/json'),
			array(NULL, array('text/plain'), 'text/plain'),
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
	public function getNegotiatedMediaTypeReturnsMediaTypeBasedOnContentNegotiation($preferredTypes, array $supportedTypes, $negotiatedType) {
		$request = Request::create(new Uri('http://localhost'));
		if ($preferredTypes !== NULL) {
			$request->setHeader('Accept', $preferredTypes);
		}
		$this->assertSame($negotiatedType, $request->getNegotiatedMediaType($supportedTypes));
	}


	/**
	 * @test
	 */
	public function getBaseUriReturnsTheDetectedBaseUri() {
		$server = array (
			'HTTP_HOST' => 'dev.blog.rob',
			'REQUEST_METHOD' => 'GET',
			'REQUEST_URI' => '/foo/bar',
			'SCRIPT_NAME' => '/index.php',
			'PHP_SELF' => '/index.php',
		);

		$request = new Request(array(), array(), array(), $server);
		$this->assertEquals('http://dev.blog.rob/', (string)$request->getBaseUri());

		$server = array (
			'HTTP_HOST' => 'dev.blog.rob',
			'REQUEST_METHOD' => 'GET',
			'REQUEST_URI' => '/foo/bar',
			'ORIG_SCRIPT_NAME' => '/index.php',
			'PHP_SELF' => '/index.php',
		);

		$request = new Request(array(), array(), array(), $server);
		$this->assertEquals('http://dev.blog.rob/', (string)$request->getBaseUri());

		$server = array (
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
	public function getBaseUriReturnsThePresetBaseUriIfItHasBeenSet() {
		$server = array (
			'HTTP_HOST' => 'dev.blog.rob',
			'REQUEST_METHOD' => 'GET',
			'REQUEST_URI' => '/foo/bar',
			'SCRIPT_NAME' => '/index.php',
			'PHP_SELF' => '/index.php',
		);

		$settings = array(
			'http' => array(
				'baseUri' => 'http://prod.blog.rob/'
			)
		);

		$request = new Request(array(), array(), array(), array(), $server);
		$request->injectSettings($settings);
		$this->assertEquals('http://prod.blog.rob/', (string)$request->getBaseUri());
	}

	/**
	 * Data Provider
	 */
	public function variousArguments() {
		return array(
			array('GET', 'http://dev.blog.rob/foo/bar?baz=quux&coffee=due', array(), array(), array('baz' => 'quux', 'coffee' => 'due')),
			array('GET', 'http://dev.blog.rob/foo/bar?baz=quux&coffee=due', array('ignore' => 'me'), array(), array('baz' => 'quux', 'coffee' => 'due')),
			array('POST', 'http://dev.blog.rob/foo/bar?baz=quux&coffee=due', array('dontignore' => 'me'), array(), array('baz' => 'quux', 'coffee' => 'due', 'dontignore' => 'me')),
		);
	}

	/**
	 * @test
	 * @dataProvider variousArguments
	 */
	public function getArgumentsReturnsGetAndPostArguments($method, $uriString, $postArguments, $filesArguments, $expectedArguments) {
		$request = Request::create(new Uri($uriString), $method, $postArguments, array(), $filesArguments);
		$this->assertEquals($expectedArguments, $request->getArguments());
	}

	/**
	 * @test
	 */
	public function singleArgumentsCanBeCheckedAndRetrieved() {
		$request = Request::create(new Uri('http://dev.blog.rob/foo/bar?baz=quux&coffee=due'));
		$this->assertTrue($request->hasArgument('baz'));
		$this->assertEquals('quux', $request->getArgument('baz'));
		$this->assertFalse($request->hasArgument('tea'));
		$this->assertNull($request->getArgument('tea'));
	}

	/**
	 * @test
	 */
	public function httpHostIsNotAppendedByColonIfNoExplicitPortIsGiven() {
		$request = Request::create(new Uri('http://dev.blog.rob/noPort/isGivenHere'));
		$this->assertEquals('dev.blog.rob', $request->getHeader('Host'));
	}

	/**
	 * RFC 2616 / 14.23 (Host)
	 * @test
	 */
	public function standardPortIsNotAddedToHttpHost() {
		$request = Request::create(new Uri('http://dev.blog.rob:80/foo/bar?baz=quux&coffee=due'));
		$this->assertNull($request->getUri()->getPort());
	}

	/**
	 * RFC 2616 / 14.23 (Host)
	 * @test
	 */
	public function nonStandardPortIsAddedToHttpHost() {
		$request = Request::create(new Uri('http://dev.blog.rob:8080/foo/bar?baz=quux&coffee=due'));
		$this->assertSame(8080, $request->getUri()->getPort());
	}

	/**
	 * RFC 2616 / 14.23 (Host)
	 * @test
	 */
	public function nonStandardPortIsAddedToServerPort() {
		$request = Request::create(new Uri('http://dev.blog.rob:8080/foo/bar?baz=quux&coffee=due'));
		$reflectedServerProperty = new \ReflectionProperty(get_class($request), 'server');
		$reflectedServerProperty->setAccessible(TRUE);
		$serverValue = $reflectedServerProperty->getValue($request);
		$this->assertSame(8080, $serverValue['SERVER_PORT']);
	}

	/**
	 * RFC 2616 / 14.23 (Host)
	 * @test
	 */
	public function nonStandardHttpsPortIsAddedToHttpHost() {
		$request = Request::create(new Uri('https://dev.blog.rob:44343/foo/bar?baz=quux&coffee=due'));
		$this->assertSame(44343, $request->getUri()->getPort());
	}

	/**
	 * RFC 2616 / 14.23 (Host)
	 * @test
	 */
	public function standardHttpsPortIsNotAddedToHttpHost() {
		$request = Request::create(new Uri('https://dev.blog.rob:443/foo/bar?baz=quux&coffee=due'));
		$this->assertNull($request->getUri()->getPort());
	}

	/**
	 * RFC 2616 / 14.23 (Host)
	 * @test
	 */
	public function nonStandardHttpsPortIsAddedToServerPort() {
		$request = Request::create(new Uri('https://dev.blog.rob:44343/foo/bar?baz=quux&coffee=due'));
		$reflectedServerProperty = new \ReflectionProperty(get_class($request), 'server');
		$reflectedServerProperty->setAccessible(TRUE);
		$serverValue = $reflectedServerProperty->getValue($request);
		$this->assertSame(44343, $serverValue['SERVER_PORT']);
	}

	/**
	 * @test
	 */
	public function setContentRebuildsUnifiedArgumentsToIntegratePutArguments() {
		$request = Request::create(new Uri('http://dev.blog.rob/?foo=bar'), 'PUT');
		$request->setContent('putArgument=first value');
		$this->assertEquals('first value', $request->getArgument('putArgument'));
		$this->assertEquals('bar', $request->getArgument('foo'));
	}

	/**
	 * Data provider
	 */
	public function contentTypesBodiesAndExpectedUnifiedArguments() {
		return array(
			array('application/json', '{"jsonArgument":"jsonValue"}', array('jsonArgument' => 'jsonValue')),
			array('application/json', 'invalid json source code', array()),
			array('application/json; charset=UTF-8', '{"jsonArgument":"jsonValue"}', array('jsonArgument' => 'jsonValue')),
			array('application/xml', '<root><xmlArgument>xmlValue</xmlArgument></root>', array('xmlArgument' => 'xmlValue')),
			array('text/xml', '<root><xmlArgument>xmlValue</xmlArgument><!-- text/xml is, by the way, meant to be readable by "the casual user" --></root>', array('xmlArgument' => 'xmlValue')),
			array('text/xml', '<invalid xml source code>', array()),
			array('application/xml;charset=UTF8', '<root><xmlArgument>xmlValue</xmlArgument></root>', array('xmlArgument' => 'xmlValue')),

			// the following media types are wrong (not registered at IANA), but still used by some out there:

			array('application/x-javascript', '{"jsonArgument":"jsonValue"}', array('jsonArgument' => 'jsonValue')),
			array('text/javascript', '{"jsonArgument":"jsonValue"}', array('jsonArgument' => 'jsonValue')),
			array('text/x-javascript', '{"jsonArgument":"jsonValue"}', array('jsonArgument' => 'jsonValue')),
			array('text/x-json', '{"jsonArgument":"jsonValue"}', array('jsonArgument' => 'jsonValue')),
		);
	}

	/**
	 * @test
	 * @dataProvider contentTypesBodiesAndExpectedUnifiedArguments
	 */
	public function argumentsInBodyOfPutAndPostRequestsAreDecodedAccordingToContentType($contentType, $requestBody, array $expectedArguments) {
		$request = Request::create(new Uri('http://dev.blog.rob/?foo=bar'), 'PUT');
		$request->setHeader('Content-Type', $contentType);
		$request->setContent($requestBody);

		foreach ($expectedArguments as $name => $value) {
			$this->assertSame($value, $request->getArgument($name));
		}
		$this->assertSame('bar', $request->getArgument('foo'));
	}

	/**
	 * @test
	 */
	public function isSecureReturnsTrueEvenIfTheSchemeIsHttpButTheRequestWasForwardedAndOriginallyWasHttps() {
		$server = array (
			'HTTP_HOST' => 'dev.blog.rob',
			'HTTP_X_FORWARDED_PROTO' => 'https',
			'SERVER_NAME' => 'dev.blog.rob',
			'SERVER_ADDR' => '127.0.0.1',
			'REMOTE_ADDR' => '127.0.0.1',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'REQUEST_METHOD' => 'GET',
			'QUERY_STRING' => 'foo=bar',
			'REQUEST_URI' => '/posts/2011/11/28/laboriosam-soluta-est-minus-molestiae?getKey1=getValue1&getKey2=getValue2',
			'SCRIPT_NAME' => '/index.php',
			'PHP_SELF' => '/index.php',
		);

		$request = Request::create(new Uri('http://acme.com'), 'GET', array(), array(), $server);
		$this->assertEquals('http', $request->getUri()->getScheme());
		$this->assertTrue($request->isSecure());
	}

	/**
	 * RFC 2616 / 9.1.1
	 *
	 * @test
	 */
	public function isMethodSafeReturnsTrueIfTheRequestMethodIsGetOrHead() {
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
	public function untangleFilesArrayTransformsTheFilesSuperglobalIntoAMangeableForm() {
		$convolutedFiles = array (
			'a0' => array (
				'name' => array (
					'a1' => 'a.txt',
				),
				'type' => array (
					'a1' => 'text/plain',
				),
				'tmp_name' => array (
					'a1' => '/private/var/tmp/phpbqXsYt',
				),
				'error' => array (
					'a1' => 0,
				),
				'size' => array (
					'a1' => 100,
				),
			),
			'b0' => array (
				'name' => array (
					'b1' => 'b.txt',
				),
				'type' => array (
					'b1' => 'text/plain',
				),
				'tmp_name' => array (
					'b1' => '/private/var/tmp/phpvZ6oUD',
				),
				'error' => array (
					'b1' => 0,
				),
				'size' => array (
					'b1' => 200,
				),
			),
			'c' => array (
				'name' => 'c.txt',
				'type' => 'text/plain',
				'tmp_name' => '/private/var/tmp/phpS9KMNw',
				'error' => 0,
				'size' => 300,
			),
			'd0' => array (
				'name' => array (
					'd1' => array (
						'd2' => array (
							'd3' => 'd.txt',
						),
					),
				),
				'type' => array (
					'd1' => array(
						'd2' => array (
							'd3' => 'text/plain',
						),
					),
				),
				'tmp_name' => array (
					'd1' => array (
						'd2' => array(
							'd3' => '/private/var/tmp/phprR3fax',
						),
					),
				),
				'error' => array (
					'd1' => array (
						'd2' => array(
							'd3' => 0,
						),
					),
				),
				'size' => array (
					'd1' => array (
						'd2' => array(
							'd3' => 400,
						),
					),
				),
			),
			'e0' => array (
				'name' => array (
					'e1' => array (
						'e2' => array (
							0 => 'e_one.txt',
							1 => 'e_two.txt',
						),
					),
				),
				'type' => array (
					'e1' => array (
						'e2' => array (
							0 => 'text/plain',
							1 => 'text/plain',
						),
					),
				),
				'tmp_name' => array (
					'e1' => array (
						'e2' => array (
							0 => '/private/var/tmp/php01fitB',
							1 => '/private/var/tmp/phpUUB2cv',
						),
					),
				),
				'error' => array (
					'e1' => array (
						'e2' => array (
							0 => 0,
							1 => 0,
						),
					),
				),
				'size' => array (
					'e1' => array (
						'e2' => array (
							0 => 510,
							1 => 520,
						)
					)
				)
			)
		);

		$untangledFiles = array (
			'a0' => array (
				'a1' => array(
					'name' => 'a.txt',
					'type' => 'text/plain',
					'tmp_name' => '/private/var/tmp/phpbqXsYt',
					'error' => 0,
					'size' => 100,
				),
			),
			'b0' => array (
				'b1' => array(
					'name' => 'b.txt',
					'type' => 'text/plain',
					'tmp_name' => '/private/var/tmp/phpvZ6oUD',
					'error' => 0,
					'size' => 200,
				)
			),
			'c' => array (
				'name' => 'c.txt',
				'type' => 'text/plain',
				'tmp_name' => '/private/var/tmp/phpS9KMNw',
				'error' => 0,
				'size' => 300,
			),
			'd0' => array (
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
			'e0' => array (
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

		$request = $this->getAccessibleMock('TYPO3\Flow\Http\Request', array('dummy'), array(), '', FALSE);
		$result = $request->_call('untangleFilesArray', $convolutedFiles);

		$this->assertSame($untangledFiles, $result);
	}

	/**
	 * @test
	 */
	public function untangleFilesArrayDoesNotChangeArgumentsIfNoFileWasUploaded() {
		$convolutedFiles = array (
			'a0' => array (
				'name' => array (
					'a1' => '',
				),
				'type' => array (
					'a1' => '',
				),
				'tmp_name' => array (
					'a1' => '',
				),
				'error' => array (
					'a1' => \UPLOAD_ERR_NO_FILE,
				),
				'size' => array (
					'a1' => 0,
				),
			),
			'b0' => array (
				'name' => array (
					'b1' => 'b.txt',
				),
				'type' => array (
					'b1' => 'text/plain',
				),
				'tmp_name' => array (
					'b1' => '/private/var/tmp/phpvZ6oUD',
				),
				'error' => array (
					'b1' => 0,
				),
				'size' => array (
					'b1' => 200,
				),
			),
		);

		$untangledFiles = array (
			'b0' => array (
				'b1' => array(
					'name' => 'b.txt',
					'type' => 'text/plain',
					'tmp_name' => '/private/var/tmp/phpvZ6oUD',
					'error' => 0,
					'size' => 200,
				)
			),
		);

		$request = $this->getAccessibleMock('TYPO3\Flow\Http\Request', array('dummy'), array(), '', FALSE);
		$result = $request->_call('untangleFilesArray', $convolutedFiles);

		$this->assertSame($untangledFiles, $result);
	}

	/**
	 * Data provider with valid quality value strings and the expected parse output
	 *
	 * @return array
	 */
	public function qualityValues() {
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
	public function parseContentNegotiationQualityValuesReturnsNormalizedAndOrderListOfPreferredValues($rawValues, $expectedValues) {
		$request = $this->getAccessibleMock('TYPO3\Flow\Http\Request', array('dummy'), array(), '', FALSE);
		$actualValues = $request->_call('parseContentNegotiationQualityValues', $rawValues);
		$this->assertSame($expectedValues, $actualValues);
	}

	/**
	 * Data provider with media types and their parsed counterparts
	 */
	public function mediaTypesAndParsedPieces() {
		return array(
			array('text/html', array('type' => 'text', 'subtype' => 'html', 'parameters' => array())),
			array('application/json; charset=UTF-8', array('type' => 'application', 'subtype' => 'json', 'parameters' => array('charset' => 'UTF-8'))),
			array('application/vnd.org.flow.coffee+json; kind =Arabica;weight= 15g;  sugar =none', array('type' => 'application', 'subtype' => 'vnd.org.flow.coffee+json', 'parameters' => array('kind' => 'Arabica', 'weight' => '15g', 'sugar' => 'none'))),
		);
	}

	/**
	 * @test
	 * @dataProvider mediaTypesAndParsedPieces
	 */
	public function parseMediaTypeReturnsAssociativeArrayWithIndividualPartsOfTheMediaType($mediaType, $expectedPieces) {
		$request = $this->getAccessibleMock('TYPO3\Flow\Http\Request', array('dummy'), array(), '', FALSE);
		$actualPieces = $request->_call('parseMediaType', $mediaType);
		$this->assertSame($expectedPieces, $actualPieces);
	}

	/**
	 * Data provider
	 */
	public function mediaRangesAndMatchingOrNonMatchingMediaTypes() {
		return array(
			array('invalid', 'text/html', FALSE),
			array('text/html', 'text/html', TRUE),
			array('text/html', 'text/plain', FALSE),
			array('*/*', 'text/html', TRUE),
			array('*/*', 'application/json', TRUE),
			array('text/*', 'text/html', TRUE),
			array('text/*', 'text/plain', TRUE),
			array('text/*', 'application/xml', FALSE),
			array('application/*', 'application/xml', TRUE),
			array('text/x-dvi', 'text/x-dvi', TRUE),
			array('-Foo.+/~Bar199', '-Foo.+/~Bar199', TRUE),
		);
	}

	/**
	 * @test
	 * @dataProvider mediaRangesAndMatchingOrNonMatchingMediaTypes
	 */
	public function mediaRangeMatchesChecksIfTheGivenMediaRangeMatchesTheGivenMediaType($mediaRange, $mediaType, $expectedResult) {
		$request = $this->getAccessibleMock('TYPO3\Flow\Http\Request', array('dummy'), array(), '', FALSE);
		$this->assertSame($expectedResult, $request->_call('mediaRangeMatches', $mediaRange, $mediaType));
	}

	/**
	 * Data provider with media types and their trimmed versions
	 */
	public function mediaTypesWithAndWithoutParameters() {
		return array(
			array('text/html', 'text/html'),
			array('application/json; charset=UTF-8', 'application/json'),
			array('application/vnd.org.flow.coffee+json; kind =Arabica;weight= 15g;  sugar =none', 'application/vnd.org.flow.coffee+json'),
			array('invalid', NULL),
			array('invalid/', NULL),
		);
	}

	/**
	 * @test
	 * @dataProvider mediaTypesWithAndWithoutParameters
	 */
	public function trimMediaTypeReturnsJustTheTypeAndSubTypeWithoutParameters($mediaType, $trimmedMediaType) {
		$request = $this->getAccessibleMock('TYPO3\Flow\Http\Request', array('dummy'), array(), '', FALSE);
		$this->assertSame($trimmedMediaType, $request->_call('trimMediaType', $mediaType));
	}

}

?>
