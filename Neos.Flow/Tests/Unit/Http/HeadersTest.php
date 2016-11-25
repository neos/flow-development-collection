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

use Neos\Flow\Http\Headers;
use Neos\Flow\Http\Cookie;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test case for the Http Headers class
 */
class HeadersTest extends UnitTestCase
{
    /**
     * @test
     */
    public function headerFieldsCanBeSpecifiedToTheConstructor()
    {
        $headers = new Headers(['User-Agent' => 'Espresso Machine', 'Server' => ['Foo', 'Bar']]);
        $this->assertSame('Espresso Machine', $headers->get('User-Agent'));
        $this->assertSame(['Foo', 'Bar'], $headers->get('Server'));
        $this->assertTrue($headers->has('Server'));
    }

    /**
     * @test
     */
    public function createFromServerCreatesFieldsFromSpecifiedServerSuperglobal()
    {
        $server = [
            'HTTP_FOO' => 'Robusta',
            'HTTP_BAR_BAZ' => 'Arabica',
        ];

        $headers = Headers::createFromServer($server);

        $this->assertEquals('Robusta', $headers->get('Foo'));
        $this->assertEquals('Arabica', $headers->get('Bar-Baz'));
    }

    /**
     * @test
     */
    public function createFromServerSimulatesAuthorizationHeaderIfPHPAuthVariablesArePresent()
    {
        $server = [
            'PHP_AUTH_USER' => 'robert',
            'PHP_AUTH_PW' => 'mysecretpassword, containing a : colon ;-)',
        ];

        $headers = Headers::createFromServer($server);

        $expectedValue = 'Basic ' . base64_encode('robert:mysecretpassword, containing a : colon ;-)');
        $this->assertEquals($expectedValue, $headers->get('Authorization'));
        $this->assertFalse($headers->has('User'));
    }

    /**
     * @test
     */
    public function headerFieldsCanBeReplaced()
    {
        $headers = new Headers();
        $headers->set('Host', 'myhost.com');
        $headers->set('Host', 'yourhost.com');
        $this->assertSame('yourhost.com', $headers->get('Host'));
    }

    /**
     * @test
     */
    public function headerFieldsCanExistMultipleTimes()
    {
        $headers = new Headers();
        $headers->set('X-Powered-By', 'Flow');
        $headers->set('X-Powered-By', 'Neos', false);
        $this->assertSame(['Flow', 'Neos'], $headers->get('X-Powered-By'));
    }

    /**
     * @test
     */
    public function getReturnsNullForNonExistingHeader()
    {
        $headers = new Headers();
        $headers->set('X-Powered-By', 'Flow');
        $this->assertFalse($headers->has('X-Empowered-By'));
        $this->assertNull($headers->get('X-Empowered-By'));
    }

    /**
     * @test
     */
    public function getAllReturnsAllHeaderFields()
    {
        $specifiedFields = ['X-Coffee' => 'Arabica', 'Host' =>'myhost.com'];
        $headers = new Headers($specifiedFields);

        $expectedFields = ['X-Coffee' => ['Arabica'], 'Host' => ['myhost.com']];
        $this->assertEquals($expectedFields, $headers->getAll());
    }

    /**
     * @test
     */
    public function getAllAddsCacheControlHeaderIfCacheDirectivesHaveBeenSet()
    {
        $expectedFields = ['Last-Modified' => ['Tue, 24 May 2012 12:00:00 +0000']];
        $headers = new Headers($expectedFields);

        $this->assertEquals($expectedFields, $headers->getAll());

        $expectedFields['Cache-Control'] = ['public, max-age=60'];
        $headers->setCacheControlDirective('public');
        $headers->setCacheControlDirective('max-age', 60);
        $this->assertEquals($expectedFields, $headers->getAll());
    }

    /**
     * (RFC 2616 3.3.1)
     *
     * This checks if set() and get() convert DateTime to an RFC 2822 compliant date /
     * time string and vice versa. Note that the date / time passed to set() is
     * normalized to GMT internally, so that get() will return the same point in time,
     * but not in the same timezone, if it was not GMT previously.
     *
     * @test
     */
    public function setGetAndGetAllConvertDatesFromDateObjectsToStringAndViceVersa()
    {
        $now = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 22 May 2012 12:00:00 +0200');
        $nowInGmt = clone $now;
        $nowInGmt->setTimezone(new \DateTimeZone('GMT'));
        $headers = new Headers();

        $headers->set('Last-Modified', $now);
        $this->assertEquals($nowInGmt->format(DATE_RFC2822), $headers->get('Last-Modified')->format(DATE_RFC2822));

        $headers->set('X-Test-Run-At', $now);
        $this->assertEquals($nowInGmt->format(DATE_RFC2822), $headers->get('X-Test-Run-At')->format(DATE_RFC2822));
    }

    /**
     * @test
     */
    public function removeRemovesTheSpecifiedHeader()
    {
        $specifiedFields = array('X-Coffee' => 'Arabica', 'Host' =>'myhost.com');
        $headers = new Headers($specifiedFields);

        $headers->remove('X-Coffee');
        $headers->remove('X-This-Does-Not-Exist-Anyway');

        $this->assertEquals(['Host' => ['myhost.com']], $headers->getAll());
    }

