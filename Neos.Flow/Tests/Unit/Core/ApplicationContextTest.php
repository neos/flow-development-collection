<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Core;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Exception;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the ApplicationContext class
 */
final class ApplicationContextTest extends UnitTestCase
{
    /**
     * Data provider with allowed contexts.
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function allowedContexts(): \Iterator
    {
        yield ['Production'];
        yield ['Testing'];
        yield ['Development'];
        yield ['Development/MyLocalComputer'];
        yield ['Development/MyLocalComputer/Foo'];
        yield ['Production/SpecialDeployment/LiveSystem'];
    }

    #[DataProvider('allowedContexts')]
    #[Test]
    public function contextStringCanBeSetInConstructorAndReadByCallingToString($allowedContext): void
    {
        $context = new ApplicationContext($allowedContext);
        self::assertSame($allowedContext, (string)$context);
    }

    /**
     * Data provider with forbidden contexts.
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function forbiddenContexts(): \Iterator
    {
        yield ['MySpecialContexz'];
        yield ['Testing123'];
        yield ['DevelopmentStuff'];
        yield ['DevelopmentStuff/FooBar'];
    }

    #[DataProvider('forbiddenContexts')]
    #[Test]
    public function constructorThrowsExceptionIfMainContextIsForbidden($forbiddenContext): void
    {
        $this->expectException(Exception::class);
        new ApplicationContext($forbiddenContext);
    }

    /**
     * Data provider with expected is*() values for various contexts.
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function isMethods(): \Iterator
    {
        yield 'Development' => [
            'contextName' => 'Development',
            'isDevelopment' => true,
            'isProduction' => false,
            'isTesting' => false,
            'parentContext' => null
        ];
        yield 'Development/YourSpecialContext' => [
            'contextName' => 'Development/YourSpecialContext',
            'isDevelopment' => true,
            'isProduction' => false,
            'isTesting' => false,
            'parentContext' => 'Development'
        ];
        yield 'Production' => [
            'contextName' => 'Production',
            'isDevelopment' => false,
            'isProduction' => true,
            'isTesting' => false,
            'parentContext' => null
        ];
        yield 'Production/MySpecialContext' => [
            'contextName' => 'Production/MySpecialContext',
            'isDevelopment' => false,
            'isProduction' => true,
            'isTesting' => false,
            'parentContext' => 'Production'
        ];
        yield 'Testing' => [
            'contextName' => 'Testing',
            'isDevelopment' => false,
            'isProduction' => false,
            'isTesting' => true,
            'parentContext' => null
        ];
        yield 'Testing/MySpecialContext' => [
            'contextName' => 'Testing/MySpecialContext',
            'isDevelopment' => false,
            'isProduction' => false,
            'isTesting' => true,
            'parentContext' => 'Testing'
        ];
    }

    #[DataProvider('isMethods')]
    #[Test]
    public function contextMethodsReturnTheCorrectValues($contextName, $isDevelopment, $isProduction, $isTesting, $parentContext): void
    {
        $context = new ApplicationContext($contextName);
        self::assertSame($isDevelopment, $context->isDevelopment());
        self::assertSame($isProduction, $context->isProduction());
        self::assertSame($isTesting, $context->isTesting());
        self::assertSame((string)$parentContext, (string)$context->getParent());
    }

    #[Test]
    public function parentContextIsConnectedRecursively(): void
    {
        $context = new ApplicationContext('Production/Foo/Bar');
        $parentContext = $context->getParent();
        self::assertSame('Production/Foo', (string) $parentContext);

        $rootContext = $parentContext->getParent();
        self::assertSame('Production', (string) $rootContext);
    }

    public static function getHierarchyDataProvider(): \Iterator
    {
        yield ['contextString' => 'Development', 'expectedResult' => ['Development']];
        yield ['contextString' => 'Testing/Staging', 'expectedResult' => ['Testing', 'Testing/Staging']];
        yield ['contextString' => 'Production/Staging/Stage1', 'expectedResult' => ['Production', 'Production/Staging', 'Production/Staging/Stage1']];
    }

    /**
     * @param string $contextString
     * @param array $expectedResult
     */
    #[DataProvider('getHierarchyDataProvider')]
    #[Test]
    public function getHierarchyTest(string $contextString, array $expectedResult): void
    {
        $context = new ApplicationContext($contextString);
        self::assertSame($expectedResult, $context->getHierarchy());
    }
}
