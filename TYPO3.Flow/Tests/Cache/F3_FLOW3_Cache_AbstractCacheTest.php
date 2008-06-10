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
 * Testcase for the abstract cache frontend
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3_FLOW3_AOP_FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Cache_AbstractCacheTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theConstructorAcceptsValidIdentifiers() {
		$mockBackend = $this->getMock('F3_FLOW3_Cache_AbstractBackend', array('load', 'save', 'has', 'remove', 'findEntriesByTag'), array(), '', FALSE);
		foreach (array('x', 'someValue', '123fivesixseveneight', 'ab_cd%', rawurlencode('package://some/äöü$&% sadf'), str_repeat('x', 250)) as $identifier) {
			$abstractCache = $this->getMock('F3_FLOW3_Cache_AbstractCache', array('__construct', 'load', 'save', 'has', 'remove', 'findEntriesByTag'), array($identifier, $mockBackend));
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theConstructorRejectsInvalidIdentifiers() {
		$mockBackend = $this->getMock('F3_FLOW3_Cache_AbstractBackend', array('load', 'save', 'has', 'remove', 'findEntriesByTag'), array(), '', FALSE);
		foreach (array('', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#', 'some&') as $identifier) {
			try {
				$abstractCache = $this->getMock('F3_FLOW3_Cache_AbstractCache', array('__construct', 'load', 'save', 'has', 'remove', 'findEntriesByTag'), array($identifier, $mockBackend));
				$this->fail('Identifier "' . $identifier . '" was not rejected.');
			} catch (InvalidArgumentException $exception) {
			}
		}
	}
}
?>