    /**
     * @test
     */
    public function singleCookieCanBeSetAndRetrieved()
    {
        $headers = new Headers();
        $cookie = new Cookie('Dark-Chocolate-Chip');
        $headers->setCookie($cookie);
        $this->assertSame($cookie, $headers->getCookie('Dark-Chocolate-Chip'));
    }

    /**
     * @test
     */
    public function cookiesCanBeRemoved()
    {
        $headers = new Headers();
        $headers->setCookie(new Cookie('Dark-Chocolate-Chip'));

        $this->assertTrue($headers->hasCookie('Dark-Chocolate-Chip'));
        $headers->removeCookie('Dark-Chocolate-Chip');
        $this->assertFalse($headers->hasCookie('Dark-Chocolate-Chip'));
    }

    /**
     * @test
     */
    public function getCookiesReturnsAllCookies()
    {
        $cookies = [
            'Dark-Chocolate-Chip' => new Cookie('Dark-Chocolate-Chip'),
            'Pecan-Maple-Choc' => new Cookie('Pecan-Maple-Choc'),
            'Coffee-Fudge-Mess' => new Cookie('Coffee-Fudge-Mess'),
        ];

        $headers = new Headers();
        $headers->setCookie($cookies['Dark-Chocolate-Chip']);
        $headers->setCookie($cookies['Pecan-Maple-Choc']);
        $headers->setCookie($cookies['Coffee-Fudge-Mess']);

        $headers->eatCookie('Coffee-Fudge-Mess');
        unset($cookies['Coffee-Fudge-Mess']);

        $this->assertEquals($cookies, $headers->getCookies());
    }

    /**
     * @test
     */
    public function cookiesCanBeSetThroughTheCookieHeader()
    {
        $headers = new Headers();
        $headers->set('Cookie', ['cookie1=the+value+number+1; cookie2=the+value+number+2;  Cookie-Thing3="' . urlencode('Fön + x = \'test\'') . '"']);

        $this->assertTrue($headers->hasCookie('cookie1'));
        $this->assertEquals('the value number 1', $headers->getCookie('cookie1')->getValue());

        $this->assertTrue($headers->hasCookie('cookie2'));
        $this->assertEquals('the value number 2', $headers->getCookie('cookie2')->getValue());

        $this->assertEquals('Fön + x = \'test\'', $headers->getCookie('Cookie-Thing3')->getValue());
    }

    /**
     * See FLOW-12
     *
     * @test
     */
    public function cookiesWithEmptyNameAreIgnored()
    {
        $headers = new Headers();
        $headers->set('Cookie', ['cookie1=the+value+number+1; =foo']);

        $this->assertTrue($headers->hasCookie('cookie1'));
        $this->assertEquals('the value number 1', $headers->getCookie('cookie1')->getValue());
    }

    /**
     * Data provider with valid cache control headers
     */
    public function cacheControlHeaders()
    {
        return [
            ['public', 'public'],
            ['private', 'private'],
            ['no-cache', 'no-cache'],
            ['private="X-Flow-Powered"', 'private="X-Flow-Powered"'],
            ['no-cache= "X-Flow-Powered" ', 'no-cache="X-Flow-Powered"'],
            ['max-age = 3600, must-revalidate', 'max-age=3600, must-revalidate'],
            ['private, max-age=0, must-revalidate', 'private, max-age=0, must-revalidate'],
            ['max-age=60, private,  proxy-revalidate', 'private, max-age=60, proxy-revalidate']
        ];
    }

    /**
     * @dataProvider cacheControlHeaders
     * @test
     */
    public function cacheControlHeaderPassedToSetIsParsedCorrectly($rawFieldValue, $renderedFieldValue)
    {
        $headers = new Headers();

        $this->assertFalse($headers->has('Cache-Control'));
        $headers->set('Cache-Control', $rawFieldValue);
        $this->assertTrue($headers->has('Cache-Control'));
        $this->assertEquals($renderedFieldValue, $headers->get('Cache-Control'));
    }

    /**
     * @test
     */
    public function setOverridesAnyPreviouslyDefinedCacheControlDirectives()
    {
        $headers = new Headers();

        $headers->setCacheControlDirective('public');
        $headers->set('Cache-Control', 'max-age=600, must-revalidate');
        $this->assertEquals('max-age=600, must-revalidate', $headers->get('Cache-Control'));
    }

    /**
     * (RFC 2616 / 14.9.1)
     *
     * @test
     */
    public function setCacheControlDirectiveSetsVisibilityCorrectly()
    {
        $headers = new Headers();

        $headers->setCacheControlDirective('public');
        $this->assertEquals('public', $headers->get('Cache-Control'));

        $headers->setCacheControlDirective('private');
        $this->assertEquals('private', $headers->get('Cache-Control'));

        $headers->setCacheControlDirective('private', 'X-Flow-Powered');
        $this->assertEquals('private="X-Flow-Powered"', $headers->get('Cache-Control'));

        $headers->setCacheControlDirective('no-cache', 'X-Flow-Powered');
        $this->assertEquals('no-cache="X-Flow-Powered"', $headers->get('Cache-Control'));

        $headers->setCacheControlDirective('no-cache');
        $this->assertEquals('no-cache', $headers->get('Cache-Control'));
    }

