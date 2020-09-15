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

class ProxyClassTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function proxyClassesDataProvider()
    {
        return [
            [
                'originalClassName' => '\Acme\Namespace\ClassName',
                'originalClassAnnotations' => [],
                'originalClassDocumentation' => '',
                'originalClassConstants' => [['name' => 'TEST_CONSTANT', 'value' => '1']],
                'expectedProxyCode' => "namespace \\Acme\\Namespace;\n" .
                    "\n" .
                    "use Doctrine\\ORM\\Mapping as ORM;\n" .
                    "use Neos\\Flow\\Annotations as Flow;\n" .
                    "\n" .
                    'class ClassName extends ClassName' . Compiler::ORIGINAL_CLASSNAME_SUFFIX . " implements \\Neos\\Flow\\ObjectManagement\\Proxy\\ProxyInterface {\n\n" .
                    "    const TEST_CONSTANT = 1;\n\n" .
                    '}',
            ],
            [
                'originalClassName' => '\ClassWithoutNamespace',
                'originalClassAnnotations' => [],
                'originalClassDocumentation' => '',
                'originalClassConstants' => [['name' => 'TEST_CONSTANT', 'value' => '1']],
                'expectedProxyCode' =>
                    "use Doctrine\\ORM\\Mapping as ORM;\n" .
                    "use Neos\\Flow\\Annotations as Flow;\n" .
                    "\n" .
                    'class ClassWithoutNamespace extends ClassWithoutNamespace' . Compiler::ORIGINAL_CLASSNAME_SUFFIX . " implements \\Neos\\Flow\\ObjectManagement\\Proxy\\ProxyInterface {\n\n" .
                    "    const TEST_CONSTANT = 1;\n\n" .
                    '}',
            ],
            [
                'originalClassName' => 'ClassWithoutNamespace',
                'originalClassAnnotations' => [],
                'originalClassDocumentation' => '',
                'originalClassConstants' => [['name' => 'TEST_CONSTANT', 'value' => '1']],
                'expectedProxyCode' =>
                    "use Doctrine\\ORM\\Mapping as ORM;\n" .
                    "use Neos\\Flow\\Annotations as Flow;\n" .
                    "\n" .
                    'class ClassWithoutNamespace extends ClassWithoutNamespace' . Compiler::ORIGINAL_CLASSNAME_SUFFIX . " implements \\Neos\\Flow\\ObjectManagement\\Proxy\\ProxyInterface {\n\n" .
                    "    const TEST_CONSTANT = 1;\n\n" .
                    '}',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider proxyClassesDataProvider
     */
    public function renderWorksAsExpected($originalClassName, $originalClassAnnotations, $originalClassDocumentation, $originalClassConstants, $expectedProxyCode)
    {
        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->any())->method('isClassAbstract')->will($this->returnValue(strpos($expectedProxyCode, 'abstract ') !== false));
        $mockReflectionService->expects($this->any())->method('getClassAnnotations')->will($this->returnValue($originalClassAnnotations));

        $mockProxyClass = $this->getAccessibleMock(ProxyClass::class, ['buildClassDocumentation'], [$originalClassName], '', true);
        $mockProxyClass->expects($this->any())->method('buildClassDocumentation')->will($this->returnValue($originalClassDocumentation));
        $mockProxyClass->injectReflectionService($mockReflectionService);
        foreach ($originalClassConstants as $originalClassConstant) {
            $mockProxyClass->addConstant($originalClassConstant['name'], $originalClassConstant['value']);
        }

        $proxyCode = $mockProxyClass->render();

        $this->assertEquals($expectedProxyCode, $proxyCode);
    }
}
