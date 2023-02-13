<?php
declare(strict_types=1);
namespace Neos\Cache\Tests\Unit\Backend;

include_once(__DIR__ . '/../../BaseTestCase.php');

use Neos\Cache\Backend\FileBackend;
use Neos\Cache\Backend\IterableMultiBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Cache\Tests\BaseTestCase;

class IterableMultiBackendTest extends BaseTestCase
{
    /**
     * @test
     * @throws \Throwable
     */
    public function allowsToIterateOverCacheEntries(): void
    {
        $multiBackend = new IterableMultiBackend(
            $this->getEnvironmentConfiguration(),
            [
                'debug' => true,
                'backendConfigurations' => [
                    [
                        'backend' => FileBackend::class,
                        'backendOptions' => []
                    ]
                ]
            ]
        );

        $cache = new VariableFrontend('TestCache', $multiBackend);
        $multiBackend->setCache($cache);

        $cache->set('foo1', 'bar1');
        $cache->set('foo2', 'bar2');


        $iterator = $cache->getIterator();
        $iterator->rewind();

        self::assertSame('foo1', $iterator->key());
        self::assertSame('bar1', $iterator->current());

        $iterator->next();

        self::assertSame('foo2', $iterator->key());
        self::assertSame('bar2', $iterator->current());
    }

    public function getEnvironmentConfiguration(): EnvironmentConfiguration
    {
        return new EnvironmentConfiguration(
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        );
    }
}
