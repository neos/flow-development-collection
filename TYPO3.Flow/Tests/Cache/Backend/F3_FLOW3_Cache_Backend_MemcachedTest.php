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
 * Testcase for the cache to memcached backend
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class MemcachedTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * Sets up this testcase
	 *
	 * @return void
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function setUp() {
		if (!extension_loaded('memcache')) {
			$this->markTestSkipped('memcache extension was not available');
		}

		$this->environment = new \F3\FLOW3\Utility\Environment();
		$this->environment->setTemporaryDirectoryBase(FLOW3_PATH_DATA . 'Temporary/');
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @expectedException \F3\FLOW3\Cache\Exception
	 */
	public function setThrowsExceptionIfNoFrontEndHasBeenSet() {
		$backendOptions = array('servers' => array('localhost:11211'));
		$backend = new \F3\FLOW3\Cache\Backend\Memcached('Testing', $backendOptions);
		$backend->injectEnvironment($this->environment);
		$backend->initializeObject();
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setRejectsInvalidIdentifiers() {
		$backend = $this->setUpBackend();
		$data = 'Somedata';

		foreach (array('', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#', 'some&') as $entryIdentifier) {
			try {
				$backend->set($entryIdentifier, $data);
				$this->fail('set() did no reject the entry identifier "' . $entryIdentifier . '".');
			} catch (\InvalidArgumentException $exception) {
			}
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Cache\Exception
	 */
	public function initializeObjectThrowsExceptionIfNoMemcacheServerIsConfigured() {
		$backend = new \F3\FLOW3\Cache\Backend\Memcached('Testing');
		$backend->initializeObject();
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @expectedException \F3\FLOW3\Cache\Exception
	 */
	public function setThrowsExceptionIfConfiguredServersAreUnreachable() {
		$backend = $this->setUpBackend(array('servers' => array('julle.did.this:1234')));
		$data = 'Somedata';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function itIsPossibleToSetAndCheckExistenceInCache() {
		try {
			$backend = $this->setUpBackend();
			$data = 'Some data';
			$identifier = 'MyIdentifier';
			$backend->set($identifier, $data);
			$inCache = $backend->has($identifier);
			$this->assertTrue($inCache,'Memcache failed to set and check entry');
		} catch (\F3\FLOW3\Cache\Exception $e) {
			$this->markTestSkipped('memcached was not reachable');
		}
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function itIsPossibleToSetAndGetEntry() {
		try {
			$backend = $this->setUpBackend();
			$data = 'Some data';
			$identifier = 'MyIdentifier';
			$backend->set($identifier, $data);
			$fetchedData = $backend->get($identifier);
			$this->assertEquals($data,$fetchedData,'Memcache failed to set and retrieve data');
		} catch (\F3\FLOW3\Cache\Exception $e) {
			$this->markTestSkipped('memcached was not reachable');
		}
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function itIsPossibleToRemoveEntryFromCache() {
		try {
			$backend = $this->setUpBackend();
			$data = 'Some data';
			$identifier = 'MyIdentifier';
			$backend->set($identifier, $data);
			$backend->remove($identifier);
			$inCache = $backend->has($identifier);
			$this->assertFalse($inCache,'Failed to set and remove data from Memcache');
		} catch (\F3\FLOW3\Cache\Exception $e) {
			$this->markTestSkipped('memcached was not reachable');
		}
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function itIsPossibleToOverwriteAnEntryInTheCache() {
		try {
			$backend = $this->setUpBackend();
			$data = 'Some data';
			$identifier = 'MyIdentifier';
			$backend->set($identifier, $data);
			$otherData = 'some other data';
			$backend->set($identifier, $otherData);
			$fetchedData = $backend->get($identifier);
			$this->assertEquals($otherData, $fetchedData, 'Memcache failed to overwrite and retrieve data');
		} catch (\F3\FLOW3\Cache\Exception $e) {
			$this->markTestSkipped('memcached was not reachable');
		}
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findIdentifiersByTagFindsSetEntries() {
		try {
			$backend = $this->setUpBackend();

			$data = 'Some data';
			$entryIdentifier = 'MyIdentifier';
			$backend->set($entryIdentifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));

			$retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag1');
			$this->assertEquals($entryIdentifier, $retrieved[0], 'Could not retrieve expected entry by tag.');

			$retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
			$this->assertEquals($entryIdentifier, $retrieved[0], 'Could not retrieve expected entry by tag.');
		} catch (\F3\FLOW3\Cache\Exception $e) {
			$this->markTestSkipped('memcached was not reachable');
		}
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setRemovesTagsFromPreviousSet() {
		try {
			$backend = $this->setUpBackend();

			$data = 'Some data';
			$entryIdentifier = 'MyIdentifier';
			$backend->set($entryIdentifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));
			$backend->set($entryIdentifier, $data, array('UnitTestTag%tag3'));

			$retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
			$this->assertEquals(array(), $retrieved, 'Found entry which should no longer exist.');
		} catch (\F3\FLOW3\Cache\Exception $e) {
			$this->markTestSkipped('memcached was not reachable');
		}
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function hasReturnsFalseIfTheEntryDoesntExist() {
		$backend = $this->setUpBackend();
		$identifier = 'NonExistingIdentifier';
		$inCache = $backend->has($identifier);
		$this->assertFalse($inCache,'"has" did not return false when checking on non existing identifier');
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function removeReturnsFalseIfTheEntryDoesntExist() {
		$backend = $this->setUpBackend();
		$identifier = 'NonExistingIdentifier';
		$inCache = $backend->remove($identifier);
		$this->assertFalse($inCache,'"remove" did not return false when checking on non existing identifier');
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushByTagRejectsInvalidTags() {
		$backend = $this->setUpBackend();
		$backend->flushByTag('SomeInvalid\Tag');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function flushByTagRemovesCacheEntriesWithSpecifiedTag() {
		try {
			$backend = $this->setUpBackend();

			$data = 'some data' . microtime();
			$backend->set('BackendMemcacheTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
			$backend->set('BackendMemcacheTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
			$backend->set('BackendMemcacheTest3', $data, array('UnitTestTag%test'));

			$backend->flushByTag('UnitTestTag%special');

			$this->assertTrue($backend->has('BackendMemcacheTest1'), 'BackendMemcacheTest1');
			$this->assertFalse($backend->has('BackendMemcacheTest2'), 'BackendMemcacheTest2');
			$this->assertTrue($backend->has('BackendMemcacheTest3'), 'BackendMemcacheTest3');
		} catch (\F3\FLOW3\Cache\Exception $e) {
			$this->markTestSkipped('memcached was not reachable');
		}
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function flushRemovesAllCacheEntries() {
		try {
			$backend = $this->setUpBackend();

			$data = 'some data' . microtime();
			$backend->set('BackendMemcacheTest1', $data);
			$backend->set('BackendMemcacheTest2', $data);
			$backend->set('BackendMemcacheTest3', $data);

			$backend->flush();

			$this->assertFalse($backend->has('BackendMemcacheTest1'), 'BackendMemcacheTest1');
			$this->assertFalse($backend->has('BackendMemcacheTest2'), 'BackendMemcacheTest2');
			$this->assertFalse($backend->has('BackendMemcacheTest3'), 'BackendMemcacheTest3');
		} catch (\F3\FLOW3\Cache\Exception $e) {
			$this->markTestSkipped('memcached was not reachable');
		}
	}

	/**
	 * Sets up the memcached backend used for testing
	 *
	 * @param array $backendOptions Options for the memcache backend
	 * @return \F3\FLOW3\Cache\Backend\Memcached
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function setUpBackend(array $backendOptions = array()) {
		$cache = $this->getMock('F3\FLOW3\Cache\AbstractCache', array(), array(), '', FALSE);
		if ($backendOptions == array()) {
			$backendOptions = array('servers' => array('localhost:11211'));
		}
		$backend = new \F3\FLOW3\Cache\Backend\Memcached('Testing', $backendOptions);
		$backend->injectEnvironment($this->environment);
		$backend->setCache($cache);
		$backend->initializeObject();
		return $backend;
	}

}
?>