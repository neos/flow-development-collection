<?php
namespace TYPO3\Flow\Tests\Unit\Cache\Backend;

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
 * These unit tests rely on a mocked redis client.
 * @requires extension redis
 */
class RedisBackendTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $redis;

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

		$this->redis = $this->getMockBuilder('\Redis')->disableOriginalConstructor()->getMock();
		$this->cache = $this->getMock('\TYPO3\Flow\Cache\Frontend\FrontendInterface');
		$this->cache->expects($this->any())
			->method('getIdentifier')
			->will($this->returnValue('Foo_Cache'));

		$this->backend = new RedisBackend(new ApplicationContext('Development'), array(), $this->redis);
		$this->backend->setCache($this->cache);
	}

	/**
	 * @test
	 */
	public function findIdentifiersByTagInvokesRedis() {
		$this->redis->expects($this->once())
			->method('sMembers')
			->with('Foo_Cache:tag:some_tag')
			->will($this->returnValue(array('entry_1', 'entry_2')));

		$this->assertEquals(array('entry_1', 'entry_2'), $this->backend->findIdentifiersByTag('some_tag'));
	}

	/**
	 * @test
	 */
	public function freezeInvokesRedis() {
		$this->redis->expects($this->once())
			->method('lRange')
			->with('Foo_Cache:entries', 0, -1)
			->will($this->returnValue(array('entry_1', 'entry_2')));

		$this->redis->expects($this->exactly(2))
			->method('persist');

		$this->redis->expects($this->once())
			->method('set')
			->with('Foo_Cache:frozen', TRUE);

		$this->backend->freeze();
	}

	/**
	 * @test
	 */
	public function setUsesDefaultLifetimeIfNotProvided() {
		$defaultLifetime = rand(1, 9999);
		$this->backend->setDefaultLifetime($defaultLifetime);
		$expected = array('ex' => $defaultLifetime);

		$this->redis->expects($this->once())
			->method('set')
			->with($this->anything(), $this->anything(), $expected);

		$this->backend->set('foo', 'bar');
	}

	/**
	 * @test
	 */
	public function setUsesProvidedLifetime() {
		$defaultLifetime = 3600;
		$this->backend->setDefaultLifetime($defaultLifetime);
		$expected = array('ex' => 1600);

		$this->redis->expects($this->once())
			->method('set')
			->with($this->anything(), $this->anything(), $expected);

		$this->backend->set('foo', 'bar', array(), 1600);
	}

	/**
	 * @test
	 */
	public function setAddsEntryToRedis() {
		$this->redis->expects($this->once())
			->method('set')
			->with('Foo_Cache:entry:entry_1', 'foo');

		$this->backend->set('entry_1', 'foo');
	}

	/**
	 * @test
	 */
	public function getInvokesRedis() {
		$this->redis->expects($this->once())
			->method('get')
			->with('Foo_Cache:entry:foo')
			->will($this->returnValue('bar'));

		$this->assertEquals('bar', $this->backend->get('foo'));
	}

	/**
	 * @test
	 */
	public function hasInvokesRedis() {
		$this->redis->expects($this->once())
			->method('exists')
			->with('Foo_Cache:entry:foo')
			->will($this->returnValue(TRUE));

		$this->assertEquals(TRUE, $this->backend->has('foo'));
	}

	/**
	 * @test
	 * @dataProvider writingOperationsProvider
	 * @expectedException \RuntimeException
	 */
	public function writingOperationsThrowAnExceptionIfCacheIsFrozen($method) {
		$this->redis->expects($this->once())
			->method('exists')
			->with('Foo_Cache:frozen')
			->will($this->returnValue(TRUE));

		$this->backend->$method('foo', 'bar');
	}

	/**
	 * @return array
	 */
	public static function writingOperationsProvider() {
		return array(
			array('set'),
			array('remove'),
			array('flushByTag'),
			array('freeze')
		);
	}

}
