<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Cache\Frontend;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the abstract cache frontend
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AbstractFrontendTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theConstructorAcceptsValidIdentifiers() {
		$mockBackend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		foreach (array('x', 'someValue', '123fivesixseveneight', 'some&', 'ab_cd%', rawurlencode('package://some/äöü$&% sadf'), str_repeat('x', 250)) as $identifier) {
			$abstractCache = $this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag', 'flush', 'flushByTag', 'collectGarbage'), array($identifier, $mockBackend));
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theConstructorRejectsInvalidIdentifiers() {
		$mockBackend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		foreach (array('', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#') as $identifier) {
			try {
				$abstractCache = $this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag', 'flush', 'flushByTag', 'collectGarbage'), array($identifier, $mockBackend));
				$this->fail('Identifier "' . $identifier . '" was not rejected.');
			} catch (\InvalidArgumentException $exception) {
			}
		}
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function flushCallsBackend() {
		$identifier = 'someCacheIdentifier';
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('flush');

		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
		$cache->flush();
	}


	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushByTagRejectsInvalidTags() {
		$identifier = 'someCacheIdentifier';
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\BackendInterface', array(), array(), '', FALSE);
		$backend->expects($this->never())->method('flushByTag');

		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
		$cache->flushByTag('SomeInvalid\Tag');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function flushByTagCallsBackend() {
		$tag = 'sometag';
		$identifier = 'someCacheIdentifier';
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('flushByTag')->with($tag);

		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
		$cache->flushByTag($tag);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function collectGarbageCallsBackend() {
		$identifier = 'someCacheIdentifier';
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('collectGarbage');

		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
		$cache->collectGarbage();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassTagRendersTagWhichCanBeUsedToTagACacheEntryWithACertainClass() {
		$identifier = 'someCacheIdentifier';
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);

		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
		$this->assertEquals('%CLASS%F3_Foo_Bar_Baz', $cache->getClassTag('F3\Foo\Bar\Baz'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invalidEntryIdentifiersAreRecognizedAsInvalid() {
		$identifier = 'someCacheIdentifier';
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array(), array(), '', FALSE);
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
		foreach (array('', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#') as $entryIdentifier) {
			$this->assertFalse($cache->isValidEntryIdentifier($entryIdentifier), 'Invalid identifier "' . $entryIdentifier . '" was not rejected.');
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function validEntryIdentifiersAreRecognizedAsValid() {
		$identifier = 'someCacheIdentifier';
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array(), array(), '', FALSE);
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
		foreach (array('_', 'abc-def', 'foo', 'bar123', '3some', '_bl_a', 'some&', 'one%TWO', str_repeat('x', 250)) as $entryIdentifier) {
			$this->assertTrue($cache->isValidEntryIdentifier($entryIdentifier), 'Valid identifier "' . $entryIdentifier . '" was not accepted.');
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invalidTagsAreRecognizedAsInvalid() {
		$identifier = 'someCacheIdentifier';
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array(), array(), '', FALSE);
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
		foreach (array('', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#') as $tag) {
			$this->assertFalse($cache->isValidTag($tag), 'Invalid tag "' . $tag . '" was not rejected.');
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function validTagsAreRecognizedAsValid() {
		$identifier = 'someCacheIdentifier';
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array(), array(), '', FALSE);
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
		foreach (array('abcdef', 'foo-bar', 'foo_baar', 'bar123', '3some', 'file%Thing', 'some&', '%x%', str_repeat('x', 250)) as $tag) {
			$this->assertTrue($cache->isValidTag($tag), 'Valid tag "' . $tag . '" was not accepted.');
		}
	}

}
?>