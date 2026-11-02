<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Utility;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Utility\PhpAnalyzer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the PhpAnalyzer utility class
 */
final class PhpAnalyzerTest extends UnitTestCase
{
    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function sampleClasses(): \Iterator
    {
        yield ['phpCode' => '', 'namespace' => null, 'className' => null, 'fqn' => null];
        yield ['phpCode' => 'namespace Foo;', 'namespace' => null, 'className' => null, 'fqn' => null];
        yield ['phpCode' => 'class Bar {}', 'namespace' => null, 'className' => null, 'fqn' => null];
        yield ['phpCode' => '<?php class {}', 'namespace' => null, 'className' => null, 'fqn' => null];
        yield ['phpCode' => '<?php class SomeClass {}', 'namespace' => null, 'className' => 'SomeClass', 'fqn' => 'SomeClass'];
        yield ['phpCode' => '<?php namespace Foo\Bar; class SomeClass {}', 'namespace' => 'Foo\Bar', 'className' => 'SomeClass', 'fqn' => 'Foo\Bar\SomeClass'];
        yield ['phpCode' => '<?php namespace \Foo\Bar\; class SomeClass {}', 'namespace' => 'Foo\Bar', 'className' => 'SomeClass', 'fqn' => 'Foo\Bar\SomeClass'];
        yield ['phpCode' => '<?php ' . chr(13) . '  namespace  Foo\Bar {' . chr(13) . '	 class    SomeClass {}', 'namespace' => 'Foo\Bar', 'className' => 'SomeClass', 'fqn' => 'Foo\Bar\SomeClass'];
        yield ['phpCode' => 'foo <?php class SomeClass', 'namespace' => null, 'className' => 'SomeClass', 'fqn' => 'SomeClass'];
    }

    /**
     * @param string $phpCode
     * @param string $namespace
     */
    #[DataProvider('sampleClasses')]
    #[Test]
    public function extractNamespaceTests($phpCode, $namespace, $className, $fqn)
    {
        $phpAnalyzer = new PhpAnalyzer($phpCode);
        self::assertSame($namespace, $phpAnalyzer->extractNamespace());
    }

    /**
     * @param string $phpCode
     * @param string $namespace
     * @param string $className
     */
    #[DataProvider('sampleClasses')]
    #[Test]
    public function extractClassNameTests($phpCode, $namespace, $className, $fqn)
    {
        $phpAnalyzer = new PhpAnalyzer($phpCode);
        self::assertSame($className, $phpAnalyzer->extractClassName());
    }

    /**
     * @param string $phpCode
     * @param string $namespace
     * @param string $className
     * @param string $fqn
     */
    #[DataProvider('sampleClasses')]
    #[Test]
    public function extractFullyQualifiedClassNameTests($phpCode, $namespace, $className, $fqn)
    {
        $phpAnalyzer = new PhpAnalyzer($phpCode);
        self::assertSame($fqn, $phpAnalyzer->extractFullyQualifiedClassName());
    }
}
