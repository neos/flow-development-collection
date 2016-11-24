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

use Neos\Flow\Http\AbstractMessage;
use Neos\Flow\Http\Cookie;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test case for the Http Message class
 */
class AbstractMessageTest extends UnitTestCase
{
    /**
     * @test
     */
    public function aHeaderCanBeSetAndRetrieved()
    {
        $message = $this->getAbstractMessageMock();

        $this->assertFalse($message->hasHeader('MyHeader'));

        $message->setHeader('MyHeader', 'MyValue');
        $this->assertTrue($message->hasHeader('MyHeader'));

        $this->assertEquals('MyValue', $message->getHeader('MyHeader'));
    }

    /**
     * @test
     */
    public function byDefaultHeadersOfTheSameNameAreReplaced()
    {
        $message = $this->getAbstractMessageMock();
        $message->setHeader('MyHeader', 'MyValue');
        $message->setHeader('MyHeader', 'OtherValue');

        $expectedHeaders = ['MyHeader' => ['OtherValue']];
        $this->assertEquals($expectedHeaders, $message->getHeaders()->getAll());
    }

    /**
     * @test
     */
    public function multipleHeadersOfTheSameNameMayBeDefined()
    {
        $message = $this->getAbstractMessageMock();
        $message->setHeader('MyHeader', 'MyValue', false);
        $message->setHeader('MyHeader', 'OtherValue', false);

        $expectedHeaders = ['MyHeader' => ['MyValue', 'OtherValue']];
        $this->assertEquals($expectedHeaders, $message->getHeaders()->getAll());
    }

    /**
     * @test
     */
    public function getHeaderReturnsAStringOrAnArray()
    {
        $message = $this->getAbstractMessageMock();

        $message->setHeader('MyHeader', 'MyValue');
        $this->assertEquals('MyValue', $message->getHeader('MyHeader'));

        $message->setHeader('MyHeader', 'OtherValue', false);
        $this->assertEquals(['MyValue', 'OtherValue'], $message->getHeader('MyHeader'));
    }

    /**
     * RFC 2616 / 3.7.1
     *
     * @test
     */
    public function setHeaderAddsCharsetToMediaTypeIfNoneWasSpecifiedAndTypeIsText()
    {
        $message = $this->getAbstractMessageMock();

        $message->setHeader('Content-Type', 'text/plain', true);
        $this->assertEquals('text/plain; charset=UTF-8', $message->getHeader('Content-Type'));

        $message->setHeader('Content-Type', 'text/plain', true);
        $message->setCharset('Shift_JIS');
        $this->assertEquals('text/plain; charset=Shift_JIS', $message->getHeader('Content-Type'));
        $this->assertEquals('Shift_JIS', $message->getCharset());

        $message->setHeader('Content-Type', 'image/jpeg', true);
        $message->setCharset('Shift_JIS');
        $this->assertEquals('image/jpeg', $message->getHeader('Content-Type'));
    }

    /**
     * @test
     */
    public function theDefaultCharacterSetIsUtf8()
    {
        $message = $this->getAbstractMessageMock();

        $this->assertEquals('UTF-8', $message->getCharset());
    }

    /**
     * (RFC 2616 / 3.7.1)
     *
     * @test
     */
    public function setCharsetSetsTheCharsetAndAlsoUpdatesContentTypeHeader()
    {
        $message = $this->getAbstractMessageMock();
        $message->setHeader('Content-Type', 'text/html; charset=UTF-8');

        $message->setCharset('UTF-16');
        $this->assertEquals('text/html; charset=UTF-16', $message->getHeader('Content-Type'));

        $message->setHeader('Content-Type', 'text/plain; charset=UTF-16');
        $message->setCharset('ISO-8859-1');
        $this->assertEquals('text/plain; charset=ISO-8859-1', $message->getHeader('Content-Type'));

        $message->setHeader('Content-Type', 'image/png');
        $message->setCharset('UTF-8');
        $this->assertEquals('image/png', $message->getHeader('Content-Type'));

        $message->setHeader('Content-Type', 'Text/Plain');
        $this->assertEquals('Text/Plain; charset=UTF-8', $message->getHeader('Content-Type'));
    }

    /**
     * (RFC 2616 / 3.7)
     *
     * @test
     */
    public function setCharsetAlsoUpdatesContentTypeHeaderIfSpaceIsMissing()
    {
        $message = $this->getAbstractMessageMock();

        $message->setHeader('Content-Type', 'text/plain;charset=UTF-16');
        $message->setCharset('ISO-8859-1');
        $this->assertEquals('text/plain; charset=ISO-8859-1', $message->getHeader('Content-Type'));
    }

    /**
     * (RFC 2616 / 3.7)
     *
     * @test
     */
    public function setCharsetUpdatesContentTypeHeaderAndLeavesAdditionalInformationIntact()
    {
        $message = $this->getAbstractMessageMock();

        $message->setHeader('Content-Type', 'text/plain; charSet=UTF-16; x-foo=bar');
        $message->setCharset('ISO-8859-1');
        $this->assertEquals('text/plain; charset=ISO-8859-1; x-foo=bar', $message->getHeader('Content-Type'));
    }

    /**
     * @test
     */
    public function contentCanBeSetAndRetrieved()
    {
        $message = $this->getAbstractMessageMock();

        $message->setContent('Two households, both alike in dignity, In fair Verona, where we lay our scene');
        $this->assertEquals('Two households, both alike in dignity, In fair Verona, where we lay our scene', $message->getContent());
    }

    /**
     * @test
     */
    public function setterMethodsAreChainable()
    {
        $message = $this->getAbstractMessageMock();
        $this->assertSame($message,
            $message->setContent('Foo')->setCharset('UTF-8')->setHeader('X-Foo', 'Bar')
        );
    }

    /**
     * @test
     */
    public function cookieConvenienceMethodsUseMethodsOfHeadersObject()
    {
        $cookie = new Cookie('foo', 'bar');
        $message = $this->getAbstractMessageMock();
        $message->setCookie($cookie);

        $this->assertSame($cookie, $message->getCookie('foo'));
        $this->assertSame($cookie, $message->getHeaders()->getCookie('foo'));
        $this->assertSame(['foo' => $cookie], $message->getCookies());
        $this->assertTrue($message->hasCookie('foo'));
        $message->removeCookie('foo');
        $this->assertFalse($message->hasCookie('foo'));
    }

    /**
     * @return AbstractMessage|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getAbstractMessageMock()
    {
        return $this->getMockForAbstractClass(AbstractMessage::class);
    }
}
