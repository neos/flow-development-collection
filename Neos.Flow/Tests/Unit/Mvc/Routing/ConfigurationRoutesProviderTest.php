<?php
namespace Neos\Flow\Tests\Unit\Mvc\Routing;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Mvc\Routing;
use Neos\Flow\Mvc\Routing\Route;
use Neos\Flow\Mvc\Routing\Routes;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the MVC Web Routing Routes Class
 */
class ConfigurationRoutesProviderTest extends UnitTestCase
{
    /**
     * @test
     */
    public function configurationManagerIsNotCalledInConstructor(): void
    {
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockConfigurationManager = $this->createMock(ConfigurationManager::class);
        $mockConfigurationManager->expects($this->never())->method('getConfiguration');
        $configurationRoutesProvider = new Routing\ConfigurationRoutesProvider($mockConfigurationManager, $mockObjectManager);
        $this->assertInstanceOf(Routing\ConfigurationRoutesProvider::class, $configurationRoutesProvider);
    }

    /**
     * @test
     */
    public function configurationFomConfigurationManagerIsHandled(): void
    {
        $configuration = [
            [
                'name' => 'Route 1',
                'uriPattern' => 'route1/{@package}/{@controller}/{@action}(.{@format})',
                'defaults' => ['@format' => 'html']
            ],
            [
                'name' => 'Route 2',
                'uriPattern' => 'route2/{@package}/{@controller}/{@action}(.{@format})',
                'defaults' => ['@format' => 'html'],
                'appendExceedingArguments' => true,
                'cache' => ['lifetime' => 10000, 'tags' => ['foo', 'bar']]
            ],
        ];

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockConfigurationManager = $this->createMock(ConfigurationManager::class);
        $mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_ROUTES)->willReturn($configuration);

        $expectedRoute1 = new Route();
        $expectedRoute1->setName('Route 1');
        $expectedRoute1->setUriPattern('route1/{@package}/{@controller}/{@action}(.{@format})');
        $expectedRoute1->setDefaults(['@format' => 'html']);

        $expectedRoute2 = new Route();
        $expectedRoute2->setName('Route 2');
        $expectedRoute2->setUriPattern('route2/{@package}/{@controller}/{@action}(.{@format})');
        $expectedRoute2->setDefaults(['@format' => 'html']);
        $expectedRoute2->setCacheLifetime(Routing\Dto\RouteLifetime::fromInt(10000));
        $expectedRoute2->setCacheTags(Routing\Dto\RouteTags::createFromArray(['foo', 'bar']));
        $expectedRoute2->setAppendExceedingArguments(true);

        $expectedRoutes = Routes::create($expectedRoute1, $expectedRoute2);

        $configurationRoutesProvider = new Routing\ConfigurationRoutesProvider($mockConfigurationManager, $mockObjectManager);
        $this->assertEquals($expectedRoutes, $configurationRoutesProvider->getRoutes());
    }

    /**
     * @test
     */
    public function configuredProvidersAreCalledToGenerateSubroutes(): void
    {
        $configuration = [
            [
                'name' => 'Routes provider without options',
                'providerFactory' => 'Vendor\Example\RoutesProvider',
            ],
            [
                'name' => 'Routes provider with options',
                'providerFactory' => 'Vendor\Example\RoutesProviderWithOptions',
                'providerOptions' => ['foo' => 'bar'],
            ],
        ];

        $mockRoutesProvider = $this->createMock(Routing\RoutesProviderInterface::class);
        $mockRoutesProviderWithOptions = $this->createMock(Routing\RoutesProviderInterface::class);

        $mockRoutesProviderFactory = $this->createMock(Routing\RoutesProviderFactoryInterface::class);
        $mockRoutesProviderFactory->expects($this->once())
            ->method('createRoutesProvider')
            ->with([])
            ->willReturn($mockRoutesProvider);

        $mockRoutesProviderWithOptionsFactory = $this->createMock(Routing\RoutesProviderFactoryInterface::class);
        $mockRoutesProviderWithOptionsFactory->expects($this->once())
            ->method('createRoutesProvider')
            ->with(['foo' => 'bar'])
            ->willReturn($mockRoutesProviderWithOptions);

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->exactly(2))->method('get')->willReturnCallback(
            function (string $name) use ($mockRoutesProviderFactory, $mockRoutesProviderWithOptionsFactory) {
                return match ($name) {
                    'Vendor\Example\RoutesProvider' => $mockRoutesProviderFactory,
                    'Vendor\Example\RoutesProviderWithOptions' => $mockRoutesProviderWithOptionsFactory
                };
            }
        );

        $mockConfigurationManager = $this->createMock(ConfigurationManager::class);
        $mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_ROUTES)->willReturn($configuration);

        $expectedRoute1 = new Route();
        $expectedRoute1->setName('Route 1');
        $expectedRoute1->setUriPattern('route1/{@package}/{@controller}/{@action}(.{@format})');
        $expectedRoute1->setDefaults(['@format' => 'html']);

        $expectedRoute2 = new Route();
        $expectedRoute2->setName('Route 2');
        $expectedRoute2->setUriPattern('route2/{@package}/{@controller}/{@action}(.{@format})');

        $mockRoutesProvider->expects($this->once())->method('getRoutes')->willReturn(Routes::create($expectedRoute1));
        $mockRoutesProviderWithOptions->expects($this->once())->method('getRoutes')->willReturn(Routes::create($expectedRoute2));

        $expectedRoutes = Routes::create($expectedRoute1, $expectedRoute2);

        $configurationRoutesProvider = new Routing\ConfigurationRoutesProvider($mockConfigurationManager, $mockObjectManager);
        $this->assertEquals($expectedRoutes, $configurationRoutesProvider->getRoutes());
    }
}
