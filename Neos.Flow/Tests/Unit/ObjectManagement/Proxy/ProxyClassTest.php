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
use Neos\Flow\Tests\Unit\ObjectManagement\Fixture\ClassImplementingInterfaceWithConstructor;
use Neos\Flow\Tests\UnitTestCase;

class ProxyClassTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function proxyClassesDataProvider()
    {
        require_once(__DIR__ . '/../Fixture/ClassWithoutNamespace.php');

        return [
            [
                'originalClassName' => ClassImplementingInterfaceWithConstructor::class,
                'originalClassAnnotations' => [],
                'originalClassDocumentation' => '',
                'originalClassConstants' => [['name' => 'TEST_CONSTANT', 'value' => '1']],
                'expectedProxyCode' =>
                    "class ClassImplementingInterfaceWithConstructor extends ClassImplementingInterfaceWithConstructor" . Compiler::ORIGINAL_CLASSNAME_SUFFIX .
                    " implements \\Neos\\Flow\\ObjectManagement\\Proxy\\ProxyInterface {\n\n" .
                    "    const TEST_CONSTANT = 1;\n\n" .
                    "}\n\n"

            ],
            [
                'originalClassName' => '\ClassWithoutNamespace',
                'originalClassAnnotations' => [],
                'originalClassDocumentation' => '',
                'originalClassConstants' => [['name' => 'TEST_CONSTANT', 'value' => '1']],
                'expectedProxyCode' =>
                    'class ClassWithoutNamespace extends ClassWithoutNamespace' . Compiler::ORIGINAL_CLASSNAME_SUFFIX . " implements \\Neos\\Flow\\ObjectManagement\\Proxy\\ProxyInterface {\n\n" .
                    "    const TEST_CONSTANT = 1;\n\n" .
                    "}\n\n" .
                    "class ClassWithoutNamespace_LazyProxy extends ClassWithoutNamespace implements \Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy\n" .
                    "{\n" .
                    "    use \Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxyTrait;\n\n" .
                    "    public function __call(string \$methodName, array \$arguments)\n" .
                    "    {\n" .
                    "        [\$methodName, \$arguments] = func_get_args();\n" .
                    "            return \$this->_activateDependency()->\$methodName(...\$arguments);\n" .
                    "    }\n" .
                    "}\n"
            ],
            [
                'originalClassName' => 'ClassWithoutNamespace',
                'originalClassAnnotations' => [],
                'originalClassDocumentation' => '',
                'originalClassConstants' => [['name' => 'TEST_CONSTANT', 'value' => '1']],
                'expectedProxyCode' =>
                    'class ClassWithoutNamespace extends ClassWithoutNamespace' . Compiler::ORIGINAL_CLASSNAME_SUFFIX . " implements \\Neos\\Flow\\ObjectManagement\\Proxy\\ProxyInterface {\n\n" .
                    "    const TEST_CONSTANT = 1;\n\n" .
                    "}\n\n" .
                    "class ClassWithoutNamespace_LazyProxy extends ClassWithoutNamespace implements \Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy\n" .
                    "{\n" .
                    "    use \Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxyTrait;\n\n" .
                    "    public function __call(string \$methodName, array \$arguments)\n" .
                    "    {\n" .
                    "        [\$methodName, \$arguments] = func_get_args();\n" .
                    "            return \$this->_activateDependency()->\$methodName(...\$arguments);\n" .
                    "    }\n" .
                    "}\n"
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
        $mockReflectionService->expects(self::any())->method('isClassAbstract')->will(self::returnValue(strpos($expectedProxyCode, 'abstract ') !== false));
        $mockReflectionService->expects(self::any())->method('getClassAnnotations')->will(self::returnValue($originalClassAnnotations));

        $mockProxyClass = $this->getAccessibleMock(ProxyClass::class, ['buildClassDocumentation'], [$originalClassName], '', true);
        $mockProxyClass->expects(self::any())->method('buildClassDocumentation')->will(self::returnValue($originalClassDocumentation));
        $mockProxyClass->injectReflectionService($mockReflectionService);
        foreach ($originalClassConstants as $originalClassConstant) {
            $mockProxyClass->addConstant($originalClassConstant['name'], $originalClassConstant['value']);
        }

        $proxyCode = $mockProxyClass->render();

        self::assertEquals($expectedProxyCode, $proxyCode);
    }
}
