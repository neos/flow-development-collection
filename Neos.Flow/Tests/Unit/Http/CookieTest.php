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

use GuzzleHttp\Psr7\Uri;
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
     */
    public function constructorThrowsExceptionOnInvalidCookieNames($cookieName)
    {
        $this->expectException(\InvalidArgumentException::class);
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
        self::assertEquals($cookieName, $cookie->getName());
    }

    /**
     * @test
     */
    public function getValueReturnsTheSetValue()
    {
        $cookie = new Cookie('foo', 'bar');
        self::assertEquals('bar', $cookie->getValue());

        $cookie = new Cookie('foo', 'bar');
        $cookie->setValue('baz');
        self::assertEquals('baz', $cookie->getValue());

        $cookie = new Cookie('foo', true);
        self::assertSame(true, $cookie->getValue());

        $uri = new Uri('http://localhost');
        $cookie = new Cookie('foo', $uri);
        self::assertSame($uri, $cookie->getValue());
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
     */
    public function constructorThrowsExceptionOnInvalidExpiresParameter($parameter)
    {
        $this->expectException(\InvalidArgumentException::class);
        new Cookie('foo', 'bar', $parameter);
    }

    /**
     * @test
     */
    public function getExpiresAlwaysReturnsAUnixTimestamp()
    {
        $cookie = new Cookie('foo', 'bar', 1345110803);
        self::assertSame(1345110803, $cookie->getExpires());

        $cookie = new Cookie('foo', 'bar', \DateTime::createFromFormat('U', 1345110803));
        self::assertSame(1345110803, $cookie->getExpires());

        $cookie = new Cookie('foo', 'bar');
        self::assertSame(0, $cookie->getExpires());
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionOnInvalidMaximumAgeParameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Cookie('foo', 'bar', 0, 'urks');
    }

    /**
     * @test
     */
    public function getMaximumAgeReturnsTheMaximumAge()
    {
        $cookie = new Cookie('foo', 'bar');
        self::assertSame(null, $cookie->getMaximumAge());

        $cookie = new Cookie('foo', 'bar', 0, 120);
        self::assertSame(120, $cookie->getMaximumAge());
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
     */
    public function constructorThrowsExceptionOnInvalidDomain($domain)
    {
        $this->expectException(\InvalidArgumentException::class);
        new Cookie('foo', 'bar', 0, null, $domain);
    }

    /**
     * @test
     */
    public function getDomainReturnsDomain()
    {
        $cookie = new Cookie('foo', 'bar', 0, null, 'flow.neos.io');
        self::assertSame('flow.neos.io', $cookie->getDomain());
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
     */
    public function constructorThrowsExceptionOnInvalidPath($path)
    {
        $this->expectException(\InvalidArgumentException::class);
        new Cookie('foo', 'bar', 0, null, null, $path);
    }

    /**
     * @test
     */
    public function getPathReturnsPath()
    {
        $cookie = new Cookie('foo', 'bar');
        self::assertSame('/', $cookie->getPath());

        $cookie = new Cookie('foo', 'bar', 0, null, 'flow.neos.io', '/about/us');
        self::assertSame('/about/us', $cookie->getPath());
    }

    /**
     * @test
     */
    public function isSecureReturnsSecureFlag()
    {
        $cookie = new Cookie('foo', 'bar');
        self::assertFalse($cookie->isSecure());

        $cookie = new Cookie('foo', 'bar', 0, null, 'neos.io', '/', true);
        self::assertTrue($cookie->isSecure());
    }

    /**
     * @test
     */
    public function isHttpOnlyReturnsHttpOnlyFlag()
    {
        $cookie = new Cookie('foo', 'bar');
        self::assertTrue($cookie->isHttpOnly());

        $cookie = new Cookie('foo', 'bar', 0, null, 'neos.io', '/', false, false);
        self::assertFalse($cookie->isHttpOnly());
    }

    /**
     * @test
     */
    public function SameSiteReturnsNone()
    {
        $cookie = new Cookie('foo', 'bar', 0, null, 'neos.io', '/', true, false, Cookie::SAMESITE_NONE);
        $this->assertSame(Cookie::SAMESITE_NONE, $cookie->getSameSite());
    }

    /**
     * @test
     */
    public function SameSiteNoneEnablesSecure()
    {
        $cookie = new Cookie('foo', 'bar', 0, null, 'neos.io', '/', false, false, Cookie::SAMESITE_NONE);
        $this->assertTrue($cookie->isSecure());
    }

    /**
     * @test
     */
    public function SameSiteReturnsLax()
    {
        $cookie = new Cookie('foo', 'bar', 0, null, 'neos.io', '/', false, false, Cookie::SAMESITE_LAX);
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
    }

    /**
     * @test
     */
    public function SameSiteReturnsStrict()
    {
        $cookie = new Cookie('foo', 'bar', 0, null, 'neos.io', '/', false, false, Cookie::SAMESITE_STRICT);
        $this->assertSame(Cookie::SAMESITE_STRICT, $cookie->getSameSite());
    }

    /**
     * @test
     */
    public function SameSiteThrowsExceptionForInvalidValues()
    {
        $this->expectExceptionCode(1584955500);
        new Cookie('foo', 'bar', 0, null, 'neos.io', '/', false, false, 'foo');
    }

    /**
     * @test
     */
    public function isExpiredTellsIfTheCookieIsExpired()
    {
        $cookie = new Cookie('foo', 'bar');
        self::assertFalse($cookie->isExpired());

        $cookie->expire();
        self::assertTrue($cookie->isExpired());

        $cookie = new Cookie('foo', 'bar', 500);
        self::assertTrue($cookie->isExpired());
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
            [new Cookie('foo', 'bar'), 'foo=bar; Path=/; HttpOnly; SameSite=lax'],
            [new Cookie('MyFoo25', 'bar'), 'MyFoo25=bar; Path=/; HttpOnly; SameSite=lax'],
            [new Cookie('MyFoo25', true), 'MyFoo25=1; Path=/; HttpOnly; SameSite=lax'],
            [new Cookie('MyFoo25', false), 'MyFoo25=0; Path=/; HttpOnly; SameSite=lax'],
            [new Cookie('foo', 'bar', 0), 'foo=bar; Path=/; HttpOnly; SameSite=lax'],
            [new Cookie('MyFoo25'), 'MyFoo25=; Path=/; HttpOnly; SameSite=lax'],
            [new Cookie('foo', 'It\'s raining cats and dogs.'), 'foo=It%27s+raining+cats+and+dogs.; Path=/; HttpOnly; SameSite=lax'],
            [new Cookie('foo', 'Some characters, like "double quotes" must be escaped.'), 'foo=Some+characters%2C+like+%22double+quotes%22+must+be+escaped.; Path=/; HttpOnly; SameSite=lax'],
            [new Cookie('foo', 'bar', 1345108546), 'foo=bar; Expires=Thu, 16-Aug-2012 09:15:46 GMT; Path=/; HttpOnly; SameSite=lax'],
            [new Cookie('foo', 'bar', \DateTime::createFromFormat('U', 1345108546)), 'foo=bar; Expires=Thu, 16-Aug-2012 09:15:46 GMT; Path=/; HttpOnly; SameSite=lax'],
            [new Cookie('foo', 'bar', 0, null, 'flow.neos.io'), 'foo=bar; Domain=flow.neos.io; Path=/; HttpOnly; SameSite=lax'],
            [new Cookie('foo', 'bar', 0, null, 'flow.neos.io', '/about'), 'foo=bar; Domain=flow.neos.io; Path=/about; HttpOnly; SameSite=lax'],
            [new Cookie('foo', 'bar', 0, null, 'neos.io', '/', true), 'foo=bar; Domain=neos.io; Path=/; Secure; HttpOnly; SameSite=lax'],
            [new Cookie('foo', 'bar', 0, null, 'neos.io', '/', true, false), 'foo=bar; Domain=neos.io; Path=/; Secure; SameSite=lax'],
            [new Cookie('foo', 'bar', 0, null, 'neos.io', '/', true, true, Cookie::SAMESITE_NONE), 'foo=bar; Domain=neos.io; Path=/; Secure; HttpOnly; SameSite=none'],
            [new Cookie('foo', 'bar', 0, null, 'neos.io', '/', true, true, Cookie::SAMESITE_STRICT), 'foo=bar; Domain=neos.io; Path=/; Secure; HttpOnly; SameSite=strict'],
            [new Cookie('foo', 'bar', 0, null, 'neos.io', '/', true, true, Cookie::SAMESITE_LAX), 'foo=bar; Domain=neos.io; Path=/; Secure; HttpOnly; SameSite=lax'],
            [new Cookie('foo', 'bar', 0, 3600), 'foo=bar; Max-Age=3600; Path=/; HttpOnly; SameSite=lax'],
            [$expiredCookie, 'foo=bar; Expires=Thu, 27-May-1976 12:00:00 GMT; Path=/; HttpOnly; SameSite=lax']
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
        self::assertEquals($expectedString, (string)$cookie);
    }

    /**
     * @test
     */
    public function createCookieFromRawReturnsNullIfBasicNameOrValueAreNotSatisfied()
    {
        self::assertNull(Cookie::createFromRawSetCookieHeader('Foobar'), 'The cookie without a = char at all is not discarded.');
        self::assertNull(Cookie::createFromRawSetCookieHeader('=Foobar'), 'The cookie with only a leading = char, hence without a name, is not discarded.');
    }

    /**
     * @test
     */
    public function createCookieFromRawDoesntCareAboutUnkownAttributeValues()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; someproperty=itsvalue');
        self::assertEquals('ckName', $cookie->getName());
        self::assertEquals('someValue', $cookie->getValue());
    }

    /**
     * @test
     */
    public function createCookieFromRawParsesExpiryDateCorrectly()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; Expires=Sun, 16-Oct-2022 17:53:36 GMT');
        self::assertSame(1665942816, $cookie->getExpires());
    }

    /**
     * @test
     */
    public function createCookieFromRawAssumesExpiryDateZeroIfItCannotBeParsed()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; Expires=trythis');
        self::assertSame(0, $cookie->getExpires());
    }

    /**
     * @test
     */
    public function createCookieFromRawParsesMaxAgeCorrectly()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; Max-Age=-20');
        self::assertSame(-20, $cookie->getMaximumAge());
    }

    /**
     * @test
     */
    public function createCookieFromRawIgnoresMaxAgeIfInvalid()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; Max-Age=--foo');
        self::assertNull($cookie->getMaximumAge());
    }

    /**
     * @test
     */
    public function createCookieFromRawIgnoresDomainAttributeIfValueIsEmpty()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; Domain=; more=nothing');
        self::assertNull($cookie->getDomain());
    }

    /**
     * @test
     */
    public function createCookieFromRawRemovesLeadingDotForDomainIfPresent()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; Domain=.example.org');
        self::assertEquals('example.org', $cookie->getDomain());
    }

    /**
     * @test
     */
    public function createCookieFromRawLowerCasesDomainName()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; Domain=EXample.org');
        self::assertEquals('example.org', $cookie->getDomain());
    }

    /**
     * @test
     */
    public function createCookieFromRawAssumesDefaultPathIfNoLeadingSlashIsPresent()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; Path=foo');
        self::assertEquals('/', $cookie->getPath());
    }

    /**
     * @test
     */
    public function createCookieFromRawUsesPathCorrectly()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; Path=/foo');
        self::assertEquals('/foo', $cookie->getPath());
    }

    /**
     * @test
     */
    public function createCookieFromRawSetsSecureIfPresent()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; Secure; more=nothing');
        self::assertTrue($cookie->isSecure());
    }

    /**
     * @test
     */
    public function createCookieFromRawSetsHttpOnlyIfPresent()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; HttpOnly; more=nothing');
        self::assertTrue($cookie->isHttpOnly());
    }

    /**
     * @test
     */
    public function createCookieFromRawIgnoresSameSiteAttributeIfValueIsEmpty()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; SameSite=; more=nothing');
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
    }

    /**
     * @test
     */
    public function createCookieFromRawLowerCasesSameSite()
    {
        $cookie = Cookie::createFromRawSetCookieHeader('ckName=someValue; SameSite=Lax');
        $this->assertEquals(Cookie::SAMESITE_LAX, $cookie->getSameSite());
    }
}
