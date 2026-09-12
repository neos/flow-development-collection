<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\ObjectManagement\Configuration;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\Configuration\ConfigurationBuilder;
use Neos\Flow\ObjectManagement\Configuration\ConfigurationParser;
use Neos\Flow\ObjectManagement\Exception;
use Neos\Flow\ObjectManagement\Exception\UnknownClassException;
use Neos\Flow\ObjectManagement\Exception\UnresolvedDependenciesException;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;

/**
 * Testcase for the object configuration builder
 *
 */
final class ConfigurationBuilderTest extends UnitTestCase
{
    #[Test]
    public function privatePropertyAnnotatedForInjectionThrowsException()
    {
        $this->expectException(Exception::class);
        $configurationArray = [];
        $configurationArray['arguments'][1]['setting'] = 'Neos.Foo.Bar';
        $configurationArray['properties']['someProperty']['setting'] = 'Neos.Bar.Baz';

        $configurationBuilder = $this->prepareConfigurationBuilder($this->reflectionServiceMockWithDummyProperty());
        $configurationBuilder->buildObjectConfigurations(['Neos.Flow.Testing' => [__CLASS__]], ['Neos.Flow.Testing' => [__CLASS__ => $configurationArray]]);
    }

    #[Test]
    public function errorOnGetClassMethodsThrowsException()
    {
        $this->expectException(UnknownClassException::class);
        $configurationArray = [];
        $configurationArray['properties']['someProperty']['object']['name'] = 'Foo';
        $configurationArray['properties']['someProperty']['object']['className'] = 'foobar';

        $configurationBuilder = $this->prepareConfigurationBuilder($this->reflectionServiceMock());
        $configurationBuilder->buildObjectConfigurations(['Neos.Flow.Testing' => ['Foo']], ['Neos.Flow.Testing' => [__CLASS__ => $configurationArray]]);
    }

    #[Test]
    public function objectsCreatedByFactoryShouldNotFailOnMissingConstructorArguments()
    {
        $configurationArray = [
            'scope' => 'singleton',
            'factoryObjectName' => 'TestFactory',
        ];

        $configurationBuilder = $this->prepareConfigurationBuilder($this->reflectionServiceMock());

        try {
            $objectConfigurations = $configurationBuilder->buildObjectConfigurations(['Neos.Flow.Testing' => [__CLASS__]], ['Neos.Flow.Testing' => [__CLASS__ => $configurationArray]]);
        } catch (UnresolvedDependenciesException $e) {
            self::fail('Factory created objects should not throw UnresolvedDependenciesException by autowiring constructor arguments');
        }
        self::assertEquals($configurationArray['factoryObjectName'], $objectConfigurations[__CLASS__]->getFactoryObjectName());
    }

    protected function prepareConfigurationBuilder(ReflectionService $reflectionServiceMock): ConfigurationBuilder
    {
        $loggerMock = $this->createMock(LoggerInterface::class);

        $configurationBuilder = new ConfigurationBuilder($reflectionServiceMock, new ConfigurationParser($reflectionServiceMock), $loggerMock);
        return $configurationBuilder;
    }

    protected function reflectionServiceMockWithDummyProperty(): ReflectionService
    {
        $reflectionServiceMock = $this->createMock(ReflectionService::class);
        $reflectionServiceMock
            ->expects(self::once())
            ->method('getPropertyNamesByAnnotation')
            ->with(__CLASS__, Flow\Inject::class)
            ->willReturn(['dummyProperty']);

        $reflectionServiceMock
            ->expects(self::once())
            ->method('isPropertyPrivate')
            ->with(__CLASS__, 'dummyProperty')
            ->willReturn(true);

        return $reflectionServiceMock;
    }

    protected function reflectionServiceMock(): ReflectionService
    {
        $reflectionServiceMock = $this->createMock(ReflectionService::class);
        return $reflectionServiceMock;
    }
}
