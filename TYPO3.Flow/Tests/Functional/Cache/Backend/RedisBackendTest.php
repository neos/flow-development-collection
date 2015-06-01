<?php
namespace TYPO3\Flow\Tests\Functional\Cache\Backend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Cache\Backend\RedisBackend;
use TYPO3\Flow\Core\ApplicationContext;

/**
 * Testcase for the redis cache backend
 *
 * These tests use an actual Redis instance and will place and remove keys in db 0!
 * Since all keys have the 'TestCache:' prefix, running the tests should have
 * no side effects on non-related cache entries.
 *
 * Tests require Redis listening on 127.0.0.1:6379.
 * @requires extension redis
 */
class RedisBackendTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var RedisBackend
	 */
	private $backend;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $cache;

	/**
	 * Set up test case
	 * @return void
	 */
	public function setUp() {
		$phpredisVersion = phpversion('redis');
		if (version_compare($phpredisVersion, '1.2.0', '<')) {
			$this->markTestSkipped(sprintf('phpredis extension version %s is not supported. Please update to verson 1.2.0+.', $phpredisVersion));
		}
		try {
			if (!@fsockopen('127.0.0.1', 6379)) {
				$this->markTestSkipped('redis server not reachable');
			}
		} catch (\Exception $e) {
			$this->markTestSkipped('redis server not reachable');
		}
		$this->backend = new RedisBackend(
			new ApplicationContext('Testing'), array('hostname' => '127.0.0.1', 'database' => 0)
		);
		$this->cache = $this->getMock('\TYPO3\Flow\Cache\Frontend\FrontendInterface');
		$this->cache->expects($this->any())->method('getIdentifier')->will($this->returnValue('TestCache'));
		$this->backend->setCache($this->cache);
		$this->backend->flush();
	}

	/**
	 * Tear down test case
	 * @return void
	 */
	public function tearDown() {
		if ($this->backend instanceof RedisBackend) {
			$this->backend->flush();
		}
	}

	/**
	 * @test
	 */
	public function setAddsCacheEntry() {
		$this->backend->set('some_entry', 'foo');
		$this->assertEquals('foo', $this->backend->get('some_entry'));
	}

	/**
	 * @test
	 */
	public function setAddsTags() {
		$this->backend->set('some_entry', 'foo', array('tag1', 'tag2'));
		$this->backend->set('some_other_entry', 'foo', array('tag2', 'tag3'));

		$this->assertEquals(array('some_entry'), $this->backend->findIdentifiersByTag('tag1'));
		$expected = array('some_entry', 'some_other_entry');
		$actual = $this->backend->findIdentifiersByTag('tag2');

		// since Redis does not garantuee the order of values in sets, manually sort the array for comparison
		natsort($actual);
		$actual = array_values($actual);

		$this->assertEquals($expected, $actual);
		$this->assertEquals(array('some_other_entry'), $this->backend->findIdentifiersByTag('tag3'));
	}

	/**
	 * @test
	 */
	public function cacheIsIterable() {
		for ($i = 0; $i < 100; $i++) {
			$this->backend->set('entry_' . $i, 'foo');
		}
		$actualEntries = array();
		foreach ($this->backend as $key => $value) {
			$actualEntries[] = $key;
		}

		$this->assertCount(100, $actualEntries);

		for ($i = 0; $i < 100; $i++) {
			$this->assertContains('entry_' . $i, $actualEntries);
		}
	}

	/**
	 * @test
	 */
	public function freezeFreezesTheCache() {
		$this->assertFalse($this->backend->isFrozen());
		for ($i = 0; $i < 10; $i++) {
			$this->backend->set('entry_' . $i, 'foo');
		}
		$this->backend->freeze();
		$this->assertTrue($this->backend->isFrozen());
	}

	/**
	 * @test
	 */
	public function flushByTagFlushesEntryByTag() {
		for ($i = 0; $i < 10; $i++) {
			$this->backend->set('entry_' . $i, 'foo', array('tag1', 'tag2'));
		}
		for ($i = 10; $i < 20; $i++) {
			$this->backend->set('entry_' . $i, 'foo', array('tag2'));
		}
		$this->assertCount(10, $this->backend->findIdentifiersByTag('tag1'));
		$this->assertCount(20, $this->backend->findIdentifiersByTag('tag2'));

		$count = $this->backend->flushByTag('tag1');
		$this->assertEquals(10, $count, 'flushByTag returns amount of flushed entries');
		$this->assertCount(0, $this->backend->findIdentifiersByTag('tag1'));
		$this->assertCount(10, $this->backend->findIdentifiersByTag('tag2'));
	}

	/**
	 * @test
	 */
	public function flushFlushesCache() {
		for ($i = 0; $i < 10; $i++) {
			$this->backend->set('entry_' . $i, 'foo', array('tag1'));
		}
		$this->assertTrue($this->backend->has('entry_5'));
		$this->backend->flush();
		$this->assertFalse($this->backend->has('entry_5'));
	}

	/**
	 * @test
	 */
	public function removeRemovesEntryFromCache() {
		for ($i = 0; $i < 10; $i++) {
			$this->backend->set('entry_' . $i, 'foo', array('tag1'));
		}
		$this->assertCount(10, $this->backend->findIdentifiersByTag('tag1'));
		$this->assertEquals('foo', $this->backend->get('entry_1'));
		$actualEntries = array();
		foreach ($this->backend as $key => $value) {
			$actualEntries[] = $key;
		}
		$this->assertCount(10, $actualEntries);

		$this->backend->remove('entry_3');
		$this->assertCount(9, $this->backend->findIdentifiersByTag('tag1'));
		$this->assertFalse($this->backend->get('entry_3'));
		$actualEntries = array();
		foreach ($this->backend as $key => $value) {
			$actualEntries[] = $key;
		}
		$this->assertCount(9, $actualEntries);
	}

}
