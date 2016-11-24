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

use Neos\Flow\Http\Uri;
use Neos\Flow\Http\Cookie;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test case for the Http Cookie class
 */
class CookieTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function invalidCookieNames()
    {
        return [
            ['foo bar'],
            ['foo(bar)'],
            ['<foo>'],
            ['@foo'],
            ['foo[bar]'],
            ['foo:bar'],
            ['foo;'],
            ['foo?'],
            ['foo{bar}'],
            ['"foo"'],
            ['foo/bar'],
            ['föö'],
            ['„foo“'],
        ];
    }

    /**
     * @return array
     */
    public function validCookieNames()
    {
        return [
            ['foo'],
            ['foo_bar'],
            ['foo\'bar'],
            ['foo*bar'],
            ['MyNameIsFooAndYoursIsBar1234567890'],
            ['foo|bar'],
            ['$foo%bar~baz'],
        ];
    }

    /**
     * @param string  $cookieName
     * @test
     * @dataProvider invalidCookieNames
     * @expectedException \InvalidArgumentException
     */
    public function constructorThrowsExceptionOnInvalidCookieNames($cookieName)
    {
        new Cookie($cookieName);
    }

    /**
     * @param string  $cookieName
     * @test
     * @dataProvider validCookieNames
     */
    public function constructorAcceptsValidCookieNames($cookieName)
    {
        $cookie = new Cookie($cookieName);
        $this->assertEquals($cookieName, $cookie->getName());
    }

    /**
     * @test
     */
    public function getValueReturnsTheSetValue()
    {
        $cookie = new Cookie('foo', 'bar');
        $this->assertEquals('bar', $cookie->getValue());

        $cookie = new Cookie('foo', 'bar');
        $cookie->setValue('baz');
        $this->assertEquals('baz', $cookie->getValue());

        $cookie = new Cookie('foo', true);
        $this->assertSame(true, $cookie->getValue());

        $uri = new Uri('http://localhost');
        $cookie = new Cookie('foo', $uri);
        $this->assertSame($uri, $cookie->getValue());
    }

    /**
     * @return array
     */
    public function invalidExpiresParameters()
    {
        return [
            ['foo'],
            ['-1'],
            [new \stdClass()],
            [false]
        ];
    }

    /**
     * @param mixed $parameter
     * @test
     * @dataProvider invalidExpiresParameters
     * @expectedException \InvalidArgumentException
     */
    public function constructorThrowsExceptionOnInvalidExpiresParameter($parameter)
    {
        new Cookie('foo', 'bar', $parameter);
    }

    /**
     * @test
     */
    public function getExpiresAlwaysReturnsAUnixTimestamp()
    {
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
    public function constructorThrowsExceptionOnInvalidMaximumAgeParameter()
    {
        new Cookie('foo', 'bar', 0, 'urks');
    }

    /**
     * @test
     */
    public function getMaximumAgeReturnsTheMaximumAge()
    {
        $cookie = new Cookie('foo', 'bar');
        $this->assertSame(null, $cookie->getMaximumAge());

        $cookie = new Cookie('foo', 'bar', 0, 120);
        $this->assertSame(120, $cookie->getMaximumAge());
    }

    /**
     * @return array
     */
    public function invalidDomains()
    {
        return [
            [' me.com'],
            ['you .com'],
            ['-neos.io'],
            ['neos.io.'],
            ['.neos.io'],
            [false]
        ];
    }

    /**
     * @param mixed $domain
     * @test
     * @dataProvider invalidDomains
     * @expectedException \InvalidArgumentException
     */
    public function constructorThrowsExceptionOnInvalidDomain($domain)
    {
        new Cookie('foo', 'bar', 0, null, $domain);
    }

    /**
     * @test
     */
    public function getDomainReturnsDomain()
    {
        $cookie = new Cookie('foo', 'bar', 0, null, 'flow.neos.io');
        $this->assertSame('flow.neos.io', $cookie->getDomain());
    }

    /**
     * @return array
     */
    public function invalidPaths()
    {
        return [
            ['/foo;'],
            ['/föö/bäär'],
            ["\tfoo"],
            [false]
        ];
    }

    /**
     * @param mixed $path
     * @test
     * @dataProvider invalidPaths
     * @expectedException \InvalidArgumentException
     */
    public function constructorThrowsExceptionOnInvalidPath($path)
    {
        new Cookie('foo', 'bar', 0, null, null, $path);
    }

    /**
     * @test
     */
    public function getPathReturnsPath()
    {
        $cookie = new Cookie('foo', 'bar');
        $this->assertSame('/', $cookie->getPath());

        $cookie = new Cookie('foo', 'bar', 0, null, 'flow.neos.io', '/about/us');
        $this->assertSame('/about/us', $cookie->getPath());
    }

    /**
     * @test
     */
    public function isSecureReturnsSecureFlag()
    {
        $cookie = new Cookie('foo', 'bar');
        $this->assertFalse($cookie->isSecure());

        $cookie = new Cookie('foo', 'bar', 0, null, 'neos.io', '/', true);
        $this->assertTrue($cookie->isSecure());
    }

    /**
     * @test
     */
    public function isHttpOnlyReturnsHttpOnlyFlag()
    {
        $cookie = new Cookie('foo', 'bar');
        $this->assertTrue($cookie->isHttpOnly());

        $cookie = new Cookie('foo', 'bar', 0, null, 'neos.io', '/', false, false);
        $this->assertFalse($cookie->isHttpOnly());
    }

    /**
     * @test
     */
    public function isExpiredTellsIfTheCookieIsExpired()
    {
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
    public function cookiesAndTheirStringRepresentations()
    {
        $expiredCookie = new Cookie('foo', 'bar');
        $expiredCookie->expire();

        return [
            [new Cookie('foo', 'bar'), 'foo=bar; Path=/; HttpOnly'],
            [new Cookie('MyFoo25', 'bar'), 'MyFoo25=bar; Path=/; HttpOnly'],
            [new Cookie('MyFoo25', true), 'MyFoo25=1; Path=/; HttpOnly'],
            [new Cookie('MyFoo25', false), 'MyFoo25=0; Path=/; HttpOnly'],
            [new Cookie('foo', 'bar', 0), 'foo=bar; Path=/; HttpOnly'],
            [new Cookie('MyFoo25'), 'MyFoo25=; Path=/; HttpOnly'],
            [new Cookie('foo', 'It\'s raining cats and dogs.'), 'foo=It%27s+raining+cats+and+dogs.; Path=/; HttpOnly'],
            [new Cookie('foo', 'Some characters, like "double quotes" must be escaped.'), 'foo=Some+characters%2C+like+%22double+quotes%22+must+be+escaped.; Path=/; HttpOnly'],
            [new Cookie('foo', 'bar', 1345108546), 'foo=bar; Expires=Thu, 16-Aug-2012 09:15:46 GMT; Path=/; HttpOnly'],
            [new Cookie('foo', 'bar', \DateTime::createFromFormat('U', 1345108546)), 'foo=bar; Expires=Thu, 16-Aug-2012 09:15:46 GMT; Path=/; HttpOnly'],
            [new Cookie('foo', 'bar', 0, null, 'flow.neos.io'), 'foo=bar; Domain=flow.neos.io; Path=/; HttpOnly'],
            [new Cookie('foo', 'bar', 0, null, 'flow.neos.io', '/about'), 'foo=bar; Domain=flow.neos.io; Path=/about; HttpOnly'],
            [new Cookie('foo', 'bar', 0, null, 'neos.io', '/', true), 'foo=bar; Domain=neos.io; Path=/; Secure; HttpOnly'],
            [new Cookie('foo', 'bar', 0, null, 'neos.io', '/', true, false), 'foo=bar; Domain=neos.io; Path=/; Secure'],
            [new Cookie('foo', 'bar', 0, 3600), 'foo=bar; Max-Age=3600; Path=/; HttpOnly'],
            [$expiredCookie, 'foo=bar; Expires=Thu, 27-May-1976 12:00:00 GMT; Path=/; HttpOnly']
        ];
    }

    /**
     * Checks if the Cookie cast to a string equals the expected string which can
     * be used as a value for the Set-Cookie header.
     *
     * @param Cookie $cookie
     * @param string $expectedString
     * @return void
     * @test
     * @dataProvider cookiesAndTheirStringRepresentations()
     */
    public function stringRepresentationOfCookieIsValidSetCookieFieldValue(Cookie $cookie, $expectedString)
    {
        $this->assertEquals($expectedString, (string)$cookie);
    }

    /**
     * @test
     */
    public function createCookieFromRawReturnsNullIfBasicNameOrValueAreNotSatisfied()
    {
        $this->assertNull(Cookie::createFromRawSetCookieHeader('Foobar'), 'The cookie without a = char at all is not discarded.');
        $this->assertNull(Cookie::createFromRawSetCookieHeader('=Foobar'), 'The cookie with only a leading = char, hence without a name, is not discarded.');
    }

    /**
     * @test
     */
    public function createCookieFromRawDoesntCareAboutUnkownAttributeValues()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; someproperty=itsvalue');
        $this->assertEquals('ckName', $cookie->getName());
        $this->assertEquals('someValue', $cookie->getValue());
    }

    /**
     * @test
     */
    public function createCookieFromRawParsesExpiryDateCorrectly()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; Expires=Sun, 16-Oct-2022 17:53:36 GMT');
        $this->assertSame(1665942816, $cookie->getExpires());
    }

    /**
     * @test
     */
    public function createCookieFromRawAssumesExpiryDateZeroIfItCannotBeParsed()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; Expires=trythis');
        $this->assertSame(0, $cookie->getExpires());
    }

    /**
     * @test
     */
    public function createCookieFromRawParsesMaxAgeCorrectly()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; Max-Age=-20');
        $this->assertSame(-20, $cookie->getMaximumAge());
    }

    /**
     * @test
     */
    public function createCookieFromRawIgnoresMaxAgeIfInvalid()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; Max-Age=--foo');
        $this->assertNull($cookie->getMaximumAge());
    }

    /**
     * @test
     */
    public function createCookieFromRawIgnoresDomainAttributeIfValueIsEmpty()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; Domain=; more=nothing');
        $this->assertNull($cookie->getDomain());
    }

    /**
     * @test
     */
    public function createCookieFromRawRemovesLeadingDotForDomainIfPresent()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; Domain=.example.org');
        $this->assertEquals('example.org', $cookie->getDomain());
    }

    /**
     * @test
     */
    public function createCookieFromRawLowerCasesDomainName()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; Domain=EXample.org');
        $this->assertEquals('example.org', $cookie->getDomain());
    }

    /**
     * @test
     */
    public function createCookieFromRawAssumesDefaultPathIfNoLeadingSlashIsPresent()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; Path=foo');
        $this->assertEquals('/', $cookie->getPath());
    }

    /**
     * @test
     */
    public function createCookieFromRawUsesPathCorrectly()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; Path=/foo');
        $this->assertEquals('/foo', $cookie->getPath());
    }

    /**
     * @test
     */
    public function createCookieFromRawSetsSecureIfPresent()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; Secure; more=nothing');
        $this->assertTrue($cookie->isSecure());
    }

    /**
     * @test
     */
    public function createCookieFromRawSetsHttpOnlyIfPresent()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; HttpOnly; more=nothing');
        $this->assertTrue($cookie->isHttpOnly());
    }
}
