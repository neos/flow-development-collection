<?php

namespace Neos\Cache\Tests\Unit\Backend;

include_once(__DIR__ . '/../../BaseTestCase.php');

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Backend\RedisBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Frontend\FrontendInterface;
use Neos\Cache\Tests\BaseTestCase;

/**
 * Testcase for the redis cache backend
 *
 * These unit tests rely on a mocked redis client.
 * @requires extension redis
 */
class RedisBackendTest extends BaseTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Redis
     */
    private $redis;

    /**
     * @var RedisBackend
     */
    private $backend;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|FrontendInterface
     */
    private $cache;

    /**
     * Set up test case
     * @return void
     */
    protected function setUp(): void
    {
        $phpredisVersion = phpversion('redis');
        if (version_compare($phpredisVersion, '5.0.0', '<')) {
            $this->markTestSkipped(sprintf('phpredis extension version %s is not supported. Please update to version 5.0.0+.', $phpredisVersion));
        }

        $this->redis = $this->getMockBuilder(\Redis::class)->disableOriginalConstructor()->getMock();
        $this->cache = $this->createMock(FrontendInterface::class);
        $this->cache->method('getIdentifier')
            ->willReturn('Foo_Cache');

        $mockEnvironmentConfiguration = $this->getMockBuilder(EnvironmentConfiguration::class)->setConstructorArgs([
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        ])->getMock();

        $this->backend = new RedisBackend($mockEnvironmentConfiguration, ['redis' => $this->redis]);
        $this->backend->setCache($this->cache);

        // set this to false manually, since the check in isFrozen leads to null (instead of a boolean)
        // as the exists call is not mocked (and cannot easily be mocked, as it is used for different
        // things.)
        $this->inject($this->backend, 'frozen', false);
    }

    /**
     * @test
     */
    public function findIdentifiersByTagInvokesRedis(): void
    {
        $this->redis->expects(self::once())
            ->method('sMembers')
            ->with('d41d8cd98f00b204e9800998ecf8427e:Foo_Cache:tag:some_tag')
            ->willReturn(['entry_1', 'entry_2']);

        $this->assertEquals(['entry_1', 'entry_2'], $this->backend->findIdentifiersByTag('some_tag'));
    }

    /**
     * @test
     */
    public function freezeInvokesRedis(): void
    {
        $this->redis->method('exec')
            ->willReturn($this->redis);

        $this->redis->expects(self::once())
            ->method('keys')
            ->willReturn(['entry_1', 'entry_2']);

        $this->redis->expects(self::exactly(2))
            ->method('persist');

        $this->redis->expects(self::once())
            ->method('set')
            ->with('d41d8cd98f00b204e9800998ecf8427e:Foo_Cache:frozen', true);

        $this->backend->freeze();
    }

    /**
     * @test
     */
    public function setUsesDefaultLifetimeIfNotProvided(): void
    {
        $defaultLifetime = random_int(1, 9999);
        $this->backend->setDefaultLifetime($defaultLifetime);
        $expected = ['ex' => $defaultLifetime];

        $this->redis->method('multi')
            ->willReturn($this->redis);

        $this->redis->expects(self::once())
            ->method('set')
            ->with($this->anything(), $this->anything(), $expected)
            ->willReturn($this->redis);

        $this->backend->set('foo', 'bar');
    }

    /**
     * @test
     */
    public function setUsesProvidedLifetime(): void
    {
        $defaultLifetime = 3600;
        $this->backend->setDefaultLifetime($defaultLifetime);
        $expected = ['ex' => 1600];

        $this->redis->method('multi')
            ->willReturn($this->redis);

        $this->redis->expects(self::once())
            ->method('set')
            ->with($this->anything(), $this->anything(), $expected)
            ->willReturn($this->redis);

        $this->backend->set('foo', 'bar', [], 1600);
    }

    /**
     * @test
     */
    public function setAddsEntryToRedis(): void
    {
        $this->redis->method('multi')
            ->willReturn($this->redis);

        $this->redis->expects(self::once())
            ->method('set')
            ->with('d41d8cd98f00b204e9800998ecf8427e:Foo_Cache:entry:entry_1', 'foo')
            ->willReturn($this->redis);

        $this->backend->set('entry_1', 'foo');
    }

    /**
     * @test
     */
    public function getInvokesRedis(): void
    {
        $this->redis->expects(self::once())
            ->method('get')
            ->with('d41d8cd98f00b204e9800998ecf8427e:Foo_Cache:entry:foo')
            ->willReturn('bar');

        self::assertEquals('bar', $this->backend->get('foo'));
    }

    /**
     * @test
     */
    public function hasInvokesRedis(): void
    {
        $this->redis->expects(self::once())
            ->method('exists')
            ->with('d41d8cd98f00b204e9800998ecf8427e:Foo_Cache:entry:foo')
            ->willReturn(true);

        self::assertEquals(true, $this->backend->has('foo'));
    }

    /**
     * @test
     * @dataProvider writingOperationsProvider
     * @param string $method
     */
    public function writingOperationsThrowAnExceptionIfCacheIsFrozen(string $method): void
    {
        $this->expectException(\RuntimeException::class);
        $this->inject($this->backend, 'frozen', null);
        $this->redis->expects(self::once())
            ->method('exists')
            ->with('d41d8cd98f00b204e9800998ecf8427e:Foo_Cache:frozen')
            ->willReturn(true);

        $this->backend->$method('foo', 'bar');
    }

    /**
     * @test
     * @dataProvider batchWritingOperationsProvider
     * @param string $method
     */
    public function batchWritingOperationsThrowAnExceptionIfCacheIsFrozen(string $method): void
    {
        $this->expectException(\RuntimeException::class);
        $this->inject($this->backend, 'frozen', null);
        $this->redis->expects(self::once())
            ->method('exists')
            ->with('d41d8cd98f00b204e9800998ecf8427e:Foo_Cache:frozen')
            ->willReturn(true);

        $this->backend->$method(['foo', 'bar']);
    }

    /**
     * @return array
     */
    public static function writingOperationsProvider(): array
    {
        return [
            ['set'],
            ['remove'],
            ['flushByTag'],
            ['freeze']
        ];
    }

    /**
     * @return array
     */
    public static function batchWritingOperationsProvider(): array
    {
        return [
            ['flushByTags'],
        ];
    }
}
