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
 * @version $Id$
 */

/**
 * Testcase for the cache to memcached backend
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3_FLOW3_AOP_FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Cache_Backend_MemcachedTest extends F3_Testing_BaseTestCase {

	/**
	 * @var F3_FLOW3_Cache_Backend_File If set, the tearDown() method will clean up the cache subdirectory used by this unit test.
	 */
	protected $backend;

	/**
	 * Sets up this testcase
	 *
	 * @return void
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function setUp() {
		if(!extension_loaded('memcache')) {
			$this->markTestSkipped('memcache extension was not available');
		}
		$this->backend = NULL;
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isPrototype() {
		$backend1 = $this->componentManager->getComponent('F3_FLOW3_Cache_Backend_Memcached', $this->componentManager->getContext());
		$backend2 = $this->componentManager->getComponent('F3_FLOW3_Cache_Backend_Memcached', $this->componentManager->getContext());
		$this->assertNotSame($backend1, $backend2, 'Memcached Backend seems to be singleton!');
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function saveThrowsExceptionIfNoFrontEndHasBeenSet() {
		$context = $this->componentManager->getContext();
		$backend = $this->componentManager->getComponent('F3_FLOW3_Cache_Backend_Memcached', $context);
		$this->backend = $backend;
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		try {
			$backend->save($data,$identifier);
			$this->fail('save() did not throw exception on missing cache frontend');
		} catch (F3_FLOW3_Cache_Exception $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function saveRejectsInvalidIdentifiers() {
		$backend = $this->setUpBackend();
		$data = 'Some data';

		foreach (array('', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#', 'some&') as $entryIdentifier) {
			try {
				$backend->save($entryIdentifier, $data);
				$this->fail('save() did no reject the entry identifier "' . $entryIdentifier . '".');
			} catch (InvalidArgumentException $exception) {
			}
		}
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function saveThrowsExceptionIfNoMemcacheServerIsConfigured() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		try {
			$backend->save($data,$identifier);
			$this->fail('save() did not throw exception on missing configuration of servers');
		} catch (F3_FLOW3_Cache_Exception  $exception) {
		}
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function saveThrowsExceptionIfConfiguredServersAreUnreachable() {
		$backend = $this->setUpBackend();
		$backend->setServers(array('julle.did.this:1234'));
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		try {
			$backend->save($data,$identifier);
			$this->fail('save() did not throw exception on missing connection');
		} catch (F3_FLOW3_Cache_Exception  $exception) {
		}
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function itIsPossibleToSaveAndCheckExistenceInCache() {
		$backend = $this->setUpBackend();
		$backend->setServers(array('localhost:11211'));
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->save($data,$identifier);
		$inCache = $backend->has($identifier);
		$this->assertTrue($inCache,'Memcache failed to set and check entry');
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function itIsPossibleToSaveAndGetEntry() {
		$backend = $this->setUpBackend();
		$backend->setServers(array('localhost:11211'));
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->save($data,$identifier);
		$fetchedData = $backend->load($identifier);
		$this->assertEquals($data,$fetchedData,'Memcache failed to set and retrieve data');
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function itIsPossibleToRemoveEntryFromCache() {
		$backend = $this->setUpBackend();
		$backend->setServers(array('localhost:11211'));
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->save($data,$identifier);
		$backend->remove($identifier);
		$inCache = $backend->has($identifier);
		$this->assertFalse($inCache,'Failed to set and remove data from Memcache');
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function itIsPossibleToOverwriteAnEntryInTheCache() {
		$backend = $this->setUpBackend();
		$backend->setServers(array('localhost:11211'));
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->save($data,$identifier);
		$otherData = 'some other data';
		$backend->save($otherData,$identifier);
		$fetchedData = $backend->load($identifier);
		$this->assertEquals($otherData,$fetchedData,'Memcache failed to overwrite and retrieve data');
	}

	/**
	 * @test
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function hasReturnsFalseIfTheEntryDoesntExist() {
		$backend = $this->setUpBackend();
		$backend->setServers(array('localhost:11211'));
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
		$backend->setServers(array('localhost:11211'));
		$identifier = 'NonExistingIdentifier';
		$inCache = $backend->remove($identifier);
		$this->assertFalse($inCache,'"remove" did not return false when checking on non existing identifier');
	}

	/**
	 * Creates a cache mock
	 *
	 * @return F3_FLOW3_Cache_AbstractCache mock
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	protected function getMockCache() {
		return $this->getMock('F3_FLOW3_Cache_AbstractCache', array(), array(), '', FALSE);
	}

	/**
	 * Sets up the memcached backend used for testing
	 *
	 * @return F3_FLOW3_Cache_Backend_Memcached
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	protected function setUpBackend() {
		$cache = $this->getMockCache();
		$context = $this->componentManager->getContext();
		$backend = $this->componentManager->getComponent('F3_FLOW3_Cache_Backend_Memcached', $context);
		$this->backend = $backend;
		$backend->setCache($cache);
		return $backend;
	}

}
?>