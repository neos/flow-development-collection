<?php

declare(strict_types=1);

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
use Neos\Flow\Http\Cookie;
use Neos\Flow\Http\Headers;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test case for the Http Headers class
 */
final class HeadersTest extends UnitTestCase
{
    #[Test]
    public function headerFieldsCanBeSpecifiedToTheConstructor()
    {
        $headers = new Headers(['User-Agent' => 'Espresso Machine', 'Server' => ['Foo', 'Bar']]);
        self::assertSame('Espresso Machine', $headers->get('User-Agent'));
        self::assertSame(['Foo', 'Bar'], $headers->get('Server'));
        self::assertTrue($headers->has('Server'));
    }

    #[Test]
    public function headerFieldsCanBeReplaced()
    {
        $headers = new Headers();
        $headers->set('Host', 'myhost.com');
        $headers->set('Host', 'yourhost.com');
        self::assertSame('yourhost.com', $headers->get('Host'));
    }

    #[Test]
    public function headerFieldsCanExistMultipleTimes()
    {
        $headers = new Headers();
        $headers->set('X-Powered-By', 'Flow');
        $headers->set('X-Powered-By', 'Neos', false);
        self::assertSame(['Flow', 'Neos'], $headers->get('X-Powered-By'));
    }

    #[Test]
    public function getReturnsNullForNonExistingHeader()
    {
        $headers = new Headers();
        $headers->set('X-Powered-By', 'Flow');
        self::assertFalse($headers->has('X-Empowered-By'));
        self::assertNull($headers->get('X-Empowered-By'));
    }

    #[Test]
    public function getAllReturnsAllHeaderFields()
    {
        $specifiedFields = ['X-Coffee' => 'Arabica', 'Host' => 'myhost.com'];
        $headers = new Headers($specifiedFields);

        $expectedFields = ['X-Coffee' => ['Arabica'], 'Host' => ['myhost.com']];
        self::assertEquals($expectedFields, $headers->getAll());
    }

    #[Test]
    public function getAllAddsCacheControlHeaderIfCacheDirectivesHaveBeenSet()
    {
        $expectedFields = ['Last-Modified' => ['Tue, 24 May 2012 12:00:00 +0000']];
        $headers = new Headers($expectedFields);

        self::assertEquals($expectedFields, $headers->getAll());

        $expectedFields['Cache-Control'] = ['public, max-age=60'];
        $headers->setCacheControlDirective('public');
        $headers->setCacheControlDirective('max-age', 60);
        self::assertEquals($expectedFields, $headers->getAll());
    }

    /**
     * (RFC 2616 3.3.1)
     *
     * This checks if set() and get() convert DateTime to an RFC 2822 compliant date /
     * time string and vice versa. Note that the date / time passed to set() is
     * normalized to GMT internally, so that get() will return the same point in time,
     * but not in the same timezone, if it was not GMT previously.
     */
    #[Test]
    public function setGetAndGetAllConvertDatesFromDateObjectsToStringAndViceVersa()
    {
        $now = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 22 May 2012 12:00:00 +0200');
        $nowInGmt = clone $now;
        $nowInGmt->setTimezone(new \DateTimeZone('GMT'));
        $headers = new Headers();

        $headers->set('Last-Modified', $now);
        self::assertEquals($nowInGmt->format(DATE_RFC2822), $headers->get('Last-Modified')->format(DATE_RFC2822));

        $headers->set('X-Test-Run-At', $now);
        self::assertEquals($nowInGmt->format(DATE_RFC2822), $headers->get('X-Test-Run-At')->format(DATE_RFC2822));
    }

    #[Test]
    public function removeRemovesTheSpecifiedHeader()
    {
        $specifiedFields = ['X-Coffee' => 'Arabica', 'Host' => 'myhost.com'];
        $headers = new Headers($specifiedFields);

        $headers->remove('X-Coffee');
        $headers->remove('X-This-Does-Not-Exist-Anyway');

        self::assertEquals(['Host' => ['myhost.com']], $headers->getAll());
    }

    #[Test]
    public function singleCookieCanBeSetAndRetrieved()
    {
        $headers = new Headers();
        $cookie = new Cookie('Dark-Chocolate-Chip');
        $headers->setCookie($cookie);
        self::assertEquals($cookie, $headers->getCookie('Dark-Chocolate-Chip'));
    }

