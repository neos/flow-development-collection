<?php
namespace Neos\Flow\Utilities\Tests\Unit;

/*
 * This file is part of the Neos.Flow.Utilities package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Utility\PhpAnalyzer;

/**
 * Testcase for the PhpAnalyzer utility class
 */
class PhpAnalyzerTest extends \PHPUnit_Framework_TestCase
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
