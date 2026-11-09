<?php

declare(strict_types=1);

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
use Neos\Flow\ObjectManagement\Proxy\Compiler;
use Neos\Flow\ObjectManagement\Proxy\ProxyClass;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

final class ProxyClassTest extends UnitTestCase
{
    public static function proxyClassesDataProvider(): \Iterator
    {
        yield [
            'originalClassName' => '\Acme\Namespace\ClassName',
            'originalClassAnnotations' => [],
            'originalClassDocumentation' => '',
            'originalClassConstants' => [['name' => 'TEST_CONSTANT', 'value' => '1']],
            'expectedProxyCode' =>
                'class ClassName extends ClassName' . Compiler::ORIGINAL_CLASSNAME_SUFFIX . " implements \\Neos\\Flow\\ObjectManagement\\Proxy\\ProxyInterface {\n\n" .
                "    const TEST_CONSTANT = 1;" . PHP_EOL . PHP_EOL.
                '}',
        ];
        yield [
            'originalClassName' => '\ClassWithoutNamespace',
            'originalClassAnnotations' => [],
            'originalClassDocumentation' => '',
            'originalClassConstants' => [['name' => 'TEST_CONSTANT', 'value' => '1']],
            'expectedProxyCode' =>
                'class ClassWithoutNamespace extends ClassWithoutNamespace' . Compiler::ORIGINAL_CLASSNAME_SUFFIX . " implements \\Neos\\Flow\\ObjectManagement\\Proxy\\ProxyInterface {\n\n" .
                "    const TEST_CONSTANT = 1;" . PHP_EOL . PHP_EOL.
                '}',
        ];
        yield [
            'originalClassName' => 'ClassWithoutNamespace',
            'originalClassAnnotations' => [],
            'originalClassDocumentation' => '',
            'originalClassConstants' => [['name' => 'TEST_CONSTANT', 'value' => '1']],
            'expectedProxyCode' =>
                'class ClassWithoutNamespace extends ClassWithoutNamespace' . Compiler::ORIGINAL_CLASSNAME_SUFFIX . " implements \\Neos\\Flow\\ObjectManagement\\Proxy\\ProxyInterface {\n\n" .
                "    const TEST_CONSTANT = 1;" . PHP_EOL . PHP_EOL.
                '}',
        ];
    }

    /**
     * @throws
     */
    #[DataProvider('proxyClassesDataProvider')]
    #[Test]
    public function renderWorksAsExpected($originalClassName, $originalClassAnnotations, $originalClassDocumentation, $originalClassConstants, $expectedProxyCode): void
    {
        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->method('isClassAbstract')->willReturn(str_contains($expectedProxyCode, 'abstract '));
        $mockReflectionService->method('getClassAnnotations')->willReturn($originalClassAnnotations);

        $mockProxyClass = $this->getAccessibleMock(ProxyClass::class, ['buildClassDocumentation'], [$originalClassName], '', true);
        /** @var ProxyClass|MockObject $mockProxyClass */
        $mockProxyClass->method('buildClassDocumentation')->willReturn($originalClassDocumentation);
        $mockProxyClass->injectReflectionService($mockReflectionService);
        foreach ($originalClassConstants as $originalClassConstant) {
            $mockProxyClass->addConstant($originalClassConstant['name'], $originalClassConstant['value']);
        }

        $proxyCode = $mockProxyClass->render();

        self::assertEquals($expectedProxyCode, $proxyCode);
    }
}