    #[Test]
    public function cookiesCanBeRemoved()
    {
        $headers = new Headers();
        $headers->setCookie(new Cookie('Dark-Chocolate-Chip'));

        self::assertTrue($headers->hasCookie('Dark-Chocolate-Chip'));
        $headers->removeCookie('Dark-Chocolate-Chip');
        self::assertFalse($headers->hasCookie('Dark-Chocolate-Chip'));
    }

    #[Test]
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

        self::assertSame(array_keys($cookies), array_keys($headers->getCookies()));
    }

    #[Test]
    public function cookiesCanBeSetThroughTheCookieHeader()
    {
        $headers = new Headers();
        $headers->set('Cookie', ['cookie1=the+value+number+1; cookie2=the+value+number+2;  Cookie-Thing3="' . urlencode('Fön + x = \'test\'') . '"']);

        self::assertTrue($headers->hasCookie('cookie1'));
        self::assertEquals('the value number 1', $headers->getCookie('cookie1')->getValue());

        self::assertTrue($headers->hasCookie('cookie2'));
        self::assertEquals('the value number 2', $headers->getCookie('cookie2')->getValue());

        self::assertEquals('Fön + x = \'test\'', $headers->getCookie('Cookie-Thing3')->getValue());
    }

    /**
     * See FLOW-12
     */
    #[Test]
    public function cookiesWithEmptyNameAreIgnored()
    {
        $headers = new Headers();
        $headers->set('Cookie', ['cookie1=the+value+number+1; =foo']);

        self::assertTrue($headers->hasCookie('cookie1'));
        self::assertEquals('the value number 1', $headers->getCookie('cookie1')->getValue());
    }

    #[Test]
    public function cookiesWithInvalidNameAreIgnored()
    {
        $headers = new Headers();
        $headers->set('Cookie', ['cookie-valid=this+is+valid; cookie[invalid]=this+is+invalid']);

        self::assertTrue($headers->hasCookie('cookie-valid'));
        self::assertFalse($headers->hasCookie('cookie[invalid]'));
    }

    /**
     * Data provider with valid cache control headers
     */
    public static function cacheControlHeaders(): \Iterator
    {
        yield ['public', 'public'];
        yield ['private', 'private'];
        yield ['no-cache', 'no-cache'];
        yield ['private="X-Flow-Powered"', 'private="X-Flow-Powered"'];
        yield ['no-cache= "X-Flow-Powered" ', 'no-cache="X-Flow-Powered"'];
        yield ['max-age = 3600, must-revalidate', 'max-age=3600, must-revalidate'];
        yield ['private, max-age=0, must-revalidate', 'private, max-age=0, must-revalidate'];
        yield ['max-age=60, private,  proxy-revalidate', 'private, max-age=60, proxy-revalidate'];
    }

    #[DataProvider('cacheControlHeaders')]
    #[Test]
    public function cacheControlHeaderPassedToSetIsParsedCorrectly($rawFieldValue, $renderedFieldValue)
    {
        $headers = new Headers();

        self::assertFalse($headers->has('Cache-Control'));
        $headers->set('Cache-Control', $rawFieldValue);
        self::assertTrue($headers->has('Cache-Control'));
        self::assertEquals($renderedFieldValue, $headers->get('Cache-Control'));
    }

    #[Test]
    public function setOverridesAnyPreviouslyDefinedCacheControlDirectives()
    {
        $headers = new Headers();

        $headers->setCacheControlDirective('public');
        $headers->set('Cache-Control', 'max-age=600, must-revalidate');
        self::assertEquals('max-age=600, must-revalidate', $headers->get('Cache-Control'));
    }

    /**
     * (RFC 2616 / 14.9.1)
     */
    #[Test]
    public function setCacheControlDirectiveSetsVisibilityCorrectly()
    {
        $headers = new Headers();

        $headers->setCacheControlDirective('public');
        self::assertEquals('public', $headers->get('Cache-Control'));

        $headers->setCacheControlDirective('private');
        self::assertEquals('private', $headers->get('Cache-Control'));

        $headers->setCacheControlDirective('private', 'X-Flow-Powered');
        self::assertEquals('private="X-Flow-Powered"', $headers->get('Cache-Control'));

        $headers->setCacheControlDirective('no-cache', 'X-Flow-Powered');
        self::assertEquals('no-cache="X-Flow-Powered"', $headers->get('Cache-Control'));

        $headers->setCacheControlDirective('no-cache');
        self::assertEquals('no-cache', $headers->get('Cache-Control'));
    }

    /**
     * Note: This is a fix for https://jira.neos.io/browse/FLOW-324 (see https://code.google.com/p/chromium/issues/detail?id=501095)
     */
    #[Test]
    #[DoesNotPerformAssertions]
    public function setExceptsHttpsHeaders()
    {
        $headers = new Headers();
        $headers->set('HTTPS', 1);
    }

    /**
     * (RFC 2616 / 14.9.1)
     */
    #[Test]
    public function removeCacheControlDirectiveRemovesVisibilityCorrectly()
    {
        $headers = new Headers();
        $headers->setCacheControlDirective('public');

        $headers->setCacheControlDirective('private');
        $headers->removeCacheControlDirective('private');
        self::assertEquals(null, $headers->get('Cache-Control'));

        $headers->setCacheControlDirective('public');
        $headers->removeCacheControlDirective('public');
        self::assertEquals(null, $headers->get('Cache-Control'));

        $headers->setCacheControlDirective('no-cache');
        $headers->removeCacheControlDirective('no-cache');
        self::assertEquals(null, $headers->get('Cache-Control'));
    }

    /**
     * (RFC 2616 / 14.9.2)
     */
    #[Test]
    public function noStoreCacheDirectiveCanBeSetAndRemoved()
    {
        $headers = new Headers();

        $headers->setCacheControlDirective('no-store');
        self::assertEquals('no-store', $headers->get('Cache-Control'));

        $headers->setCacheControlDirective('public');
        self::assertEquals('public, no-store', $headers->get('Cache-Control'));

        $headers->removeCacheControlDirective('no-store');
        self::assertEquals('public', $headers->get('Cache-Control'));
    }

    /**
     * (RFC 2616 / 14.9.3)
     */
    #[Test]
    public function maxAgeAndSMaxAgeIsRenderedCorrectly()
    {
        $headers = new Headers();

        $headers->setCacheControlDirective('max-age', 120);
        self::assertEquals('max-age=120', $headers->get('Cache-Control'));

        $headers->setCacheControlDirective('s-maxage', 60);
        self::assertEquals('max-age=120, s-maxage=60', $headers->get('Cache-Control'));

        $headers->removeCacheControlDirective('max-age');
        self::assertEquals('s-maxage=60', $headers->get('Cache-Control'));

        $headers->removeCacheControlDirective('s-maxage');
        self::assertEquals(null, $headers->get('Cache-Control'));
    }

    /**
     * (RFC 2616 / 14.9.5)
     */
    #[Test]
    public function noTransformCacheDirectiveIsRenderedCorrectly()
    {
        $headers = new Headers();

        $headers->setCacheControlDirective('no-transform');
        $headers->setCacheControlDirective('public');

        self::assertEquals('public, no-transform', $headers->get('Cache-Control'));

        $headers->removeCacheControlDirective('no-transform');

        self::assertEquals('public', $headers->get('Cache-Control'));
    }

    /**
     * (RFC 2616 / 14.9.4)
     */
    #[Test]
    public function mustRevalidateAndProxyRevalidateAreRenderedCorrectly()
    {
        $headers = new Headers();

        $headers->setCacheControlDirective('must-revalidate');
        self::assertEquals('must-revalidate', $headers->get('Cache-Control'));

        $headers->removeCacheControlDirective('must-revalidate');
        $headers->setCacheControlDirective('proxy-revalidate');
        self::assertEquals('proxy-revalidate', $headers->get('Cache-Control'));
    }

    /**
     * Data provider for the test below
     */
    public static function cacheDirectivesAndExampleValues(): \Iterator
    {
        yield ['public', true];
        yield ['private', true];
        yield ['private', 'X-Flow'];
        yield ['no-cache', true];
        yield ['no-cache', 'X-Flow'];
        yield ['max-age', 60];
        yield ['s-maxage', 120];
        yield ['must-revalidate', true];
        yield ['proxy-revalidate', true];
        yield ['no-store', true];
        yield ['no-transform', true];
        yield ['must-revalidate', true];
        yield ['proxy-revalidate', true];
    }

    #[DataProvider('cacheDirectivesAndExampleValues')]
    #[Test]
    public function getCacheControlDirectiveReturnsTheSpecifiedDirectiveValueIfPresent($name, $value)
    {
        $headers = new Headers();
        self::assertNull($headers->getCacheControlDirective($name));
        if ($value === true) {
            $headers->setCacheControlDirective($name);
        } else {
            $headers->setCacheControlDirective($name, $value);
        }
        self::assertEquals($value, $headers->getCacheControlDirective($name));
    }
}
