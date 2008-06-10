<?php
declare(ENCODING = 'utf-8');

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
 * @version $Id:F3_FLOW3_AOP_FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the abstract cache backend
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3_FLOW3_AOP_FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Cache_AbstractBackendTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invalidEntryIdentifiersAreRecognizedAsInvalid() {
		foreach (array('', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#', 'some&') as $entryIdentifier) {
			$this->assertFalse(F3_FLOW3_Cache_AbstractBackend::isValidEntryIdentifier($entryIdentifier), 'Invalid identifier "' . $entryIdentifier . '" was not rejected.');
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function validEntryIdentifiersAreRecognizedAsValid() {
		foreach (array('_', 'abcdef', 'foo', 'bar123', '3some', '_bl_a', 'one%TWO', str_repeat('x', 250)) as $entryIdentifier) {
			$this->assertTrue(F3_FLOW3_Cache_AbstractBackend::isValidEntryIdentifier($entryIdentifier), 'Valid identifier "' . $entryIdentifier . '" was not accepted.');
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invalidTagsAreRecognizedAsInvalid() {
		foreach (array('', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#', 'some&', 'a_c') as $tag) {
			$this->assertFalse(F3_FLOW3_Cache_AbstractBackend::isValidTag($tag), 'Invalid tag "' . $tag . '" was not rejected.');
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function validTagsAreRecognizedAsValid() {
		foreach (array('abcdef', 'foo', 'bar123', '3some', 'file%Thing', '%x%', str_repeat('x', 250)) as $tag) {
			$this->assertTrue(F3_FLOW3_Cache_AbstractBackend::isValidTag($tag), 'Valid tag "' . $tag . '" was not accepted.');
		}
	}

}
?>