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
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test cases for the Proxy Compiler
 */
class CompilerTest extends UnitTestCase
{
    /**
     * @var Compiler|MockObject
     */
    protected $compiler;

    protected function setUp(): void
    {
        $this->compiler = $this->getAccessibleMock(Compiler::class, null);
    }

    public function annotationsAndStrings(): array
    {
        $sessionWithAutoStart = new Session();
        $sessionWithAutoStart->autoStart = true;
        return [
            [
                new Signal(),
                '@\Neos\Flow\Annotations\Signal'
            ],
            [
                new Scope('singleton'),
                '@\Neos\Flow\Annotations\Scope("singleton")'
            ],
            [
                new FooBarAnnotation(),
                '@\Neos\Flow\Tests\Unit\ObjectManagement\Fixture\FooBarAnnotation(1.2)'
            ],
            [
                new FooBarAnnotation(new FooBarAnnotation()),
                '@\Neos\Flow\Tests\Unit\ObjectManagement\Fixture\FooBarAnnotation(@\Neos\Flow\Tests\Unit\ObjectManagement\Fixture\FooBarAnnotation(1.2))'
            ],
            [
                $sessionWithAutoStart,
                '@\Neos\Flow\Annotations\Session(autoStart=true)'
            ],
            [
                new Session(),
                '@\Neos\Flow\Annotations\Session'
            ],
            [
                new Validate('foo1', 'bar1'),
                '@\Neos\Flow\Annotations\Validate(type="bar1", argumentName="foo1")'
            ],
            [
                new Validate(null, 'bar1', ['minimum' => 2]),
                '@\Neos\Flow\Annotations\Validate(type="bar1", options={ "minimum"=2 })'
            ],
            [
                new Validate(null, 'bar1', ['foo' => ['bar' => 'baz']]),
                '@\Neos\Flow\Annotations\Validate(type="bar1", options={ "foo"={ "bar"="baz" } })'
            ],
            [
                new Validate(null, 'bar1', ['foo' => 'hubbabubba', 'bar' => true]),
                '@\Neos\Flow\Annotations\Validate(type="bar1", options={ "foo"="hubbabubba", "bar"=true })'
            ],
            [
                new Validate(null, 'bar1', [new Inject()]),
                '@\Neos\Flow\Annotations\Validate(type="bar1", options={ @\Neos\Flow\Annotations\Inject })'
            ],
            [
                new Validate(null, 'bar1', [new Validate(null, 'bar1', ['foo' => 'hubbabubba'])]),
                '@\Neos\Flow\Annotations\Validate(type="bar1", options={ @\Neos\Flow\Annotations\Validate(type="bar1", options={ "foo"="hubbabubba" }) })'
            ],
        ];
    }

    /**
     * @dataProvider annotationsAndStrings()
     * @test
     */
    public function renderAnnotationRendersCorrectly($annotation, $expectedString): void
    {
        self::assertEquals($expectedString, Compiler::renderAnnotation($annotation));
    }

