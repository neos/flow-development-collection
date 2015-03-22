<?php
namespace TYPO3\Flow\Tests\Unit\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\Utility\PhpAnalyzer;

/**
 * Testcase for the PhpAnalyzer utility class
 */
class PhpAnalyzerTest extends UnitTestCase {

	/**
	 * @return array
	 */
	public function sampleClasses() {
		return array(
			array('phpCode' => '', 'namespace' => NULL, 'className' => NULL, 'fqn' => NULL),
			array('phpCode' => 'namespace Foo;', 'namespace' => NULL, 'className' => NULL, 'fqn' => NULL),
			array('phpCode' => 'class Bar {}', 'namespace' => NULL, 'className' => NULL, 'fqn' => NULL),
			array('phpCode' => '<?php class {}', 'namespace' => NULL, 'className' => NULL, 'fqn' => NULL),

			array('phpCode' => '<?php class SomeClass {}', 'namespace' => NULL, 'className' => 'SomeClass', 'fqn' => 'SomeClass'),
			array('phpCode' => '<?php namespace Foo\Bar; class SomeClass {}', 'namespace' => 'Foo\Bar', 'className' => 'SomeClass', 'fqn' => 'Foo\Bar\SomeClass'),

			array('phpCode' => '<?php namespace \Foo\Bar\; class SomeClass {}', 'namespace' => 'Foo\Bar', 'className' => 'SomeClass', 'fqn' => 'Foo\Bar\SomeClass'),
			array('phpCode' => '<?php ' . chr(13) . '  namespace  Foo\Bar {' . chr(13) . '	 class    SomeClass {}', 'namespace' => 'Foo\Bar', 'className' => 'SomeClass', 'fqn' => 'Foo\Bar\SomeClass'),
			array('phpCode' => 'foo <?php class SomeClass', 'namespace' => NULL, 'className' => 'SomeClass', 'fqn' => 'SomeClass'),
		);
	}

	/**
	 * @param string $phpCode
	 * @param string $namespace
	 * @test
	 * @dataProvider sampleClasses
	 */
	public function extractNamespaceTests($phpCode, $namespace) {
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
	public function extractClassNameTests($phpCode, $namespace, $className) {
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
	public function extractFullyQualifiedClassNameTests($phpCode, $namespace, $className, $fqn) {
		$phpAnalyzer = new PhpAnalyzer($phpCode);
		$this->assertSame($fqn, $phpAnalyzer->extractFullyQualifiedClassName());
	}
}
