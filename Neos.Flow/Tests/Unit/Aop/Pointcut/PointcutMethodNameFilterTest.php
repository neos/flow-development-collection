<?php
namespace Neos\Flow\Tests\Unit\Aop\Pointcut;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Aop;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Testcase for the Pointcut Method Name Filter
 */
class PointcutMethodNameFilterTest extends UnitTestCase
{
    /**
     * @test
     */
    public function matchesRespectsFinalMethodsIfTheirNameMatches()
    {
        $className = 'TestClass' . md5(uniqid(mt_rand(), true));
        eval('
			class ' . $className . ' {
				final public function someFinalMethod() {}
			}'
        );
        /** @var ReflectionService|\PHPUnit_Framework_MockObject_MockObject $mockReflectionService */
        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->any())->method('isMethodFinal')->with($className, 'someFinalMethod')->will($this->returnValue(true));
        $methodNameFilter = new Aop\Pointcut\PointcutMethodNameFilter('someFinalMethod');
        $methodNameFilter->injectReflectionService($mockReflectionService);
        $this->assertTrue($methodNameFilter->matches($className, 'someFinalMethod', $className, 1));
    }

    /**
     * @test
     */
    public function matchesTakesTheVisibilityModifierIntoAccountIfOneWasSpecified()
    {
        $className = 'TestClass' . md5(uniqid(mt_rand(), true));
        eval('
			class ' . $className . ' {
				public function somePublicMethod() {}
				protected function someProtectedMethod() {}
				private function somePrivateMethod() {}
			}'
        );

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->atLeastOnce())->method('isMethodPublic')->will($this->onConsecutiveCalls(true, false, false, true));
        $mockReflectionService->expects($this->atLeastOnce())->method('isMethodProtected')->will($this->onConsecutiveCalls(false, true, false, false));
        $mockReflectionService->expects($this->atLeastOnce())->method('getMethodParameters')->will($this->returnValue([]));

        $methodNameFilter = new Aop\Pointcut\PointcutMethodNameFilter('some.*', 'public');
        $methodNameFilter->injectReflectionService($mockReflectionService);
        $this->assertTrue($methodNameFilter->matches(__CLASS__, 'somePublicMethod', $className, 1));
        $this->assertFalse($methodNameFilter->matches(__CLASS__, 'someProtectedMethod', $className, 1));
        $this->assertFalse($methodNameFilter->matches(__CLASS__, 'somePrivateMethod', $className, 1));
        $this->assertFalse($methodNameFilter->matches(__CLASS__, 'somePublicMethod', null, 1));

        $methodNameFilter = new Aop\Pointcut\PointcutMethodNameFilter('some.*', 'protected');
        $methodNameFilter->injectReflectionService($mockReflectionService);
        $this->assertFalse($methodNameFilter->matches(__CLASS__, 'somePublicMethod', $className, 1));
        $this->assertTrue($methodNameFilter->matches(__CLASS__, 'someProtectedMethod', $className, 1));
        $this->assertFalse($methodNameFilter->matches(__CLASS__, 'somePrivateMethod', $className, 1));
        $this->assertFalse($methodNameFilter->matches(__CLASS__, 'someProtectedMethod', null, 1));
    }

    /**
     * @test
     */
    public function matchesChecksTheAvailablityOfAnArgumentNameIfArgumentConstraintsHaveBeenConfigured()
    {
        $className = 'TestClass' . md5(uniqid(mt_rand(), true));
        eval('
			class ' . $className . " {
				public function somePublicMethod(\$arg1) {}
				public function someOtherPublicMethod(\$arg1, \$arg2 = 'default') {}
				public function someThirdMethod(\$arg1, \$arg2, \$arg3 = 'default') {}
			}"
        );

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->exactly(3))->method('getMethodParameters')->will($this->onConsecutiveCalls(
                ['arg1' => []],
                ['arg1' => [], 'arg2' => []],
                ['arg1' => [], 'arg2' => [], 'arg3' => []]
        ));

        $mockSystemLogger = $this->getMockBuilder(LoggerInterface::class)->setMethods([])->getMock();
        $mockSystemLogger->expects($this->once())->method('notice')->with($this->equalTo(
            'The argument "arg2" declared in pointcut does not exist in method ' . $className . '->somePublicMethod'
        ));

        $argumentConstraints = [
            'arg1' => [
                'operator' => '==',
                'value' => 'someValue'
            ],
            'arg2.some.sub.object' => [
                'operator' => '==',
                'value' => 'someValue'
            ]
        ];

        $methodNameFilter = new Aop\Pointcut\PointcutMethodNameFilter('some.*', null, $argumentConstraints);
        $methodNameFilter->injectReflectionService($mockReflectionService);
        $methodNameFilter->injectLogger($mockSystemLogger);

        $methodNameFilter->matches(__CLASS__, 'somePublicMethod', $className, 1);

        $this->assertTrue($methodNameFilter->matches(__CLASS__, 'someOtherPublicMethod', $className, 1));
        $this->assertTrue($methodNameFilter->matches(__CLASS__, 'someThirdMethod', $className, 1));
    }

    /**
     * @test
     */
    public function getRuntimeEvaluationsReturnsTheMethodArgumentConstraintsDefinitions()
    {
        $argumentConstraints = [
            'arg2' => [
                'operator' => '==',
                'value' => 'someValue'
            ]
        ];

        $expectedRuntimeEvaluations = [
            'methodArgumentConstraints' => $argumentConstraints
        ];

        $methodNameFilter = new Aop\Pointcut\PointcutMethodNameFilter('some.*', null, $argumentConstraints);

        $this->assertEquals($expectedRuntimeEvaluations, $methodNameFilter->getRuntimeEvaluationsDefinition(), 'The argument constraint definitions have not been returned as expected.');
    }
}
