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

use Neos\Flow\Aop\Advice\BeforeAdvice;
use Neos\Flow\Aop\Advisor;
use Neos\Flow\Aop\AspectContainer;
use Neos\Flow\Aop\Builder\AdvisedConstructorInterceptorBuilder;
use Neos\Flow\Aop\Builder\AdvisedMethodInterceptorBuilder;
use Neos\Flow\Aop\Builder\ClassNameIndex;
use Neos\Flow\Aop\Builder\ProxyClassBuilder;
use Neos\Flow\Aop\Exception;
use Neos\Flow\Aop\Pointcut\Pointcut;
use Neos\Flow\ObjectManagement\CompileTimeObjectManager;
use Neos\Flow\ObjectManagement\Configuration\Configuration as ObjectConfiguration;
use Neos\Flow\ObjectManagement\Proxy\Compiler;
use Neos\Flow\ObjectManagement\Proxy\ProxyClass;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the AOP Proxy Class Builder
 */
class ProxyClassBuilderTest extends UnitTestCase
{
    protected ProxyClass $proxyClass;

    protected ReflectionService $mockReflectionService;

    protected CompileTimeObjectManager $mockObjectManager;

    /**
     * Creates a proxy class builder which is wired up with mocks and knows nothing about
     * session scoped classes.
     */
    protected function buildProxyClassBuilder(string $targetClassName): ProxyClassBuilder
    {
        $this->proxyClass = new ProxyClass($targetClassName);

        $mockCompiler = $this->getMockBuilder(Compiler::class)->disableOriginalConstructor()->getMock();
        $mockCompiler->method('getProxyClass')->willReturn($this->proxyClass);

        $this->mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $this->mockReflectionService->method('isMethodStatic')->willReturn(false);
        $this->mockReflectionService->method('hasMethod')->willReturn(false);

        $this->mockObjectManager = $this->getMockBuilder(CompileTimeObjectManager::class)->disableOriginalConstructor()->getMock();
        $this->mockObjectManager->method('getClassNamesByScope')->willReturn([]);

        $proxyClassBuilder = new ProxyClassBuilder();
        $proxyClassBuilder->injectCompiler($mockCompiler);
        $proxyClassBuilder->injectReflectionService($this->mockReflectionService);
        $proxyClassBuilder->injectObjectManager($this->mockObjectManager);
        $proxyClassBuilder->injectAdvisedConstructorInterceptorBuilder($this->getMockBuilder(AdvisedConstructorInterceptorBuilder::class)->disableOriginalConstructor()->getMock());
        $proxyClassBuilder->injectAdvisedMethodInterceptorBuilder($this->getMockBuilder(AdvisedMethodInterceptorBuilder::class)->disableOriginalConstructor()->getMock());

        return $proxyClassBuilder;
    }

    /**
     * Creates an aspect container with a single before advice which matches the given method
     * of the given target class.
     *
     * @return array<string, AspectContainer>
     */
    protected function buildAspectContainers(string $targetClassName, ?string $matchingMethodName): array
    {
        $mockPointcut = $this->getMockBuilder(Pointcut::class)->disableOriginalConstructor()->getMock();
        $mockPointcut->method('reduceTargetClassNames')->willReturn(new ClassNameIndex([$targetClassName => true]));
        $mockPointcut->method('getRuntimeEvaluationsClosureCode')->willReturn('NULL');
        $mockPointcut->method('matches')->willReturnCallback(
            static fn (string $className, ?string $methodName): bool => $methodName === $matchingMethodName
        );

        $aspectContainer = new AspectContainer('Some\Aspect');
        $aspectContainer->addAdvisor(new Advisor(new BeforeAdvice('Some\Aspect', 'someAdvice'), $mockPointcut));
        $aspectContainer->reduceTargetClassNames(new ClassNameIndex([$targetClassName => true]));

        return ['Some\Aspect' => $aspectContainer];
    }

