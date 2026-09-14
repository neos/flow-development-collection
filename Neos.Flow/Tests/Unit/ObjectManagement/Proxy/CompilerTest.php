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

require_once(__DIR__ . '/../Fixture/FooBarAnnotation.php');

use Neos\Flow\Annotations\Entity;
use Neos\Flow\Annotations\Inject;
use Neos\Flow\Annotations\Scope;
use Neos\Flow\Annotations\Session;
use Neos\Flow\Annotations\Signal;
use Neos\Flow\Annotations\Validate;
use Neos\Flow\ObjectManagement\Proxy\Compiler;
use Neos\Flow\Tests\Unit\ObjectManagement\Fixture\ExampleEnum;
use Neos\Flow\Tests\Unit\ObjectManagement\Fixture\FooBarAnnotation;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

require_once(__DIR__ . '/../Fixture/ExampleEnum.php');
require_once(__DIR__ . '/../Fixture/FooBarAnnotation.php');

/**
 * Test cases for the Proxy Compiler
 */
final class CompilerTest extends UnitTestCase
{
    /**
     * @var Compiler|MockObject
     */
    protected $compiler;

    protected function setUp(): void
    {
        $this->compiler = $this->getAccessibleMock(Compiler::class, [], [], '', false);
    }

    public static function annotationsAndStrings(): \Iterator
    {
        $sessionWithAutoStart = new Session();
        $sessionWithAutoStart->autoStart = true;
        yield [
            new Signal(),
            '@\Neos\Flow\Annotations\Signal'
        ];
        yield [
            new Scope('singleton'),
            '@\Neos\Flow\Annotations\Scope("singleton")'
        ];
        yield [
            new FooBarAnnotation(),
            '@\Neos\Flow\Tests\Unit\ObjectManagement\Fixture\FooBarAnnotation(1.2)'
        ];
        yield [
            new FooBarAnnotation(new FooBarAnnotation()),
            '@\Neos\Flow\Tests\Unit\ObjectManagement\Fixture\FooBarAnnotation(@\Neos\Flow\Tests\Unit\ObjectManagement\Fixture\FooBarAnnotation(1.2))'
        ];
        yield [
            $sessionWithAutoStart,
            '@\Neos\Flow\Annotations\Session(autoStart=true)'
        ];
        yield [
            new Session(),
            '@\Neos\Flow\Annotations\Session'
        ];
        yield [
            new Validate('foo1', 'bar1'),
            '@\Neos\Flow\Annotations\Validate(type="bar1", argumentName="foo1")'
        ];
        yield [
            new Validate(null, 'bar1', ['minimum' => 2]),
            '@\Neos\Flow\Annotations\Validate(type="bar1", options={ "minimum"=2 })'
        ];
        yield [
            new Validate(null, 'bar1', ['foo' => ['bar' => 'baz']]),
            '@\Neos\Flow\Annotations\Validate(type="bar1", options={ "foo"={ "bar"="baz" } })'
        ];
        yield [
            new Validate(null, 'bar1', ['foo' => 'hubbabubba', 'bar' => true]),
            '@\Neos\Flow\Annotations\Validate(type="bar1", options={ "foo"="hubbabubba", "bar"=true })'
        ];
        yield [
            new Validate(null, 'bar1', [new Inject()]),
            '@\Neos\Flow\Annotations\Validate(type="bar1", options={ @\Neos\Flow\Annotations\Inject })'
        ];
        yield [
            new Validate(null, 'bar1', [new Validate(null, 'bar1', ['foo' => 'hubbabubba'])]),
            '@\Neos\Flow\Annotations\Validate(type="bar1", options={ @\Neos\Flow\Annotations\Validate(type="bar1", options={ "foo"="hubbabubba" }) })'
        ];
    }

    #[DataProvider('annotationsAndStrings')]
    #[Test]
    public function renderAnnotationRendersCorrectly($annotation, $expectedString): void
    {
        self::assertEquals($expectedString, Compiler::renderAnnotation($annotation));
    }

