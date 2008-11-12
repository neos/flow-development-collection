<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Cache;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::AOP::FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the abstract cache backend
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::AOP::FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class AbstractBackendTest extends F3::Testing::BaseTestCase {

	/**
	 * @var F3::FLOW3::Cache::AbstractBackend
	 */
	protected $backend;

	/**
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$className = uniqid('ConcreteBackend_');
		eval('
			class ' . $className. ' extends F3::FLOW3::Cache::AbstractBackend {
				public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {}
				public function get($entryIdentifier) {}
				public function has($entryIdentifier) {}
				public function remove($entryIdentifier) {}
				public function flush() {}
				public function flushByTag($tag) {}
				public function findIdentifiersByTag($tag) {}
				public function collectGarbage() {}
			}
		');
		$this->backend = new $className;
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invalidEntryIdentifiersAreRecognizedAsInvalid() {
		foreach (array('', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#', 'some&') as $entryIdentifier) {
			$this->assertFalse($this->backend->isValidEntryIdentifier($entryIdentifier), 'Invalid identifier "' . $entryIdentifier . '" was not rejected.');
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function validEntryIdentifiersAreRecognizedAsValid() {
		foreach (array('_', 'abcdef', 'foo', 'bar123', '3some', '_bl_a', 'one%TWO', str_repeat('x', 250)) as $entryIdentifier) {
			$this->assertTrue($this->backend->isValidEntryIdentifier($entryIdentifier), 'Valid identifier "' . $entryIdentifier . '" was not accepted.');
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invalidTagsAreRecognizedAsInvalid() {
		foreach (array('', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#', 'some&') as $tag) {
			$this->assertFalse($this->backend->isValidTag($tag), 'Invalid tag "' . $tag . '" was not rejected.');
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function validTagsAreRecognizedAsValid() {
		foreach (array('abcdef', 'foo_baar', 'bar123', '3some', 'file%Thing', '%x%', str_repeat('x', 250)) as $tag) {
			$this->assertTrue($this->backend->isValidTag($tag), 'Valid tag "' . $tag . '" was not accepted.');
		}
	}

}
?>