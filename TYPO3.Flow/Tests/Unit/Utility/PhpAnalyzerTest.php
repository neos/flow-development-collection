<?php
namespace TYPO3\Flow\Tests\Unit\Utility;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\Utility\PhpAnalyzer;

/**
 * Testcase for the PhpAnalyzer utility class
 */
class PhpAnalyzerTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function sampleClasses()
    {
        return array(
            array('phpCode' => '', 'namespace' => null, 'className' => null, 'fqn' => null),
            array('phpCode' => 'namespace Foo;', 'namespace' => null, 'className' => null, 'fqn' => null),
            array('phpCode' => 'class Bar {}', 'namespace' => null, 'className' => null, 'fqn' => null),
            array('phpCode' => '<?php class {}', 'namespace' => null, 'className' => null, 'fqn' => null),

            array('phpCode' => '<?php class SomeClass {}', 'namespace' => null, 'className' => 'SomeClass', 'fqn' => 'SomeClass'),
            array('phpCode' => '<?php namespace Foo\Bar; class SomeClass {}', 'namespace' => 'Foo\Bar', 'className' => 'SomeClass', 'fqn' => 'Foo\Bar\SomeClass'),

            array('phpCode' => '<?php namespace \Foo\Bar\; class SomeClass {}', 'namespace' => 'Foo\Bar', 'className' => 'SomeClass', 'fqn' => 'Foo\Bar\SomeClass'),
            array('phpCode' => '<?php ' . chr(13) . '  namespace  Foo\Bar {' . chr(13) . '	 class    SomeClass {}', 'namespace' => 'Foo\Bar', 'className' => 'SomeClass', 'fqn' => 'Foo\Bar\SomeClass'),
            array('phpCode' => 'foo <?php class SomeClass', 'namespace' => null, 'className' => 'SomeClass', 'fqn' => 'SomeClass'),
        );
    }

    /**
     * @param string $phpCode
     * @param string $namespace
     * @test
     * @dataProvider sampleClasses
     */
    public function extractNamespaceTests($phpCode, $namespace)
    {
        $phpAnalyzer = new PhpAnalyzer($phpCode);
        $this->assertSame($namespace, $phpAnalyzer->extractNamespace());
    }

    /**
     * @param string $phpCode
     * @param string $namespace
     * @param string $className
     * @test
     * @dataProvider sampleClasses
     */
    public function extractClassNameTests($phpCode, $namespace, $className)
    {
        $phpAnalyzer = new PhpAnalyzer($phpCode);
        $this->assertSame($className, $phpAnalyzer->extractClassName());
    }

    /**
     * @param string $phpCode
     * @param string $namespace
     * @param string $className
     * @param string $fqn
     * @test
     * @dataProvider sampleClasses
     */
    public function extractFullyQualifiedClassNameTests($phpCode, $namespace, $className, $fqn)
    {
        $phpAnalyzer = new PhpAnalyzer($phpCode);
        $this->assertSame($fqn, $phpAnalyzer->extractFullyQualifiedClassName());
    }
}
