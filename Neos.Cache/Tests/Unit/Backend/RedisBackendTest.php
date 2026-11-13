<?php

declare(strict_types=1);

namespace Neos\Cache\Tests\Unit\Backend;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

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
 */
#[RequiresPhpExtension('redis')]
final class RedisBackendTest extends BaseTestCase
{
    /**
     * @var MockObject|\Redis
     */
    private $redis;

    /**
     * @var RedisBackend
     */
    private $backend;

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

        $this->redis = $this->createMock(\Redis::class);
        $cache = $this->createMock(FrontendInterface::class);
        $cache->method('getIdentifier')
            ->willReturn('Foo_Cache');

        $mockEnvironmentConfiguration = $this->getMockBuilder(EnvironmentConfiguration::class)->setConstructorArgs([
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        ])->getMock();

        $this->backend = new RedisBackend($mockEnvironmentConfiguration, ['redis' => $this->redis]);
        $this->backend->setCache($cache);

        // set this to false manually, since the check in isFrozen leads to null (instead of a boolean)
        // as the exists call is not mocked (and cannot easily be mocked, as it is used for different
        // things.)
        $this->inject($this->backend, 'frozen', false);
    }

    #[Test]
    public function getRetriesOnRedisException(): void
    {
        $backend = $this->createBackendWithoutRetryDelay(['maximumRetries' => 3]);

        $this->redis->expects($this->exactly(3))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new \RedisException('LOADING Redis is loading the dataset in memory')),
                $this->throwException(new \RedisException('LOADING Redis is loading the dataset in memory')),
                'some data'
            );

        self::assertSame('some data', $backend->get('foo'));
        self::assertSame([1, 2], $backend->retryDelays);
    }

    #[Test]
    public function getThrowsLastExceptionIfMaximumRetriesAreExceeded(): void
    {
        $backend = $this->createBackendWithoutRetryDelay(['maximumRetries' => 2]);

        $this->redis->expects($this->exactly(3))
            ->method('get')
            ->willThrowException(new \RedisException('LOADING Redis is loading the dataset in memory'));

        $this->expectException(\RedisException::class);
        try {
            $backend->get('foo');
        } finally {
            self::assertSame([1, 2], $backend->retryDelays);
        }
    }

    #[Test]
    public function getDoesNotRetryIfRetryOnErrorIsDisabled(): void
    {
        $backend = $this->createBackendWithoutRetryDelay(['retryOnError' => false]);

        $this->redis->expects($this->once())
            ->method('get')
            ->willThrowException(new \RedisException('LOADING Redis is loading the dataset in memory'));

        $this->expectException(\RedisException::class);
        $backend->get('foo');
    }

    #[Test]
    public function hasRetriesOnRedisException(): void
    {
        $backend = $this->createBackendWithoutRetryDelay();

        $this->redis->expects($this->exactly(2))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new \RedisException('LOADING Redis is loading the dataset in memory')),
                1
            );

        self::assertTrue($backend->has('foo'));
    }

    #[Test]
    public function setMaximumRetriesRejectsNegativeValues(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->backend->setMaximumRetries(-1);
    }

    /**
     * A backend that records the retry delays instead of sleeping
     */
    private function createBackendWithoutRetryDelay(array $options = []): RedisBackend
    {
        $backend = new class ($this->createMock(EnvironmentConfiguration::class), array_merge(['redis' => $this->redis], $options)) extends RedisBackend {
            public array $retryDelays = [];

            protected function waitBeforeRetry(int $retry): void
            {
                $this->retryDelays[] = $retry;
            }
        };
        $cache = $this->createMock(FrontendInterface::class);
        $cache->method('getIdentifier')->willReturn('Foo_Cache');
        $backend->setCache($cache);
        return $backend;
    }

    #[Test]
    public function findIdentifiersByTagInvokesRedis(): void
    {
        $this->redis->expects($this->once())
            ->method('sMembers')
            ->with('d41d8cd98f00b204e9800998ecf8427e:Foo_Cache:tag:some_tag')
            ->willReturn(['entry_1', 'entry_2']);

        $this->assertEquals(['entry_1', 'entry_2'], $this->backend->findIdentifiersByTag('some_tag'));
    }

    #[Test]
    public function freezeInvokesRedis(): void
    {
        $this->redis->method('exec')
            ->willReturn($this->redis);

        $this->redis->expects($this->once())
            ->method('keys')
            ->willReturn(['entry_1', 'entry_2']);

        $this->redis->expects($this->exactly(2))
            ->method('persist');

        $this->redis->expects($this->once())
            ->method('set')
            ->with('d41d8cd98f00b204e9800998ecf8427e:Foo_Cache:frozen', true);

        $this->backend->freeze();
    }

    #[Test]
    public function setUsesDefaultLifetimeIfNotProvided(): void
    {
        $defaultLifetime = random_int(1, 9999);
        $this->backend->setDefaultLifetime($defaultLifetime);
        $expected = ['ex' => $defaultLifetime];

        $this->redis->method('multi')
            ->willReturn($this->redis);

        $this->redis->expects($this->once())
            ->method('set')
            ->with($this->anything(), $this->anything(), $expected)
            ->willReturn($this->redis);

        $this->backend->set('foo', 'bar');
    }

    #[Test]
    public function setUsesProvidedLifetime(): void
    {
        $defaultLifetime = 3600;
        $this->backend->setDefaultLifetime($defaultLifetime);
        $expected = ['ex' => 1600];

        $this->redis->method('multi')
            ->willReturn($this->redis);

        $this->redis->expects($this->once())
            ->method('set')
            ->with($this->anything(), $this->anything(), $expected)
            ->willReturn($this->redis);

        $this->backend->set('foo', 'bar', [], 1600);
    }

    #[Test]
    public function setAddsEntryToRedis(): void
    {
        $this->redis->method('multi')
            ->willReturn($this->redis);

        $this->redis->expects($this->once())
            ->method('set')
            ->with('d41d8cd98f00b204e9800998ecf8427e:Foo_Cache:entry:entry_1', 'foo')
            ->willReturn($this->redis);

        $this->backend->set('entry_1', 'foo');
    }

    #[Test]
    public function getInvokesRedis(): void
    {
        $this->redis->expects($this->once())
            ->method('get')
            ->with('d41d8cd98f00b204e9800998ecf8427e:Foo_Cache:entry:foo')
            ->willReturn('bar');

        self::assertEquals('bar', $this->backend->get('foo'));
    }

    #[Test]
    public function hasInvokesRedis(): void
    {
        $this->redis->expects($this->once())
            ->method('exists')
            ->with('d41d8cd98f00b204e9800998ecf8427e:Foo_Cache:entry:foo')
            ->willReturn(true);

        self::assertEquals(true, $this->backend->has('foo'));
    }

    /**
     * @param string $method
     */
    #[DataProvider('writingOperationsProvider')]
    #[Test]
    public function writingOperationsThrowAnExceptionIfCacheIsFrozen(string $method): void
    {
        $this->expectException(\RuntimeException::class);
        $this->inject($this->backend, 'frozen', null);
        $this->redis->expects($this->once())
            ->method('exists')
            ->with('d41d8cd98f00b204e9800998ecf8427e:Foo_Cache:frozen')
            ->willReturn(true);

        $this->backend->$method('foo', 'bar');
    }

    /**
     * @param string $method
     */
    #[DataProvider('batchWritingOperationsProvider')]
    #[Test]
    public function batchWritingOperationsThrowAnExceptionIfCacheIsFrozen(string $method): void
    {
        $this->expectException(\RuntimeException::class);
        $this->inject($this->backend, 'frozen', null);
        $this->redis->expects($this->once())
            ->method('exists')
            ->with('d41d8cd98f00b204e9800998ecf8427e:Foo_Cache:frozen')
            ->willReturn(true);

        $this->backend->$method(['foo', 'bar']);
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function writingOperationsProvider(): \Iterator
    {
        yield ['set'];
        yield ['remove'];
        yield ['flushByTag'];
        yield ['freeze'];
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function batchWritingOperationsProvider(): \Iterator
    {
        yield ['flushByTags'];
    }
}
