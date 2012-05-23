<?php
namespace TYPO3\FLOW3\Tests\Unit\Http;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


use TYPO3\FLOW3\Http\Headers;
use TYPO3\FLOW3\Http\Cookie;
use TYPO3\FLOW3\Http\Uri;

/**
 * Testcase for the Http Headers class
 */
class HeadersTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function headerFieldsCanBeSpecifiedToTheConstructor() {
		$headers = new Headers(array('User-Agent' => 'Espresso Machine', 'Server' => array('Foo', 'Bar')));
		$this->assertSame('Espresso Machine', $headers->get('User-Agent'));
		$this->assertSame(array('Foo', 'Bar'), $headers->get('Server'));
		$this->assertTrue($headers->has('Server'));
	}

	/**
	 * @test
	 */
	public function createFromServerCreatesFieldsFromSpecifiedServerSuperglobal() {
		$server = array(
			'HTTP_FOO' => 'Robusta',
			'HTTP_BAR_BAZ' => 'Arabica',
		);

		$headers = Headers::createFromServer($server);

		$this->assertEquals('Robusta', $headers->get('Foo'));
		$this->assertEquals('Arabica', $headers->get('Bar-Baz'));
	}

	/**
	 * @test
	 */
	public function createFromServerSimulatesAuthorizationHeaderIfPHPAuthVariablesArePresent() {
		$server = array(
			'PHP_AUTH_USER' => 'robert',
			'PHP_AUTH_PW' => 'mysecretpassword, containing a : colon ;-)',
		);

		$headers = Headers::createFromServer($server);

		$expectedValue = 'Basic ' . base64_encode('robert:mysecretpassword, containing a : colon ;-)');
		$this->assertEquals($expectedValue, $headers->get('Authorization'));
		$this->assertFalse($headers->has('User'));
	}

	/**
	 * @test
	 */
	public function headerFieldsCanBeReplaced() {
		$headers = new Headers();
		$headers->set('Host', 'myhost.com');
		$headers->set('Host', 'yourhost.com');
		$this->assertSame('yourhost.com', $headers->get('Host'));
	}

	/**
	 * @test
	 */
	public function headerFieldsCanExistMultipleTimes() {
		$headers = new Headers();
		$headers->set('X-Powered-By', 'FLOW3');
		$headers->set('X-Powered-By', 'TYPO3', FALSE);
		$this->assertSame(array('FLOW3', 'TYPO3'), $headers->get('X-Powered-By'));
	}

	/**
	 * @test
	 */
	public function getReturnsNullForNonExistingHeader() {
		$headers = new Headers();
		$headers->set('X-Powered-By', 'FLOW3');
		$this->assertFalse($headers->has('X-Empowered-By'));
		$this->assertNull($headers->get('X-Empowered-By'));
	}

	/**
	 * @test
	 */
	public function getAllReturnsAllHeaderFields() {
		$specifiedFields = array('X-Coffee' => 'Arabica', 'Host' =>'myhost.com');
		$headers = new Headers($specifiedFields);

		$expectedFields = array('X-Coffee' => array('Arabica'), 'Host' => array('myhost.com'));
		$this->assertEquals($expectedFields, $headers->getAll());
	}

	/**
	 * @test
	 */
	public function setGetAndGetAllConvertDatesFromDateObjectsToStringAndViceVersa() {
		$now = new \DateTime();
		$headers = new Headers();

		$headers->set('Last-Modified', $now);
		$this->assertEquals($now->format(DATE_RFC2822), $headers->get('Last-Modified')->format(DATE_RFC2822));

		$headers->set('X-Test-Run-At', $now);
		$this->assertEquals($now->format(DATE_RFC2822), $headers->get('X-Test-Run-At')->format(DATE_RFC2822));
	}

	/**
	 * @test
	 */
	public function removeRemovesTheSpecifiedHeader() {
		$specifiedFields = array('X-Coffee' => 'Arabica', 'Host' =>'myhost.com');
		$headers = new Headers($specifiedFields);

		$headers->remove('X-Coffee');
		$headers->remove('X-This-Does-Not-Exist-Anyway');

		$this->assertEquals(array('Host' => array('myhost.com')), $headers->getAll());
	}

	/**
	 * @test
	 */
	public function singleCookieCanBeSetAndRetrieved() {
		$headers = new Headers();
		$cookie = new Cookie('Dark-Chocolate-Chip');
		$headers->setCookie($cookie);
		$this->assertSame($cookie, $headers->getCookie('Dark-Chocolate-Chip'));
	}

	/**
	 * @test
	 */
	public function cookiesCanBeRemoved() {
		$headers = new Headers();
		$headers->setCookie(new Cookie('Dark-Chocolate-Chip'));

		$this->assertTrue($headers->hasCookie('Dark-Chocolate-Chip'));
		$headers->removeCookie('Dark-Chocolate-Chip');
		$this->assertFalse($headers->hasCookie('Dark-Chocolate-Chip'));
	}

	/**
	 * @test
	 */
	public function getCookiesReturnsAllCookies() {
		$cookies = array(
			'Dark-Chocolate-Chip' => new Cookie('Dark-Chocolate-Chip'),
			'Pecan-Maple-Choc' => new Cookie('Pecan-Maple-Choc'),
			'Coffee-Fudge-Mess' => new Cookie('Coffee-Fudge-Mess'),
		);

		$headers = new Headers();
		$headers->setCookie($cookies['Dark-Chocolate-Chip']);
		$headers->setCookie($cookies['Pecan-Maple-Choc']);
		$headers->setCookie($cookies['Coffee-Fudge-Mess']);

		$headers->eatCookie('Coffee-Fudge-Mess');
		unset($cookies['Coffee-Fudge-Mess']);

		$this->assertEquals($cookies, $headers->getCookies());
	}
}

?>