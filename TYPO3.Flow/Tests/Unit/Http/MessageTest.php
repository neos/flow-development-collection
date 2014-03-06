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

use TYPO3\Flow\Http\Message;
use TYPO3\Flow\Http\Cookie;

/**
 * Testcase for the Http Message class
 */
class MessageTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function aHeaderCanBeSetAndRetrieved() {
		$message = new Message();

		$this->assertFalse($message->hasHeader('MyHeader'));

		$message->setHeader('MyHeader', 'MyValue');
		$this->assertTrue($message->hasHeader('MyHeader'));

		$this->assertEquals('MyValue', $message->getHeader('MyHeader'));
	}

	/**
	 * @test
	 */
	public function byDefaultHeadersOfTheSameNameAreReplaced() {
		$message = new Message();
		$message->setHeader('MyHeader', 'MyValue');
		$message->setHeader('MyHeader', 'OtherValue');

		$expectedHeaders = array('MyHeader' => array('OtherValue'));
		$this->assertEquals($expectedHeaders, $message->getHeaders()->getAll());
	}

	/**
	 * @test
	 */
	public function multipleHeadersOfTheSameNameMayBeDefined() {
		$message = new Message();
		$message->setHeader('MyHeader', 'MyValue', FALSE);
		$message->setHeader('MyHeader', 'OtherValue', FALSE);

		$expectedHeaders = array('MyHeader' => array('MyValue', 'OtherValue'));
		$this->assertEquals($expectedHeaders, $message->getHeaders()->getAll());
	}

	/**
	 * @test
	 */
	public function getHeaderReturnsAStringOrAnArray() {
		$message = new Message();

		$message->setHeader('MyHeader', 'MyValue');
		$this->assertEquals('MyValue', $message->getHeader('MyHeader'));

		$message->setHeader('MyHeader', 'OtherValue', FALSE);
		$this->assertEquals(array('MyValue', 'OtherValue'), $message->getHeader('MyHeader'));
	}

	/**
	 * RFC 2616 / 3.7.1
	 *
	 * @test
	 */
	public function setHeaderAddsCharsetToMediaTypeIfNoneWasSpecifiedAndTypeIsText() {
		$message = new Message();

		$message->setHeader('Content-Type', 'text/plain', TRUE);
		$this->assertEquals('text/plain; charset=UTF-8', $message->getHeader('Content-Type'));

		$message->setHeader('Content-Type', 'text/plain', TRUE);
		$message->setCharset('Shift_JIS');
		$this->assertEquals('text/plain; charset=Shift_JIS', $message->getHeader('Content-Type'));
		$this->assertEquals('Shift_JIS', $message->getCharset());

		$message->setHeader('Content-Type', 'image/jpeg', TRUE);
		$message->setCharset('Shift_JIS');
		$this->assertEquals('image/jpeg', $message->getHeader('Content-Type'));
	}

	/**
	 * @test
	 */
	public function theDefaultCharacterSetIsUtf8() {
		$message = new Message();

		$this->assertEquals('UTF-8', $message->getCharset());
	}

	/**
	 * (RFC 2616 / 3.7.1)
	 *
	 * @test
	 */
	public function setCharsetSetsTheCharsetAndAlsoUpdatesContentTypeHeader() {
		$message = new Message();
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
	public function setCharsetAlsoUpdatesContentTypeHeaderIfSpaceIsMissing() {
		$message = new Message();

		$message->setHeader('Content-Type', 'text/plain;charset=UTF-16');
		$message->setCharset('ISO-8859-1');
		$this->assertEquals('text/plain; charset=ISO-8859-1', $message->getHeader('Content-Type'));
	}

	/**
	 * (RFC 2616 / 3.7)
	 *
	 * @test
	 */
	public function setCharsetUpdatesContentTypeHeaderAndLeavesAdditionalInformationIntact() {
		$message = new Message();

		$message->setHeader('Content-Type', 'text/plain; charSet=UTF-16; x-foo=bar');
		$message->setCharset('ISO-8859-1');
		$this->assertEquals('text/plain; charset=ISO-8859-1; x-foo=bar', $message->getHeader('Content-Type'));
	}

	/**
	 * @test
	 */
	public function contentCanBeSetAndRetrieved() {
		$message = new Message();

		$message->setContent('Two households, both alike in dignity, In fair Verona, where we lay our scene');
		$this->assertEquals('Two households, both alike in dignity, In fair Verona, where we lay our scene', $message->getContent());
	}

	/**
	 * @test
	 */
	public function setterMethodsAreChainable() {
		$message = new Message();
		$this->assertSame($message,
			$message->setContent('Foo')->setCharset('UTF-8')->setHeader('X-Foo', 'Bar')
		);
	}

	/**
	 * @test
	 */
	public function cookieConvenienceMethodsUseMethodsOfHeadersObject() {
		$cookie = new Cookie('foo', 'bar');
		$message = new Message();
		$message->setCookie($cookie);

		$this->assertSame($cookie, $message->getCookie('foo'));
		$this->assertSame($cookie, $message->getHeaders()->getCookie('foo'));
		$this->assertSame(array('foo' => $cookie), $message->getCookies());
		$this->assertTrue($message->hasCookie('foo'));
		$message->removeCookie('foo');
		$this->assertFalse($message->hasCookie('foo'));
	}
}