    /**
     * @test
     *
     * Note: This is a fix for https://jira.neos.io/browse/FLOW-324 (see https://code.google.com/p/chromium/issues/detail?id=501095)
     */
    public function setExceptsHttpsHeaders()
    {
        $headers = new Headers();
        $headers->set('HTTPS', 1);

        // dummy assertion to suppress PHPUnit warning
        $this->assertTrue(true);
    }

    /**
     * (RFC 2616 / 14.9.1)
     *
     * @test
     */
    public function removeCacheControlDirectiveRemovesVisibilityCorrectly()
    {
        $headers = new Headers();
        $headers->setCacheControlDirective('public');

        $headers->setCacheControlDirective('private');
        $headers->removeCacheControlDirective('private');
        $this->assertEquals(null, $headers->get('Cache-Control'));

        $headers->setCacheControlDirective('public');
        $headers->removeCacheControlDirective('public');
        $this->assertEquals(null, $headers->get('Cache-Control'));

        $headers->setCacheControlDirective('no-cache');
        $headers->removeCacheControlDirective('no-cache');
        $this->assertEquals(null, $headers->get('Cache-Control'));
    }

    /**
     * (RFC 2616 / 14.9.2)
     *
     * @test
     */
    public function noStoreCacheDirectiveCanBeSetAndRemoved()
    {
        $headers = new Headers();

        $headers->setCacheControlDirective('no-store');
        $this->assertEquals('no-store', $headers->get('Cache-Control'));

        $headers->setCacheControlDirective('public');
        $this->assertEquals('public, no-store', $headers->get('Cache-Control'));

        $headers->removeCacheControlDirective('no-store');
        $this->assertEquals('public', $headers->get('Cache-Control'));
    }

    /**
     * (RFC 2616 / 14.9.3)
     *
     * @test
     */
    public function maxAgeAndSMaxAgeIsRenderedCorrectly()
    {
        $headers = new Headers();

        $headers->setCacheControlDirective('max-age', 120);
        $this->assertEquals('max-age=120', $headers->get('Cache-Control'));

        $headers->setCacheControlDirective('s-maxage', 60);
        $this->assertEquals('max-age=120, s-maxage=60', $headers->get('Cache-Control'));

        $headers->removeCacheControlDirective('max-age');
        $this->assertEquals('s-maxage=60', $headers->get('Cache-Control'));

        $headers->removeCacheControlDirective('s-maxage');
        $this->assertEquals(null, $headers->get('Cache-Control'));
    }

    /**
     * (RFC 2616 / 14.9.5)
     *
     * @test
     */
    public function noTransformCacheDirectiveIsRenderedCorrectly()
    {
        $headers = new Headers();

        $headers->setCacheControlDirective('no-transform');
        $headers->setCacheControlDirective('public');

        $this->assertEquals('public, no-transform', $headers->get('Cache-Control'));

        $headers->removeCacheControlDirective('no-transform');

        $this->assertEquals('public', $headers->get('Cache-Control'));
    }

    /**
     * (RFC 2616 / 14.9.4)
     *
     * @test
     */
    public function mustRevalidateAndProxyRevalidateAreRenderedCorrectly()
    {
        $headers = new Headers();

        $headers->setCacheControlDirective('must-revalidate');
        $this->assertEquals('must-revalidate', $headers->get('Cache-Control'));

        $headers->removeCacheControlDirective('must-revalidate');
        $headers->setCacheControlDirective('proxy-revalidate');
        $this->assertEquals('proxy-revalidate', $headers->get('Cache-Control'));
    }

    /**
     * Data provider for the test below
     */
    public function cacheDirectivesAndExampleValues()
    {
        return [
            ['public', true],
            ['private', true],
            ['private', 'X-Flow'],
            ['no-cache', true],
            ['no-cache', 'X-Flow'],
            ['max-age', 60],
            ['s-maxage', 120],
            ['must-revalidate', true],
            ['proxy-revalidate', true],
            ['no-store', true],
            ['no-transform', true],
            ['must-revalidate', true],
            ['proxy-revalidate', true]
        ];
    }

    /**
     * @dataProvider cacheDirectivesAndExampleValues
     * @test
     */
    public function getCacheControlDirectiveReturnsTheSpecifiedDirectiveValueIfPresent($name, $value)
    {
        $headers = new Headers();
        $this->assertNull($headers->getCacheControlDirective($name));
        if ($value === true) {
            $headers->setCacheControlDirective($name);
        } else {
            $headers->setCacheControlDirective($name, $value);
        }
        $this->assertEquals($value, $headers->getCacheControlDirective($name));
    }
}