    public function attributes(): \Generator
    {
        $simple = $this->createMock(\ReflectionAttribute::class);
        $simple->expects($this->any())->method('getName')->willReturn('Neos\Flow\Annotations\Entity');
        $simple->expects($this->any())->method('getArguments')->willReturn([]);

        yield 'L ' . __LINE__ . ': simple' => [
            'attribute' => $simple,
            'expectedResult' => '#[\Neos\Flow\Annotations\Entity]'
        ];

        $singleArgument = $this->createMock(\ReflectionAttribute::class);
        $singleArgument->expects($this->any())->method('getName')->willReturn('Neos\Flow\Annotations\Scope');
        $singleArgument->expects($this->any())->method('getArguments')->willReturn(['singleton']);

        yield 'L ' . __LINE__ . ': with single argument' => [
            'attribute' => $singleArgument,
            'expectedResult' => '#[\Neos\Flow\Annotations\Scope(\'singleton\')]'
        ];

        $namedArguments = $this->createMock(\ReflectionAttribute::class);
        $namedArguments->expects($this->any())->method('getName')->willReturn('Neos\Flow\Annotations\Inject');
        $namedArguments->expects($this->any())->method('getArguments')->willReturn(['name' => 'SomeClass', 'lazy' => false]);

        yield 'L ' . __LINE__ . ': with named arguments' => [
            'attribute' => $namedArguments,
            'expectedResult' => '#[\Neos\Flow\Annotations\Inject(name: \'SomeClass\', lazy: false)]'
        ];

        $nestedAttribute = $this->createMock(\ReflectionAttribute::class);
        $nestedAttribute->expects($this->any())->method('getName')->willReturn('Neos\Flow\Annotations\Example');
        $nestedAttribute->expects($this->any())->method('getArguments')->willReturn([
            'attribute' => new Entity(),
            'enum' => ExampleEnum::Foo,
        ]);

        yield 'L ' . __LINE__ . ': with nested attribute' => [
            'attribute' => $nestedAttribute,
            'expectedResult' => '#[\Neos\Flow\Annotations\Example(attribute: new \Neos\Flow\Annotations\Entity(), enum: \\Neos\Flow\Tests\Unit\ObjectManagement\Fixture\ExampleEnum::Foo)]'
        ];

        $nestedArrayOfAttributes = $this->createMock(\ReflectionAttribute::class);
        $nestedArrayOfAttributes->expects($this->any())->method('getName')->willReturn('Neos\Flow\Annotations\Example');
        $nestedArrayOfAttributes->expects($this->any())->method('getArguments')->willReturn([
            'nestedArrayOfAttributes' => [new Entity(), new Scope('singleton'), new Inject(name: "SomeClass", lazy: false)]
        ]);

        yield 'L ' . __LINE__ . ': nested array arguments' => [
            'attribute' => $nestedArrayOfAttributes,
            'expectedResult' => '#[\Neos\Flow\Annotations\Example(nestedArrayOfAttributes: [new \Neos\Flow\Annotations\Entity(), new \Neos\Flow\Annotations\Scope(value: \'singleton\'), new \Neos\Flow\Annotations\Inject(name: \'SomeClass\', lazy: false)])]'
        ];

        $nestedNamedArrayArgumentAttribute = $this->createMock(\ReflectionAttribute::class);
        $nestedNamedArrayArgumentAttribute->expects($this->any())->method('getName')->willReturn('Neos\Flow\Annotations\Example');
        $nestedNamedArrayArgumentAttribute->expects($this->any())->method('getArguments')->willReturn([
            'nestedNamedArray' => ['foo' => new Entity(), 'bar' => new Scope('singleton'), 'baz' => new Inject(name: "SomeClass", lazy: false)]
        ]);

        yield 'L ' . __LINE__ . ': nested array arguments' => [
            'attribute' => $nestedNamedArrayArgumentAttribute,
            'expectedResult' => '#[\Neos\Flow\Annotations\Example(nestedNamedArray: [\'foo\' => new \Neos\Flow\Annotations\Entity(), \'bar\' => new \Neos\Flow\Annotations\Scope(value: \'singleton\'), \'baz\' => new \Neos\Flow\Annotations\Inject(name: \'SomeClass\', lazy: false)])]'
        ];
    }

    /**
     * @dataProvider attributes()
     * @test
     */
    public function renderAttributesRendersCorrectly(\ReflectionAttribute $attribute, string $expectedResult): void
    {
        $this->assertSame($expectedResult, Compiler::renderAttribute($attribute));
    }

    public function stripOpeningPhpTagCorrectlyStripsPhpTagDataProvider(): array
    {
        return [
            // no (valid) php file
            ['classCode' => "", 'expectedResult' => ""],
            ['classCode' => "Not\nPHP code\n", 'expectedResult' => "Not\nPHP code\n"],

            // PHP files with only one line
            ['classCode' => "<?php just one line", 'expectedResult' => " just one line"],
            ['classCode' => "<?php another <?php tag", 'expectedResult' => " another <?php tag"],
            ['classCode' => "  <?php  space before and after tag", 'expectedResult' => "  space before and after tag"],

            // PHP files with more lines
            ['classCode' => "<?php\nsecond line", 'expectedResult' => "\nsecond line"],
            ['classCode' => "  <?php\nsecond line", 'expectedResult' => "\nsecond line"],
            ['classCode' => "<?php  first line\nsecond line", 'expectedResult' => "  first line\nsecond line"],
            ['classCode' => "<?php\nsecond line with another <?php tag", 'expectedResult' => "\nsecond line with another <?php tag"],
            ['classCode' => "\n<?php\nempty line before php tag", 'expectedResult' => "\nempty line before php tag"],
            ['classCode' => "<?php\nsecond line\n<?php\nthird line", 'expectedResult' => "\nsecond line\n<?php\nthird line"],
        ];
    }

    /**
     * @test
     * @dataProvider stripOpeningPhpTagCorrectlyStripsPhpTagDataProvider()
     */
    public function stripOpeningPhpTagCorrectlyStripsPhpTagTests($classCode, $expectedResult): void
    {
        $actualResult = $this->compiler->_call('stripOpeningPhpTag', $classCode);
        self::assertSame($expectedResult, $actualResult);
    }

    public function classCodeExamples(): array
    {
        return [
            [
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
            ],
            [
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
            ],
            [
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
            ],
            [
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
            ],
            [
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
            ]
        ];
    }

    /**
     * @test
     * @dataProvider classCodeExamples()
     */
    public function replaceClassNameAppendsSuffixToOriginalClassName(string $originalClassCode, string $expectedClassCode, string $pathAndFilename): void
    {
        $actualClassCode = $this->compiler->_call('replaceClassName', $originalClassCode, $pathAndFilename);
        self::assertSame($expectedClassCode, $actualClassCode);
    }
}
