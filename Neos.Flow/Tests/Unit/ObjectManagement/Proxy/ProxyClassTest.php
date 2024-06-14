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

use Neos\Flow\ObjectManagement\Proxy\Compiler;
use Neos\Flow\ObjectManagement\Proxy\ProxyClass;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ProxyClassTest extends UnitTestCase
{
    public function proxyClassesDataProvider(): array
    {
        return [
            [
                'originalClassName' => '\Acme\Namespace\ClassName',
                'originalClassAnnotations' => [],
                'originalClassDocumentation' => '',
                'originalClassConstants' => [['name' => 'TEST_CONSTANT', 'value' => '1']],
                'expectedProxyCode' =>
                    'class ClassName extends ClassName' . Compiler::ORIGINAL_CLASSNAME_SUFFIX . " implements \\Neos\\Flow\\ObjectManagement\\Proxy\\ProxyInterface {\n\n" .
                    "    const TEST_CONSTANT = 1;" . PHP_EOL . PHP_EOL.
                    '}',
            ],
            [
                'originalClassName' => '\ClassWithoutNamespace',
                'originalClassAnnotations' => [],
                'originalClassDocumentation' => '',
                'originalClassConstants' => [['name' => 'TEST_CONSTANT', 'value' => '1']],
                'expectedProxyCode' =>
                    'class ClassWithoutNamespace extends ClassWithoutNamespace' . Compiler::ORIGINAL_CLASSNAME_SUFFIX . " implements \\Neos\\Flow\\ObjectManagement\\Proxy\\ProxyInterface {\n\n" .
                    "    const TEST_CONSTANT = 1;" . PHP_EOL . PHP_EOL.
                    '}',
            ],
            [
                'originalClassName' => 'ClassWithoutNamespace',
                'originalClassAnnotations' => [],
                'originalClassDocumentation' => '',
                'originalClassConstants' => [['name' => 'TEST_CONSTANT', 'value' => '1']],
                'expectedProxyCode' =>
                    'class ClassWithoutNamespace extends ClassWithoutNamespace' . Compiler::ORIGINAL_CLASSNAME_SUFFIX . " implements \\Neos\\Flow\\ObjectManagement\\Proxy\\ProxyInterface {\n\n" .
                    "    const TEST_CONSTANT = 1;" . PHP_EOL . PHP_EOL.
                    '}',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider proxyClassesDataProvider
     * @throws
     */
    public function renderWorksAsExpected($originalClassName, $originalClassAnnotations, $originalClassDocumentation, $originalClassConstants, $expectedProxyCode): void
    {
        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
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
