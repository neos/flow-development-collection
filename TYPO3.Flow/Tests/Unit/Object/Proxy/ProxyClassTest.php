<?php
namespace TYPO3\Flow\Tests\Unit\Object\Proxy;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\Object;

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
                'expectedProxyCode' => "namespace \Acme\Namespace;\n".
            "\n" .
            "use Doctrine\\ORM\\Mapping as ORM;\n" .
            "use TYPO3\\Flow\\Annotations as Flow;\n" .
            "\n" .
            "class ClassName extends ClassName". Object\Proxy\Compiler::ORIGINAL_CLASSNAME_SUFFIX." implements \TYPO3\Flow\Object\Proxy\ProxyInterface {\n\n" .
            "	const TEST_CONSTANT = 1;\n\n".
            "}",
            ],
            [
                'originalClassName' => '\ClassWithoutNamespace',
                'originalClassAnnotations' => [],
                'originalClassDocumentation' => '',
                'originalClassConstants' => [['name' => 'TEST_CONSTANT', 'value' => '1']],
                'expectedProxyCode' =>
            "use Doctrine\\ORM\\Mapping as ORM;\n" .
            "use TYPO3\\Flow\\Annotations as Flow;\n" .
            "\n" .
            "class ClassWithoutNamespace extends ClassWithoutNamespace". Object\Proxy\Compiler::ORIGINAL_CLASSNAME_SUFFIX." implements \TYPO3\Flow\Object\Proxy\ProxyInterface {\n\n" .
            "	const TEST_CONSTANT = 1;\n\n".
            "}",
            ],
            [
                'originalClassName' => 'ClassWithoutNamespace',
                'originalClassAnnotations' => [],
                'originalClassDocumentation' => '',
                'originalClassConstants' => [['name' => 'TEST_CONSTANT', 'value' => '1']],
                'expectedProxyCode' =>
            "use Doctrine\\ORM\\Mapping as ORM;\n" .
            "use TYPO3\\Flow\\Annotations as Flow;\n" .
            "\n" .
            "class ClassWithoutNamespace extends ClassWithoutNamespace". Object\Proxy\Compiler::ORIGINAL_CLASSNAME_SUFFIX." implements \TYPO3\Flow\Object\Proxy\ProxyInterface {\n\n" .
            "	const TEST_CONSTANT = 1;\n\n".
            "}",
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

        $mockProxyClass = $this->getAccessibleMock(Object\Proxy\ProxyClass::class, ['buildClassDocumentation'], [$originalClassName], '', true);
        $mockProxyClass->expects($this->any())->method('buildClassDocumentation')->will($this->returnValue($originalClassDocumentation));
        $mockProxyClass->injectReflectionService($mockReflectionService);
        foreach ($originalClassConstants as $originalClassConstant) {
            $mockProxyClass->addConstant($originalClassConstant['name'], $originalClassConstant['value']);
        }

        $proxyCode = $mockProxyClass->render();

        $this->assertEquals($expectedProxyCode, $proxyCode);
    }
}
