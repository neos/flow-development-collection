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

use Neos\Flow\Aop\Builder\AdvisedMethodInterceptorBuilder;
use Neos\Flow\Aop\Exception;
use Neos\Flow\ObjectManagement\Proxy\Compiler;
use Neos\Flow\ObjectManagement\Proxy\ProxyClass;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Advised Method Interceptor Builder
 */
class AdvisedMethodInterceptorBuilderTest extends UnitTestCase
{
    protected ProxyClass $proxyClass;

    protected function buildInterceptorBuilder(string $targetClassName): AdvisedMethodInterceptorBuilder
    {
        $this->proxyClass = new ProxyClass($targetClassName);

        $mockCompiler = $this->getMockBuilder(Compiler::class)->disableOriginalConstructor()->getMock();
        $mockCompiler->method('getProxyClass')->willReturn($this->proxyClass);

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->method('getMethodParameters')->willReturn([]);

        $builder = new AdvisedMethodInterceptorBuilder();
        $builder->injectCompiler($mockCompiler);
        $builder->injectReflectionService($mockReflectionService);
        return $builder;
    }

    /**
     * A method returning by reference cannot be advised, because the return value travels
     * through the advice chain by value.
     *
     * @see https://github.com/neos/flow-development-collection/issues/3590
     */
    #[Test]
    public function buildThrowsExceptionIfTheAdvisedMethodReturnsByReference(): void
    {
        $targetClassName = 'AdvisedMethodByReferenceFixture' . md5(uniqid((string)mt_rand(), true));
        eval('
            class ' . $targetClassName . ' {
                protected array $items = [];
                public function &itemsReference(): array { return $this->items; }
            }
        ');

        $builder = $this->buildInterceptorBuilder($targetClassName);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(1785837971);
        $this->expectExceptionMessageMatches('/returns by reference/');

        $builder->build('itemsReference', ['itemsReference' => ['declaringClassName' => $targetClassName, 'groupedAdvices' => []]], $targetClassName);
    }

    #[Test]
    public function buildThrowsExceptionIfAMethodIntroducedByAnInterfaceReturnsByReference(): void
    {
        $suffix = md5(uniqid((string)mt_rand(), true));
        $interfaceName = 'AdvisedMethodByReferenceFixtureInterface' . $suffix;
        $targetClassName = 'AdvisedMethodByReferenceIntroductionFixture' . $suffix;
        eval('
            interface ' . $interfaceName . ' {
                public function &itemsReference(): array;
            }
            class ' . $targetClassName . ' {
            }
        ');

        $builder = $this->buildInterceptorBuilder($targetClassName);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(1785837971);

        $builder->build('itemsReference', ['itemsReference' => ['declaringClassName' => $interfaceName, 'groupedAdvices' => []]], $targetClassName);
    }

    #[Test]
    public function buildAddsInterceptorCodeForMethodsReturningByValue(): void
    {
        $targetClassName = 'AdvisedMethodByValueFixture' . md5(uniqid((string)mt_rand(), true));
        eval('
            class ' . $targetClassName . ' {
                protected array $items = [];
                public function items(): array { return $this->items; }
            }
        ');

        $builder = $this->buildInterceptorBuilder($targetClassName);
        $builder->build('items', ['items' => ['declaringClassName' => $targetClassName, 'groupedAdvices' => []]], $targetClassName);

        $bodyCode = $this->proxyClass->getMethod('items')->renderBodyCode();

        self::assertStringContainsString('$this->Flow_Aop_Proxy_methodIsInAdviceMode[\'items\']', $bodyCode);
        self::assertStringContainsString('$result = parent::items();', $bodyCode);
        self::assertStringNotContainsString('&parent::', $bodyCode);
    }
}
