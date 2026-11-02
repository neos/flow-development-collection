<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Mvc\Routing\Dto;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Cache\CacheAwareInterface;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\RouterInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the RouteParameters DTO
 */
final class RouteParametersTest extends UnitTestCase
{
    public static function withParameterThrowsExceptionForInvalidParameterValuesDataProvider(): \Iterator
    {
        yield 'stdClass' => ['parameterValue' => new \stdClass()];
        yield 'RouterInterface mock' => ['parameterValue' => '__mock:' . RouterInterface::class];
        yield 'null' => ['parameterValue' => null];
    }

    #[DataProvider('withParameterThrowsExceptionForInvalidParameterValuesDataProvider')]
    #[Test]
    public function withParameterThrowsExceptionForInvalidParameterValues($parameterValue)
    {
        if (is_string($parameterValue) && str_starts_with($parameterValue, '__mock:')) {
            $parameterValue = $this->createMock(substr($parameterValue, 7));
        }
        $this->expectException(\InvalidArgumentException::class);
        RouteParameters::createEmpty()->withParameter('someParameter', $parameterValue);
    }

    public static function withParameterAcceptsValidParameterValuesDataProvider(): \Iterator
    {
        yield ['parameterValue' => 'string'];
        yield ['parameterValue' => 123];
        yield ['parameterValue' => 123.45];
        yield ['parameterValue' => true];
        yield ['parameterValue' => false];
        yield ['parameterValue' => '__stub:' . CacheAwareInterface::class];
    }

    #[DataProvider('withParameterAcceptsValidParameterValuesDataProvider')]
    #[Test]
    public function withParameterAcceptsValidParameterValues($parameterValue)
    {
        if (is_string($parameterValue) && str_starts_with($parameterValue, '__stub:')) {
            $parameterValue = $this->createStub(substr($parameterValue, 7));
        }
        RouteParameters::createEmpty()->withParameter('someParameter', $parameterValue);
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function withParameterDoesNotMutateTheObject()
    {
        $originalParameters = RouteParameters::createEmpty();
        $originalParameters->withParameter('someParameter', 'someValue');
        self::assertFalse($originalParameters->has('someParameter'));
    }

    #[Test]
    public function withParameterReturnsANewInstanceWithTheGivenParameter()
    {
        $originalParameters = RouteParameters::createEmpty()->withParameter('someParameter', 'someValue');
        self::assertSame('someValue', $originalParameters->getValue('someParameter'));
    }

    #[Test]
    public function withParameterOverridesAnyPreviousParameters()
    {
        $originalParameters = RouteParameters::createEmpty()->withParameter('someParameter', 'someValue');
        $originalParameters = $originalParameters->withParameter('someParameter', 'overriddenValue');
        self::assertSame('overriddenValue', $originalParameters->getValue('someParameter'));
    }
}