    /**
     * Advices cannot be woven into readonly classes, because the generated proxy code needs
     * mutable state for the advice chains and the re-entrance flags.
     *
     * @see https://github.com/neos/flow-development-collection/issues/3591
     */
    #[Test]
    public function buildProxyClassThrowsExceptionIfAdvicesWouldBeWovenIntoAReadonlyClass(): void
    {
        $targetClassName = 'ReadonlyAdvisedFixture' . md5(uniqid((string)mt_rand(), true));
        eval('final readonly class ' . $targetClassName . ' { public function greet(): string { return "hello"; } }');

        $proxyClassBuilder = $this->buildProxyClassBuilder($targetClassName);
        $this->mockReflectionService->method('isClassReadonly')->willReturn(true);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(1787734304);
        $this->expectExceptionMessageMatches('/requires mutable state which readonly classes do not allow/');

        $proxyClassBuilder->buildProxyClass($targetClassName, $this->buildAspectContainers($targetClassName, 'greet'));
    }

    /**
     * A session scoped class implicitly pulls in Flow's lazy loading aspect, therefore readonly
     * classes cannot be session scoped. That special case deserves a more specific message.
     */
    #[Test]
    public function buildProxyClassThrowsSpecificExceptionForSessionScopedReadonlyClasses(): void
    {
        $targetClassName = 'ReadonlySessionScopedFixture' . md5(uniqid((string)mt_rand(), true));
        eval('final readonly class ' . $targetClassName . ' { public function greet(): string { return "hello"; } }');

        $proxyClassBuilder = $this->buildProxyClassBuilder($targetClassName);
        $this->mockReflectionService->method('isClassReadonly')->willReturn(true);

        $mockObjectManager = $this->getMockBuilder(CompileTimeObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->method('getClassNamesByScope')->willReturnCallback(
            static fn (int $scope): array => $scope === ObjectConfiguration::SCOPE_SESSION ? [$targetClassName] : []
        );
        $proxyClassBuilder->injectObjectManager($mockObjectManager);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(1787734303);
        $this->expectExceptionMessageMatches('/readonly classes cannot be session-scoped/');

        $proxyClassBuilder->buildProxyClass($targetClassName, $this->buildAspectContainers($targetClassName, 'greet'));
    }

    /**
     * Readonly classes which are not targeted by any advice must still compile as before.
     */
    #[Test]
    public function buildProxyClassReturnsFalseForReadonlyClassesWithoutMatchingAdvices(): void
    {
        $targetClassName = 'ReadonlyUnadvisedFixture' . md5(uniqid((string)mt_rand(), true));
        eval('final readonly class ' . $targetClassName . ' { public function greet(): string { return "hello"; } }');

        $proxyClassBuilder = $this->buildProxyClassBuilder($targetClassName);
        $this->mockReflectionService->method('isClassReadonly')->willReturn(true);

        self::assertFalse($proxyClassBuilder->buildProxyClass($targetClassName, $this->buildAspectContainers($targetClassName, null)));
    }

    /**
     * Positive control: non-readonly classes are still woven.
     */
    #[Test]
    public function buildProxyClassStillBuildsAdvicesForNonReadonlyClasses(): void
    {
        $targetClassName = 'MutableAdvisedFixture' . md5(uniqid((string)mt_rand(), true));
        eval('class ' . $targetClassName . ' { public function greet(): string { return "hello"; } }');

        $proxyClassBuilder = $this->buildProxyClassBuilder($targetClassName);
        $this->mockReflectionService->method('isClassReadonly')->willReturn(false);

        self::assertTrue($proxyClassBuilder->buildProxyClass($targetClassName, $this->buildAspectContainers($targetClassName, 'greet')));
        self::assertStringContainsString('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', $this->proxyClass->getMethod('Flow_Aop_Proxy_buildMethodsAndAdvicesArray')->renderBodyCode());
    }
}
