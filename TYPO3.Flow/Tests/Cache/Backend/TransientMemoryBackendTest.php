<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Cache\Backend;

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
 * Testcase for the Transient Memory Backend
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TransientMemoryBackendTest extends \F3\Testing\BaseTestCase {

	/**
	 * @expectedException \F3\FLOW3\Cache\Exception
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setThrowsExceptionIfNoFrontEndHasBeenSet() {
		$backend = new \F3\FLOW3\Cache\Backend\TransientMemoryBackend('Testing');

		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function itIsPossibleToSetAndCheckExistenceInCache() {
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\FrontendInterface', array(), array(), '', FALSE);
		$backend = new \F3\FLOW3\Cache\Backend\TransientMemoryBackend('Testing');
		$backend->setCache($cache);

		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
		$inCache = $backend->has($identifier);
		$this->assertTrue($inCache);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function itIsPossibleToSetAndGetEntry() {
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\FrontendInterface', array(), array(), '', FALSE);
		$backend = new \F3\FLOW3\Cache\Backend\TransientMemoryBackend('Testing');
		$backend->setCache($cache);

		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
		$fetchedData = $backend->get($identifier);
		$this->assertEquals($data, $fetchedData);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function itIsPossibleToRemoveEntryFromCache() {
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\FrontendInterface', array(), array(), '', FALSE);
		$backend = new \F3\FLOW3\Cache\Backend\TransientMemoryBackend('Testing');
		$backend->setCache($cache);

		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
		$backend->remove($identifier);
		$inCache = $backend->has($identifier);
		$this->assertFalse($inCache);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function itIsPossibleToOverwriteAnEntryInTheCache() {
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\FrontendInterface', array(), array(), '', FALSE);
		$backend = new \F3\FLOW3\Cache\Backend\TransientMemoryBackend('Testing');
		$backend->setCache($cache);

		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
		$otherData = 'some other data';
		$backend->set($identifier, $otherData);
		$fetchedData = $backend->get($identifier);
		$this->assertEquals($otherData, $fetchedData);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag() {
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\FrontendInterface', array(), array(), '', FALSE);
		$backend = new \F3\FLOW3\Cache\Backend\TransientMemoryBackend('Testing');
		$backend->setCache($cache);

		$data = 'Some data';
		$entryIdentifier = 'MyIdentifier';
		$backend->set($entryIdentifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));

		$retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag1');
		$this->assertEquals($entryIdentifier, $retrieved[0]);

		$retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
		$this->assertEquals($entryIdentifier, $retrieved[0]);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasReturnsFalseIfTheEntryDoesntExist() {
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\FrontendInterface', array(), array(), '', FALSE);
		$backend = new \F3\FLOW3\Cache\Backend\TransientMemoryBackend('Testing');
		$backend->setCache($cache);

		$identifier = 'NonExistingIdentifier';
		$inCache = $backend->has($identifier);
		$this->assertFalse($inCache);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeReturnsFalseIfTheEntryDoesntExist() {
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\FrontendInterface', array(), array(), '', FALSE);
		$backend = new \F3\FLOW3\Cache\Backend\TransientMemoryBackend('Testing');
		$backend->setCache($cache);

		$identifier = 'NonExistingIdentifier';
		$inCache = $backend->remove($identifier);
		$this->assertFalse($inCache);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function flushByTagRemovesCacheEntriesWithSpecifiedTag() {
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\FrontendInterface', array(), array(), '', FALSE);
		$backend = new \F3\FLOW3\Cache\Backend\TransientMemoryBackend('Testing');
		$backend->setCache($cache);

		$data = 'some data' . microtime();
		$backend->set('TransientMemoryBackendTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
		$backend->set('TransientMemoryBackendTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$backend->set('TransientMemoryBackendTest3', $data, array('UnitTestTag%test'));

		$backend->flushByTag('UnitTestTag%special');

		$this->assertTrue($backend->has('TransientMemoryBackendTest1'), 'TransientMemoryBackendTest1');
		$this->assertFalse($backend->has('TransientMemoryBackendTest2'), 'TransientMemoryBackendTest2');
		$this->assertTrue($backend->has('TransientMemoryBackendTest3'), 'TransientMemoryBackendTest3');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function flushRemovesAllCacheEntries() {
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\FrontendInterface', array(), array(), '', FALSE);
		$backend = new \F3\FLOW3\Cache\Backend\TransientMemoryBackend('Testing');
		$backend->setCache($cache);

		$data = 'some data' . microtime();
		$backend->set('TransientMemoryBackendTest1', $data);
		$backend->set('TransientMemoryBackendTest2', $data);
		$backend->set('TransientMemoryBackendTest3', $data);

		$backend->flush();

		$this->assertFalse($backend->has('TransientMemoryBackendTest1'), 'TransientMemoryBackendTest1');
		$this->assertFalse($backend->has('TransientMemoryBackendTest2'), 'TransientMemoryBackendTest2');
		$this->assertFalse($backend->has('TransientMemoryBackendTest3'), 'TransientMemoryBackendTest3');
	}
}
?>