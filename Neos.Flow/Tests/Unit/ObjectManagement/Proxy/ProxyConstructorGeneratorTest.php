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
use Neos\Flow\ObjectManagement\DependencyInjection\ProxyClassBuilder;
use Neos\Flow\ObjectManagement\Proxy\Compiler;
use Neos\Flow\ObjectManagement\Proxy\ProxyConstructorGenerator;
use Neos\Flow\Tests\Unit\ObjectManagement\Fixture\ClassWithoutConstructor;
use Neos\Flow\Tests\Unit\ObjectManagement\Fixture\ClassWithPrivateConstructor;
use Neos\Flow\Tests\Unit\ObjectManagement\Fixture\ClassWithProtectedConstructor;
use Neos\Flow\Tests\Unit\ObjectManagement\Fixture\ClassWithPublicConstructor;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

require_once(__DIR__ . '/../Fixture/ClassWithoutConstructor.php');
require_once(__DIR__ . '/../Fixture/ClassWithPrivateConstructor.php');
require_once(__DIR__ . '/../Fixture/ClassWithProtectedConstructor.php');
require_once(__DIR__ . '/../Fixture/ClassWithPublicConstructor.php');

/**
 * Test cases for the Proxy Constructor Generator
 */
class ProxyConstructorGeneratorTest extends UnitTestCase
{
    /**
     * @param class-string $className
     */
    private function createProxyConstructorFor(string $className): ProxyConstructorGenerator
    {
        return ProxyConstructorGenerator::fromReflection(new MethodReflection($className, '__construct'));
    }

    #[Test]
    public function fromReflectionAlwaysCopiesTheOriginalParameters(): void
    {
        $proxyConstructor = $this->createProxyConstructorFor(ClassWithPublicConstructor::class);
        $parameters = $proxyConstructor->getParameters();

        self::assertSame(['name', 'number', 'options'], array_keys($parameters));
        self::assertSame('string', $parameters['name']->getType());
        self::assertSame('int', $parameters['number']->getType());
        self::assertSame('42', (string)$parameters['number']->getDefaultValue());
    }

    /**
     * Constructor property promotion must not be repeated in the proxy constructor, because
     * the promoted properties are already declared by the original class.
     */
    #[Test]
    public function promotedParametersOfTheOriginalConstructorAreRenderedAsPlainParameters(): void
    {
        $proxyConstructor = $this->createProxyConstructorFor(ClassWithPublicConstructor::class);
        $proxyConstructor->addPreParentCallCode('$foo = 1;');

        $code = $proxyConstructor->generate();

        self::assertStringContainsString('public function __construct(string $name, int $number = 42, ?\ArrayObject $options = null)', $code);
        self::assertStringNotContainsString('public readonly string $name', $code);
        self::assertStringNotContainsString('protected int $number', $code);
    }

    #[Test]
    public function fromReflectionAlwaysCreatesAMethodNamedConstruct(): void
    {
        self::assertSame('__construct', $this->createProxyConstructorFor(ClassWithPublicConstructor::class)->getName());
        self::assertSame('__construct', (new ProxyConstructorGenerator('someOtherName'))->getName());
    }

    #[Test]
    public function fromReflectionRemembersTheOriginalClassName(): void
    {
        self::assertSame(
            ClassWithPublicConstructor::class,
            $this->createProxyConstructorFor(ClassWithPublicConstructor::class)->getFullOriginalClassName()
        );
    }

    #[Test]
    public function fromReflectionCopiesTheFinalFlag(): void
    {
        self::assertTrue($this->createProxyConstructorFor(ClassWithProtectedConstructor::class)->isFinal());
        self::assertFalse($this->createProxyConstructorFor(ClassWithPublicConstructor::class)->isFinal());
    }

    #[Test]
    public function theOriginalVisibilityIsOnlyRememberedForNonPublicConstructors(): void
    {
        self::assertNull($this->createProxyConstructorFor(ClassWithPublicConstructor::class)->getOriginalVisibility());
        self::assertSame(ProxyConstructorGenerator::VISIBILITY_PRIVATE, $this->createProxyConstructorFor(ClassWithPrivateConstructor::class)->getOriginalVisibility());
        self::assertSame(ProxyConstructorGenerator::VISIBILITY_PROTECTED, $this->createProxyConstructorFor(ClassWithProtectedConstructor::class)->getOriginalVisibility());
    }

    public static function classesWithNonPublicConstructorsDataProvider(): array
    {
        return [
            'private' => ['className' => ClassWithPrivateConstructor::class, 'expectedVisibilityString' => 'private'],
            'protected' => ['className' => ClassWithProtectedConstructor::class, 'expectedVisibilityString' => 'protected'],
        ];
    }

    #[DataProvider('classesWithNonPublicConstructorsDataProvider')]
    #[Test]
    public function theProxyConstructorIsAlwaysPublic(string $className, string $expectedVisibilityString): void
    {
        self::assertSame(ProxyConstructorGenerator::VISIBILITY_PUBLIC, $this->createProxyConstructorFor($className)->getVisibility());
    }

    #[DataProvider('classesWithNonPublicConstructorsDataProvider')]
    #[Test]
    public function visibilityEnforcementCodeIsRenderedForNonPublicConstructors(string $className, string $expectedVisibilityString): void
    {
        $proxyConstructor = $this->createProxyConstructorFor($className);
        $proxyConstructor->addPreParentCallCode('$foo = 1;');

        $bodyCode = $proxyConstructor->renderBodyCode();

        self::assertStringContainsString('$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);', $bodyCode);
        self::assertStringContainsString('is_subclass_of($backtrace[1][\'class\'], \\' . $className . '::class)', $bodyCode);
        self::assertStringContainsString('$backtrace[1][\'class\'] !== \'' . $className . Compiler::ORIGINAL_CLASSNAME_SUFFIX . '\'', $bodyCode);
        self::assertStringContainsString('throw new \\Error(\'Call to ' . $expectedVisibilityString . ' ' . $className . '::__construct() from invalid context\', 1686153840);', $bodyCode);
        self::assertLessThan(strpos($bodyCode, '$foo = 1;'), strpos($bodyCode, '$backtrace = debug_backtrace'));
    }

