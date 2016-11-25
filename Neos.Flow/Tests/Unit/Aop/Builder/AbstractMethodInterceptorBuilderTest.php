<?php
namespace Neos\Flow\Tests\Unit\Aop\Builder;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Aop\Builder\AbstractMethodInterceptorBuilder;
use Neos\Flow\Aop\Builder\AdvicedConstructorInterceptorBuilder;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the Abstract Method Interceptor Builder
 *
 */
class AbstractMethodInterceptorBuilderTest extends UnitTestCase
{
    /**
     * @test
     */
    public function buildMethodArgumentsArrayCodeRendersCodeForPassingParametersToTheJoinPoint()
    {
        $className = 'TestClass' . md5(uniqid(mt_rand(), true));
        eval('
			class ' . $className . ' {
				public function foo($arg1, array $arg2, \ArrayObject $arg3, &$arg4, $arg5= "foo", $arg6 = TRUE) {}
			}
		');
        $methodParameters = [
            'arg1' => [
                'position' => 0,
                'byReference' => false,
                'array' => false,
                'optional' => false,
                'allowsNull' => true
            ],
            'arg2' => [
                'position' => 1,
                'byReference' => false,
                'array' => true,
                'optional' => false,
                'allowsNull' => true
            ],
            'arg3' => [
                'position' => 2,
                'byReference' => false,
                'array' => false,
                'optional' => false,
                'allowsNull' => true
            ],
            'arg4' => [
                'position' => 3,
                'byReference' => true,
                'array' => false,
                'optional' => false,
                'allowsNull' => true
            ],
            'arg5' => [
                'position' => 4,
                'byReference' => false,
                'array' => false,
                'optional' => true,
                'allowsNull' => true
            ],
            'arg6' => [
                'position' => 5,
                'byReference' => false,
                'array' => false,
                'optional' => true,
                'allowsNull' => true
            ],
        ];

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->any())->method('getMethodParameters')->with($className, 'foo')->will($this->returnValue($methodParameters));

        $expectedCode = "
                \$methodArguments = [];

                \$methodArguments['arg1'] = \$arg1;
                \$methodArguments['arg2'] = \$arg2;
                \$methodArguments['arg3'] = \$arg3;
                \$methodArguments['arg4'] = &\$arg4;
                \$methodArguments['arg5'] = \$arg5;
                \$methodArguments['arg6'] = \$arg6;
            ";

        $builder = $this->getAccessibleMock(AbstractMethodInterceptorBuilder::class, ['build'], [], '', false);
        $builder->injectReflectionService($mockReflectionService);

        $actualCode = $builder->_call('buildMethodArgumentsArrayCode', $className, 'foo');
        $this->assertSame($expectedCode, $actualCode);
    }

    /**
     * @test
     */
    public function buildMethodArgumentsArrayCodeReturnsAnEmptyStringIfTheClassNameIsNULL()
    {
        $builder = $this->getAccessibleMock(AbstractMethodInterceptorBuilder::class, ['build'], [], '', false);

        $actualCode = $builder->_call('buildMethodArgumentsArrayCode', null, 'foo');
        $this->assertSame('', $actualCode);
    }

    /**
     * @test
     */
    public function buildSavedConstructorParametersCodeReturnsTheCorrectParametersCode()
    {
        $className = 'TestClass' . md5(uniqid(mt_rand(), true));
        eval('
			class ' . $className . ' {
				public function __construct($arg1, array $arg2, \ArrayObject $arg3, $arg4= "__construct", $arg5 = TRUE) {}
			}
		');
        $methodParameters = [
            'arg1' => [
                'position' => 0,
                'byReference' => false,
                'array' => false,
                'optional' => false,
                'allowsNull' => true
            ],
            'arg2' => [
                'position' => 1,
                'byReference' => false,
                'array' => true,
                'optional' => false,
                'allowsNull' => true
            ],
            'arg3' => [
                'position' => 2,
                'byReference' => false,
                'array' => false,
                'optional' => false,
                'allowsNull' => true
            ],
            'arg4' => [
                'position' => 3,
                'byReference' => false,
                'array' => false,
                'optional' => true,
                'allowsNull' => true
            ],
            'arg5' => [
                'position' => 4,
                'byReference' => false,
                'array' => false,
                'optional' => true,
                'allowsNull' => true
            ],
        ];

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->any())->method('getMethodParameters')->with($className, '__construct')->will($this->returnValue($methodParameters));

        $builder = $this->getAccessibleMock(AdvicedConstructorInterceptorBuilder::class, ['dummy'], [], '', false);
        $builder->injectReflectionService($mockReflectionService);

        $expectedCode = '$this->Flow_Aop_Proxy_originalConstructorArguments[\'arg1\'], $this->Flow_Aop_Proxy_originalConstructorArguments[\'arg2\'], $this->Flow_Aop_Proxy_originalConstructorArguments[\'arg3\'], $this->Flow_Aop_Proxy_originalConstructorArguments[\'arg4\'], $this->Flow_Aop_Proxy_originalConstructorArguments[\'arg5\']';
        $actualCode = $builder->_call('buildSavedConstructorParametersCode', $className);

        $this->assertSame($expectedCode, $actualCode);
    }
}
