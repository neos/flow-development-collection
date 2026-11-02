<?php

declare(strict_types=1);

namespace Neos\Cache\Tests\Unit;

include_once(__DIR__ . '/../BaseTestCase.php');

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Backend\NullBackend;
use Neos\Cache\CacheFactory;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Frontend\FrontendInterface;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Cache\Tests\BaseTestCase;
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Utility\Environment;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the abstract cache frontend
 *
 */
final class CacheFactoryTest extends BaseTestCase
{
    /**
     * @var Environment
     */
    protected $mockEnvironment;

    /**
     * @var EnvironmentConfiguration
     */
    protected $mockEnvironmentConfiguration;

    /**
     * Creates the mocked filesystem used in the tests
     */
    protected function setUp(): void
    {
        vfsStream::setup('Foo');

        $this->mockEnvironment = $this->createMock(Environment::class);
        $this->mockEnvironment->method('getPathToTemporaryDirectory')->willReturn(('vfs://Foo/'));
        $this->mockEnvironment->method('getMaximumPathLength')->willReturn((1024));
        $this->mockEnvironment->method('getContext')->willReturn((new ApplicationContext('Testing')));

        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->onlyMethods(['registerCache', 'isCachePersistent'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockCacheManager->method('isCachePersistent')->willReturn((false));

        $this->mockEnvironmentConfiguration = $this->getMockBuilder(EnvironmentConfiguration::class)
            ->onlyMethods([])
            ->setConstructorArgs([
                __DIR__ . '~Testing',
                'vfs://Foo/',
                255
            ])
            ->getMock();
    }

    #[Test]
    public function createReturnCacheFrontend(): void
    {
        $subject = new CacheFactory($this->mockEnvironmentConfiguration);

        $cache = $subject->create('Some_Cache', VariableFrontend::class, NullBackend::class);
        Assert::assertInstanceOf(FrontendInterface::class, $cache);
        // test that the cache can actually be resolved and is not a hollow proxy
        $cache->getBackend();
    }
}
