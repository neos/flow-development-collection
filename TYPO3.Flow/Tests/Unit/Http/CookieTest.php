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
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Http\Headers;
use TYPO3\Flow\Http\Cookie;

/**
 * Test case for the Http Cookie class
 */
class CookieTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @return array
	 */
	public function invalidCookieNames() {
		return array(
			array('foo bar'),
			array('foo(bar)'),
			array('<foo>'),
			array('@foo'),
			array('foo[bar]'),
			array('foo:bar'),
			array('foo;'),
			array('foo?'),
			array('foo{bar}'),
			array('"foo"'),
			array('foo/bar'),
			array('föö'),
			array('„foo“'),
		);
	}

	/**
	 * @return array
	 */
	public function validCookieNames() {
		return array(
			array('foo'),
			array('foo_bar'),
			array('foo\'bar'),
			array('foo*bar'),
			array('MyNameIsFooAndYoursIsBar1234567890'),
			array('foo|bar'),
			array('$foo%bar~baz'),
		);
	}

	/**
	 * @param string  $cookieName
	 * @test
	 * @dataProvider invalidCookieNames
	 * @expectedException \InvalidArgumentException
	 */
	public function constructorThrowsExceptionOnInvalidCookieNames($cookieName) {
		new Cookie($cookieName);
	}

	/**
	 * @param string  $cookieName
	 * @test
	 * @dataProvider validCookieNames
	 */
	public function constructorAcceptsValidCookieNames($cookieName) {
		$cookie = new Cookie($cookieName);
		$this->assertEquals($cookieName, $cookie->getName());
	}

	/**
	 * @test
	 */
	public function getValueReturnsTheSetValue() {
		$cookie = new Cookie('foo', 'bar');
		$this->assertEquals('bar', $cookie->getValue());

		$cookie = new Cookie('foo', 'bar');
		$cookie->setValue('baz');
		$this->assertEquals('baz', $cookie->getValue());

		$cookie = new Cookie('foo', TRUE);
		$this->assertSame(TRUE, $cookie->getValue());

		$uri = new Uri('http://localhost');
		$cookie = new Cookie('foo', $uri);
		$this->assertSame($uri, $cookie->getValue());
	}

	/**
	 * @return array
	 */
	public function invalidExpiresParameters() {
		return array(
			array('foo'),
			array('-1'),
			array(new \stdClass()),
			array(FALSE)
		);
	}

	/**
	 * @param mixed $parameter
	 * @test
	 * @dataProvider invalidExpiresParameters
	 * @expectedException \InvalidArgumentException
	 */
	public function constructorThrowsExceptionOnInvalidExpiresParameter($parameter) {
		new Cookie('foo', 'bar', $parameter);
	}

	/**
	 * @test
	 */
	public function getExpiresAlwaysReturnsAUnixTimestamp() {
		$cookie = new Cookie('foo', 'bar', 1345110803);
		$this->assertSame(1345110803, $cookie->getExpires());

		$cookie = new Cookie('foo', 'bar', \DateTime::createFromFormat('U', 1345110803));
		$this->assertSame(1345110803, $cookie->getExpires());

		$cookie = new Cookie('foo', 'bar');
		$this->assertSame(0, $cookie->getExpires());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function constructorThrowsExceptionOnInvalidMaximumAgeParameter() {
		new Cookie('foo', 'bar', 0, 'urks');
	}

	/**
	 * @test
	 */
	public function getMaximumAgeReturnsTheMaximumAge() {
		$cookie = new Cookie('foo', 'bar');
		$this->assertSame(NULL, $cookie->getMaximumAge());

		$cookie = new Cookie('foo', 'bar', 0, 120);
		$this->assertSame(120, $cookie->getMaximumAge());
	}

	/**
	 * @return array
	 */
	public function invalidDomains() {
		return array(
			array(' me.com'),
			array('you .com'),
			array('-typo3.org'),
			array('typo3.org.'),
			array('.typo3.org'),
			array(FALSE)
		);
	}

	/**
	 * @param mixed $domain
	 * @test
	 * @dataProvider invalidDomains
	 * @expectedException \InvalidArgumentException
	 */
	public function constructorThrowsExceptionOnInvalidDomain($domain) {
		new Cookie('foo', 'bar', 0, NULL, $domain);
	}

	/**
	 * @test
	 */
	public function getDomainReturnsDomain() {
		$cookie = new Cookie('foo', 'bar', 0, NULL, 'flow.typo3.org');
		$this->assertSame('flow.typo3.org', $cookie->getDomain());
	}

	/**
	 * @return array
	 */
	public function invalidPaths() {
		return array(
			array('/foo;'),
			array('/föö/bäär'),
			array("\tfoo"),
			array(FALSE)
		);
	}

	/**
	 * @param mixed $path
	 * @test
	 * @dataProvider invalidPaths
	 * @expectedException \InvalidArgumentException
	 */
	public function constructorThrowsExceptionOnInvalidPath($path) {
		new Cookie('foo', 'bar', 0, NULL, 'typo3.org', $path);
	}

	/**
	 * @test
	 */
	public function getPathReturnsPath() {
		$cookie = new Cookie('foo', 'bar');
		$this->assertSame('/', $cookie->getPath());

		$cookie = new Cookie('foo', 'bar', 0, NULL, 'flow.typo3.org', '/about/us');
		$this->assertSame('/about/us', $cookie->getPath());
	}

	/**
	 * @test
	 */
	public function isSecureReturnsSecureFlag() {
		$cookie = new Cookie('foo', 'bar');
		$this->assertFalse($cookie->isSecure());

		$cookie = new Cookie('foo', 'bar', 0, NULL, 'typo3.org', '/', TRUE);
		$this->assertTrue($cookie->isSecure());
	}

	/**
	 * @test
	 */
	public function isHttpOnlyReturnsHttpOnlyFlag() {
		$cookie = new Cookie('foo', 'bar');
		$this->assertTrue($cookie->isHttpOnly());

		$cookie = new Cookie('foo', 'bar', 0, NULL, 'typo3.org', '/', FALSE, FALSE);
		$this->assertFalse($cookie->isHttpOnly());
	}

	/**
	 * @test
	 */
	public function isExpiredTellsIfTheCookieIsExpired() {
		$cookie = new Cookie('foo', 'bar');
		$this->assertFalse($cookie->isExpired());

		$cookie->expire();
		$this->assertTrue($cookie->isExpired());

		$cookie = new Cookie('foo', 'bar', 500);
		$this->assertTrue($cookie->isExpired());
	}

	/**
	 * Data provider with cookies and their expected string representation.
	 *
	 * @return array
	 */
	public function cookiesAndTheirStringRepresentations() {
		$expiredCookie = new Cookie('foo', 'bar');
		$expiredCookie->expire();

		return array(
			array(new Cookie('foo', 'bar'), 'foo=bar; Path=/; HttpOnly'),
			array(new Cookie('MyFoo25', 'bar'), 'MyFoo25=bar; Path=/; HttpOnly'),
			array(new Cookie('MyFoo25', TRUE), 'MyFoo25=1; Path=/; HttpOnly'),
			array(new Cookie('MyFoo25', FALSE), 'MyFoo25=0; Path=/; HttpOnly'),
			array(new Cookie('foo', 'bar', 0), 'foo=bar; Path=/; HttpOnly'),
			array(new Cookie('MyFoo25'), 'MyFoo25=; Path=/; HttpOnly'),
			array(new Cookie('foo', 'It\'s raining cats and dogs.'), 'foo=It%27s+raining+cats+and+dogs.; Path=/; HttpOnly'),
			array(new Cookie('foo', 'Some characters, like "double quotes" must be escaped.'), 'foo=Some+characters%2C+like+%22double+quotes%22+must+be+escaped.; Path=/; HttpOnly'),
			array(new Cookie('foo', 'bar', 1345108546), 'foo=bar; Expires=Thu, 16-Aug-2012 09:15:46 GMT; Path=/; HttpOnly'),
			array(new Cookie('foo', 'bar', \DateTime::createFromFormat('U', 1345108546)), 'foo=bar; Expires=Thu, 16-Aug-2012 09:15:46 GMT; Path=/; HttpOnly'),
			array(new Cookie('foo', 'bar', 0, NULL, 'flow.typo3.org'), 'foo=bar; Domain=flow.typo3.org; Path=/; HttpOnly'),
			array(new Cookie('foo', 'bar', 0, NULL, 'flow.typo3.org', '/about'), 'foo=bar; Domain=flow.typo3.org; Path=/about; HttpOnly'),
			array(new Cookie('foo', 'bar', 0, NULL, 'typo3.org', '/', TRUE), 'foo=bar; Domain=typo3.org; Path=/; Secure; HttpOnly'),
			array(new Cookie('foo', 'bar', 0, NULL, 'typo3.org', '/', TRUE, FALSE), 'foo=bar; Domain=typo3.org; Path=/; Secure'),
			array($expiredCookie, 'foo=bar; Expires=Thu, 27-May-1976 12:00:00 GMT; Path=/; HttpOnly')
		);
	}

	/**
	 * Checks if the Cookie cast to a string equals the expected string which can
	 * be used as a value for the Set-Cookie header.
	 *
	 * @param \TYPO3\Flow\Http\Cookie $cookie
	 * @param string $expectedString
	 * @return void
	 * @test
	 * @dataProvider cookiesAndTheirStringRepresentations()
	 */
	public function stringRepresentationOfCookieIsValidSetCookieFieldValue(Cookie $cookie, $expectedString) {
		$this->assertEquals($expectedString, (string)$cookie);
	}

}
?>