    public static function attributes(): \Generator
    {
        yield 'L ' . __LINE__ . ': simple' => [
            'name' => 'Neos\Flow\Annotations\Entity',
            'arguments' => [],
            'expectedResult' => '#[\Neos\Flow\Annotations\Entity]'
        ];
        yield 'L ' . __LINE__ . ': with single argument' => [
            'name' => 'Neos\Flow\Annotations\Scope',
            'arguments' => ['singleton'],
            'expectedResult' => '#[\Neos\Flow\Annotations\Scope(\'singleton\')]'
        ];
        yield 'L ' . __LINE__ . ': with named arguments' => [
            'name' => 'Neos\Flow\Annotations\Inject',
            'arguments' => ['name' => 'SomeClass', 'lazy' => false],
            'expectedResult' => '#[\Neos\Flow\Annotations\Inject(name: \'SomeClass\', lazy: false)]'
        ];
        yield 'L ' . __LINE__ . ': with nested attribute' => [
            'name' => 'Neos\Flow\Annotations\Example',
            'arguments' => [
                'attribute' => new Entity(),
                'enum' => ExampleEnum::Foo,
            ],
            'expectedResult' => '#[\Neos\Flow\Annotations\Example(attribute: new \Neos\Flow\Annotations\Entity(), enum: \\Neos\Flow\Tests\Unit\ObjectManagement\Fixture\ExampleEnum::Foo)]'
        ];
        yield 'L ' . __LINE__ . ': nested array arguments' => [
            'name' => 'Neos\Flow\Annotations\Example',
            'arguments' => [
                'nestedArrayOfAttributes' => [new Entity(), new Scope('singleton'), new Inject(name: "SomeClass", lazy: false)]
            ],
            'expectedResult' => '#[\Neos\Flow\Annotations\Example(nestedArrayOfAttributes: [new \Neos\Flow\Annotations\Entity(), new \Neos\Flow\Annotations\Scope(value: \'singleton\'), new \Neos\Flow\Annotations\Inject(name: \'SomeClass\', lazy: false)])]'
        ];
        yield 'L ' . __LINE__ . ': nested named array arguments' => [
            'name' => 'Neos\Flow\Annotations\Example',
            'arguments' => [
                'nestedNamedArray' => ['foo' => new Entity(), 'bar' => new Scope('singleton'), 'baz' => new Inject(name: "SomeClass", lazy: false)]
            ],
            'expectedResult' => '#[\Neos\Flow\Annotations\Example(nestedNamedArray: [\'foo\' => new \Neos\Flow\Annotations\Entity(), \'bar\' => new \Neos\Flow\Annotations\Scope(value: \'singleton\'), \'baz\' => new \Neos\Flow\Annotations\Inject(name: \'SomeClass\', lazy: false)])]'
        ];
    }

    #[DataProvider('attributes')]
    #[Test]
    public function renderAttributesRendersCorrectly(string $name, array $arguments, string $expectedResult): void
    {
        $attribute = $this->createMock(\ReflectionAttribute::class);
        $attribute->method('getName')->willReturn($name);
        $attribute->method('getArguments')->willReturn($arguments);
        $this->assertSame($expectedResult, Compiler::renderAttribute($attribute));
    }

    public static function stripOpeningPhpTagCorrectlyStripsPhpTagDataProvider(): \Iterator
    {
        // no (valid) php file
        yield ['classCode' => "", 'expectedResult' => ""];
        yield ['classCode' => "Not\nPHP code\n", 'expectedResult' => "Not\nPHP code\n"];
        // PHP files with only one line
        yield ['classCode' => "<?php just one line", 'expectedResult' => " just one line"];
        yield ['classCode' => "<?php another <?php tag", 'expectedResult' => " another <?php tag"];
        yield ['classCode' => "  <?php  space before and after tag", 'expectedResult' => "  space before and after tag"];
        // PHP files with more lines
        yield ['classCode' => "<?php\nsecond line", 'expectedResult' => "\nsecond line"];
        yield ['classCode' => "  <?php\nsecond line", 'expectedResult' => "\nsecond line"];
        yield ['classCode' => "<?php  first line\nsecond line", 'expectedResult' => "  first line\nsecond line"];
        yield ['classCode' => "<?php\nsecond line with another <?php tag", 'expectedResult' => "\nsecond line with another <?php tag"];
        yield ['classCode' => "\n<?php\nempty line before php tag", 'expectedResult' => "\nempty line before php tag"];
        yield ['classCode' => "<?php\nsecond line\n<?php\nthird line", 'expectedResult' => "\nsecond line\n<?php\nthird line"];
    }

    #[DataProvider('stripOpeningPhpTagCorrectlyStripsPhpTagDataProvider')]
    #[Test]
    public function stripOpeningPhpTagCorrectlyStripsPhpTagTests($classCode, $expectedResult): void
    {
        $actualResult = $this->compiler->_call('stripOpeningPhpTag', $classCode);
        self::assertSame($expectedResult, $actualResult);
    }

