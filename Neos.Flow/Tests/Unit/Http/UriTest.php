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
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the URI class
 *
 */
class UriTest extends UnitTestCase
{
    /**
     * Checks if a complete URI with all parts is transformed into an object correctly.
     *
     * @test
     */
    public function constructorParsesAFullBlownUriStringCorrectly()
    {
        $uriString = 'http://username:password@subdomain.domain.com:8080/path1/path2/index.php?argument1=value1&argument2=value2&argument3[subargument1]=subvalue1#anchor';
        $uri = new Uri($uriString);

        $check = (
            $uri->getScheme() === 'http' &&
            $uri->getUserInfo() === 'username:password' &&
            $uri->getHost() === 'subdomain.domain.com' &&
            $uri->getPort() === 8080 &&
            $uri->getPath() === '/path1/path2/index.php' &&
            urldecode($uri->getQuery()) === 'argument1=value1&argument2=value2&argument3[subargument1]=subvalue1' &&
            $uri->getFragment() === 'anchor'
        );

        self::assertTrue($check, 'The valid and complete URI has not been correctly transformed to an URI object');
    }

    /**
     * Uri strings
     */
    public function uriStrings()
    {
        return [
            ['http://flow.neos.io/x'],
            ['http://flow.neos.io/foo/bar?baz=1&quux=true'],
            ['https://robert@localhost/arabica/coffee.html'],
            ['http://127.0.0.1/bar.baz.com/foo.js'],
            ['http://localhost:8080?foo=bar'],
            ['http://localhost:443#hashme!x'],
            ['http://[3b00:f59:1008::212:183:20]'],
            ['http://[3b00:f59:1008::212:183:20]:443#hashme!x'],
        ];
    }

    /**
     * Checks round trips for various URIs
     *
     * @dataProvider uriStrings
     * @test
     */
    public function urisCanBeConvertedForthAndBackWithoutLoss(string $uriString)
    {
        $uri = new Uri($uriString);
        self::assertSame($uriString, (string)$uri);
    }

    /**
     * Checks round trips for various URIs
     *
     * @test
     */
    public function settingSchemeAndHostOnUriDoesNotConfuseToString()
    {
        $uri = new Uri('/no/scheme/or/host');
        $uri = $uri->withScheme('http')
                   ->withHost('localhost');
        self::assertSame('http://localhost/no/scheme/or/host', (string)$uri);
    }

    /**
     * @test
     */
    public function toStringOmitsStandardPorts()
    {
        $uri = new Uri('http://flow.neos.io');
        self::assertSame('http://flow.neos.io', (string)$uri);
        self::assertNull($uri->getPort());

        $uri = new Uri('https://flow.neos.io');
        self::assertSame('https://flow.neos.io', (string)$uri);
        self::assertNull($uri->getPort());
    }

    /**
     * @test
     */
    public function constructorParsesArgumentsWithSpecialCharactersCorrectly()
    {
        $uriString = 'http://www.neos.io/path1/?argumentäöü1=' . urlencode('valueåø€œ');
        $uri = new Uri($uriString);

        $check = (
            $uri->getScheme() === 'http' &&
            $uri->getHost() === 'www.neos.io' &&
            $uri->getPath() === '/path1/' &&
            $uri->getQuery() === 'argument%C3%A4%C3%B6%C3%BC1=value%C3%A5%C3%B8%E2%82%AC%C5%93'
        );
        self::assertTrue($check, 'The URI with special arguments has not been correctly transformed to an URI object');
    }

    /**
     * URIs for testing host parsing
     */
    public function hostTestUris()
    {
        return [
            ['http://www.neos.io/about/project', 'www.neos.io'],
            ['http://flow.neos.io/foo', 'flow.neos.io'],
            ['http://[3b00:f59:1008::212:183:20]', '[3b00:f59:1008::212:183:20]'],
        ];
    }

    /**
     * @dataProvider hostTestUris
     * @test
     */
    public function constructorParsesHostCorrectly(string $uriString, string $expectedHost)
    {
        $uri = new Uri($uriString);
        self::assertSame($expectedHost, $uri->getHost());
    }

    /**
     * @dataProvider hostTestUris
     * @test
     */
    public function settingValidHostPassesRegexCheck(string $uriString, string $plainHost)
    {
        $uri = (new Uri(''))->withHost($plainHost);
        self::assertEquals($plainHost, $uri->getHost());
    }

    /**
     * @test
     */
    public function settingInvalidHostThrowsException()
    {
        $this->markTestSkipped('This is no longer the case with PSR-7 URIs');
        $this->expectException(\InvalidArgumentException::class);
        (new Uri(''))->withHost('an#invalid.host');
    }

    public function uriStringTestUris()
    {
        return [
            ['http://username:password@subdomain.domain.com:1234/pathx1/pathx2/index.php?argument1=value1&argument2=value2&argument3%5Bsubargument1%5D=subvalue1#anchorman'],
            ['http://username:password@[2a00:f48:1008::212:183:10]:1234/pathx1/pathx2/index.php?argument1=value1&argument2=value2&argument3%5Bsubargument1%5D=subvalue1#anchorman'],
        ];
    }
    /**
     * Checks if a complete URI with all parts is transformed into an object correctly.
     *
     * @test
     * @dataProvider uriStringTestUris
     */
    public function stringRepresentationIsCorrect(string $uriString)
    {
        $uri = new Uri($uriString);
        self::assertEquals($uriString, (string)$uri, 'The string representation of the URI is not equal to the original URI string.');
    }

    /**
     * @test
     */
    public function constructingWithNotAStringThrowsException()
    {
        $error = null;
        try {
            new Uri(['foo']);
        } catch (\Throwable $error) {
        }
        $this->assertNotEmpty($error);
    }

    /**
     * @test
     */
    public function unparsableUriStringThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Uri('http:////localhost');
    }
}
