<?php

namespace Neos\Flow\Tests\Unit\ObjectManagement\Proxy;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Laminas\Code\Reflection\MethodReflection;
use Neos\Flow\ObjectManagement\Proxy\ProxyMethodGenerator;
use Neos\Flow\Tests\Unit\ObjectManagement\Fixture\ClassWithVariadicMethods;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for the handling of variadic parameters in generated proxy methods
 */
class ProxyMethodVariadicsTest extends UnitTestCase
{
    public static function variadicMethodsDataProvider(): array
    {
        return [
            'no variadic parameter' => [
                'noVariadics',
                '$first, $second',
                'string $first, int $second = 42',
            ],
            'variadic parameter without type' => [
                'sum',
                '...$numbers',
                '... $numbers',
            ],
            'variadic parameter with type' => [
                'sumTyped',
                '...$numbers',
                'int ... $numbers',
            ],
            'regular and variadic parameters mixed' => [
                'concatenate',
                '$separator, ...$parts',
                'string $separator, string ... $parts',
            ],
            'by-reference and variadic parameters mixed' => [
                'collect',
                '$collection, ...$items',
                'array &$collection, string ... $items',
            ],
        ];
    }

    #[DataProvider('variadicMethodsDataProvider')]
    #[Test]
    public function buildMethodParametersCodeUnpacksVariadicParameters(string $methodName, string $expectedArgumentsCode, string $expectedSignatureCode): void
    {
        $proxyMethod = ProxyMethodGenerator::fromReflection(new MethodReflection(ClassWithVariadicMethods::class, $methodName));

        self::assertSame($expectedArgumentsCode, $proxyMethod->buildMethodParametersCode(ClassWithVariadicMethods::class, $methodName, false));
        self::assertSame($expectedSignatureCode, $proxyMethod->buildMethodParametersCode(ClassWithVariadicMethods::class, $methodName, true));
    }

    #[DataProvider('variadicMethodsDataProvider')]
    #[Test]
    public function renderBodyCodeUnpacksVariadicParametersInTheParentCall(string $methodName, string $expectedArgumentsCode): void
    {
        $proxyMethod = ProxyMethodGenerator::fromReflection(new MethodReflection(ClassWithVariadicMethods::class, $methodName));
        $proxyMethod->addPreParentCallCode('$before = true;');
        $proxyMethod->addPostParentCallCode('$after = true;');

        self::assertStringContainsString('parent::' . $methodName . '(' . $expectedArgumentsCode . ');', $proxyMethod->renderBodyCode());
    }

    #[Test]
    public function renderBodyCodeUnpacksVariadicParametersInTheReturningParentCall(): void
    {
        $proxyMethod = ProxyMethodGenerator::fromReflection(new MethodReflection(ClassWithVariadicMethods::class, 'sumTyped'));
        $proxyMethod->addPreParentCallCode('$before = true;');

        self::assertStringContainsString('return parent::sumTyped(...$numbers);', $proxyMethod->renderBodyCode());
    }
}
