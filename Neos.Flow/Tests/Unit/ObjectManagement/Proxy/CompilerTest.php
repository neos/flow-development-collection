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

use Neos\Flow\Annotations\Inject;
use Neos\Flow\Annotations\Scope;
use Neos\Flow\Annotations\Session;
use Neos\Flow\Annotations\Signal;
use Neos\Flow\Annotations\Validate;
use Neos\Flow\ObjectManagement\Proxy\Compiler;
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