    #[Test]
    public function noVisibilityEnforcementCodeIsRenderedForPublicConstructors(): void
    {
        $proxyConstructor = $this->createProxyConstructorFor(ClassWithPublicConstructor::class);
        $proxyConstructor->addPreParentCallCode('$foo = 1;');

        self::assertStringNotContainsString('debug_backtrace', $proxyConstructor->renderBodyCode());
    }

    #[Test]
    public function theParentConstructorIsCalledWithAllOriginalArguments(): void
    {
        $proxyConstructor = $this->createProxyConstructorFor(ClassWithPublicConstructor::class);
        $proxyConstructor->addPreParentCallCode('$foo = 1;');

        self::assertStringContainsString('parent::__construct(...func_get_args());', $proxyConstructor->renderBodyCode());
    }

    #[Test]
    public function preAndPostParentCallCodeIsRenderedAroundTheParentCall(): void
    {
        $proxyConstructor = $this->createProxyConstructorFor(ClassWithPublicConstructor::class);
        $proxyConstructor->addPreParentCallCode('$foo = 1;');
        $proxyConstructor->addPostParentCallCode('$bar = 2;');

        $bodyCode = $proxyConstructor->renderBodyCode();

        self::assertLessThan(strpos($bodyCode, 'parent::__construct'), strpos($bodyCode, '$foo = 1;'));
        self::assertLessThan(strpos($bodyCode, '$bar = 2;'), strpos($bodyCode, 'parent::__construct'));
    }

    #[Test]
    public function noResultIsAssignedOrReturnedByTheProxyConstructor(): void
    {
        $proxyConstructor = $this->createProxyConstructorFor(ClassWithPublicConstructor::class);
        $proxyConstructor->addPreParentCallCode('$foo = 1;');
        $proxyConstructor->addPostParentCallCode('$bar = 2;');

        $bodyCode = $proxyConstructor->renderBodyCode();

        self::assertStringNotContainsString('$result', $bodyCode);
        self::assertStringNotContainsString('return', $bodyCode);
    }

    #[Test]
    public function renderBodyCodeReturnsAnEmptyStringIfNoCodeWasAdded(): void
    {
        $proxyConstructor = $this->createProxyConstructorFor(ClassWithPrivateConstructor::class);

        self::assertSame('', $proxyConstructor->renderBodyCode());
        self::assertFalse($proxyConstructor->willBeRendered());
        self::assertSame('', $proxyConstructor->generate());
    }

    #[Test]
    public function neitherParentCallNorVisibilityEnforcementCodeIsRenderedIfTheOriginalClassHasNoConstructor(): void
    {
        $proxyConstructor = new ProxyConstructorGenerator();
        $proxyConstructor->setFullOriginalClassName(ClassWithoutConstructor::class);
        $proxyConstructor->addPreParentCallCode('$foo = 1;');

        $bodyCode = $proxyConstructor->renderBodyCode();

        self::assertStringNotContainsString('parent::__construct', $bodyCode);
        self::assertStringNotContainsString('debug_backtrace', $bodyCode);
        self::assertStringContainsString('$foo = 1;', $bodyCode);
    }

    #[Test]
    public function noParentCallIsRenderedIfTheOriginalClassIsUnknown(): void
    {
        $proxyConstructor = new ProxyConstructorGenerator();
        $proxyConstructor->addPreParentCallCode('$foo = 1;');

        self::assertStringNotContainsString('parent::__construct', $proxyConstructor->renderBodyCode());
    }

    #[Test]
    public function copyMethodSignatureThrowsAnException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionCode(1685078402);

        ProxyConstructorGenerator::copyMethodSignature(new MethodReflection(ClassWithPublicConstructor::class, '__construct'));
    }

    #[Test]
    public function theDocBlockMarksTheConstructorAsAutogeneratedAndKeepsTheOriginalDocumentation(): void
    {
        $docBlock = $this->createProxyConstructorFor(ClassWithPublicConstructor::class)->getDocBlock();

        self::assertNotNull($docBlock);
        self::assertStringContainsString(ProxyClassBuilder::AUTOGENERATED_PROXY_METHOD_COMMENT, $docBlock->getSourceContent());
        self::assertStringContainsString('Some documentation for this constructor', $docBlock->getSourceContent());
    }

    #[Test]
    public function theDocBlockMarksTheConstructorAsAutogeneratedIfTheOriginalHasNoDocumentation(): void
    {
        $docBlock = $this->createProxyConstructorFor(ClassWithPrivateConstructor::class)->getDocBlock();

        self::assertNotNull($docBlock);
        self::assertStringContainsString(ProxyClassBuilder::AUTOGENERATED_PROXY_METHOD_COMMENT, $docBlock->getSourceContent());
    }

    #[Test]
    public function aConstructorCreatedWithoutReflectionIsAlsoMarkedAsAutogenerated(): void
    {
        $docBlock = (new ProxyConstructorGenerator())->getDocBlock();

        self::assertNotNull($docBlock);
        self::assertStringContainsString(ProxyClassBuilder::AUTOGENERATED_PROXY_METHOD_COMMENT, $docBlock->getSourceContent());
    }
}