    public static function classCodeExamples(): \Iterator
    {
        yield [
            <<<PHP
                <?php
                class EasyClassName extends \ArrayIterator
                {
                }
                PHP,
            <<<PHP
                <?php
                class EasyClassName_Original extends \ArrayIterator
                {
                }
                PHP,
            '/Some/Path/Classes/EasyClassName.php'
        ];
        yield [
            <<<PHP
                <?php
                /*
                class foo
                */
                /*
                class bar */class /* oddly placed comment for class */ ClassWithKeywordsInClassBody //class quux
                {
                    public function doSomething()
                    {
                    }
                }
                PHP,
            <<<PHP
                <?php
                /*
                class foo
                */
                /*
                class bar */class /* oddly placed comment for class */ ClassWithKeywordsInClassBody_Original //class quux
                {
                    public function doSomething()
                    {
                    }
                }
                PHP,
            '/Some/Path/Classes/ClassWithKeywordsInClassBody.php'
        ];
        yield [
            <<<PHP
                <?php
                class /* oddly placed comment for class */
                ClassWithClassNameOnNextLine //class quux
                {
                }
                PHP,
            <<<PHP
                <?php
                class /* oddly placed comment for class */
                ClassWithClassNameOnNextLine_Original //class quux
                {
                }
                PHP,
            '/Some/Path/Classes/ClassWithClassNameOnNextLine.php'
        ];
        yield [
            <<<PHP
                <?php
                final class SomeFinalClass // this is final, is it?
                {
                }
                PHP,
            <<<PHP
                <?php
                class SomeFinalClass_Original // this is final, is it?
                {
                }
                PHP,
            '/Some/Path/Classes/SomeFinalClass.php'
        ];
        yield [
            <<<PHP
                <?php
                class ClassImplementingInterfaceWithSameName implements ClassImplementingInterfaceWithSameNameInterface
                {
                }
                PHP,
            <<<PHP
                <?php
                class ClassImplementingInterfaceWithSameName_Original implements ClassImplementingInterfaceWithSameNameInterface
                {
                }
                PHP,
            '/Some/Path/Classes/ClassImplementingInterfaceWithSameName.php'
        ];
    }

    #[DataProvider('classCodeExamples')]
    #[Test]
    public function replaceClassNameAppendsSuffixToOriginalClassName(string $originalClassCode, string $expectedClassCode, string $pathAndFilename): void
    {
        $actualClassCode = $this->compiler->_call('replaceClassName', $originalClassCode, $pathAndFilename);
        self::assertSame($expectedClassCode, $actualClassCode);
    }

    public static function finalMethodDeclarationExamples(): array
    {
        return [
            'final public function' => [
                '    final public function someMethod(): string',
                '    /*final*/ public function someMethod(): string'
            ],
            'public final function' => [
                '    public final function someMethod(): string',
                '    public /*final*/ function someMethod(): string'
            ],
            'final public static function' => [
                '    final public static function someMethod(): string',
                '    /*final*/ public static function someMethod(): string'
            ],
            'public static final function' => [
                '    public static final function someMethod(): string',
                '    public static /*final*/ function someMethod(): string'
            ],
            'final protected static function' => [
                '    final protected static function someMethod(): string',
                '    /*final*/ protected static function someMethod(): string'
            ],
            'single character method name' => [
                '    final public function m(): string',
                '    /*final*/ public function m(): string'
            ],
            'method returning by reference' => [
                '    final public function &someMethod(): string',
                '    /*final*/ public function &someMethod(): string'
            ],
        ];
    }

    #[Test]
    #[DataProvider('finalMethodDeclarationExamples')]
    public function commentOutFinalKeywordForMethodsHandlesAllModifierCombinations(string $methodDeclaration, string $expectedMethodDeclaration): void
    {
        $classCode = "class SomeClass_Original\n{\n" . $methodDeclaration . "\n    {\n    }\n}\n";
        $proxyClassCode = "class SomeClass extends SomeClass_Original\n{\n" . $methodDeclaration . "\n    {\n    }\n}\n";

        $actualClassCode = $this->compiler->_call('commentOutFinalKeywordForMethods', $classCode, $proxyClassCode);
        self::assertStringContainsString($expectedMethodDeclaration, $actualClassCode);
    }

    #[Test]
    public function commentOutFinalKeywordForMethodsLeavesMethodsAloneWhichAreNotPartOfTheProxyClass(): void
    {
        $classCode = "class SomeClass_Original\n{\n    final public static function someMethod(): string\n    {\n    }\n}\n";
        $proxyClassCode = "class SomeClass extends SomeClass_Original\n{\n    public function someOtherMethod(): string\n    {\n    }\n}\n";

        $actualClassCode = $this->compiler->_call('commentOutFinalKeywordForMethods', $classCode, $proxyClassCode);
        self::assertSame($classCode, $actualClassCode);
    }
}
