<?php
namespace TYPO3\FLOW3\Tests\Unit\Aop\Builder;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the ClassNameIndex
 */
class ClassNameIndexTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function intersectOfTwoIndicesWorks() {
		$index1 = new \TYPO3\FLOW3\Aop\Builder\ClassNameIndex();
		$index1->setClassNames(array('\Foo\Bar', '\Foo\Baz'));
		$index2 = new \TYPO3\FLOW3\Aop\Builder\ClassNameIndex();
		$index2->setClassNames(array('\Foo\Baz', '\Foo\Blubb'));
		$intersectedIndex = $index1->intersect($index2);

		$this->assertEquals(array('\Foo\Baz'), $intersectedIndex->getClassNames());
	}

	/**
	 * @test
	 */
	public function applyIntersectWorks() {
		$index1 = new \TYPO3\FLOW3\Aop\Builder\ClassNameIndex();
		$index1->setClassNames(array('\Foo\Bar', '\Foo\Baz'));
		$index2 = new \TYPO3\FLOW3\Aop\Builder\ClassNameIndex();
		$index2->setClassNames(array('\Foo\Baz', '\Foo\Blubb'));
		$index1->applyIntersect($index2);

		$this->assertEquals(array('\Foo\Baz'), $index1->getClassNames());
	}

	/**
	 * @test
	 */
	public function unionOfTwoIndicesWorks() {
		$index1 = new \TYPO3\FLOW3\Aop\Builder\ClassNameIndex();
		$index1->setClassNames(array('\Foo\Bar', '\Foo\Baz'));
		$index2 = new \TYPO3\FLOW3\Aop\Builder\ClassNameIndex();
		$index2->setClassNames(array('\Foo\Baz', '\Foo\Blubb'));
		$intersectedIndex = $index1->union($index2);
		$intersectedIndex->sort();

		$this->assertEquals(array('\Foo\Bar', '\Foo\Baz', '\Foo\Blubb'), $intersectedIndex->getClassNames());
	}

	/**
	 * @test
	 */
	public function applyUnionWorks() {
		$index1 = new \TYPO3\FLOW3\Aop\Builder\ClassNameIndex();
		$index1->setClassNames(array('\Foo\Bar', '\Foo\Baz'));
		$index2 = new \TYPO3\FLOW3\Aop\Builder\ClassNameIndex();
		$index2->setClassNames(array('\Foo\Baz', '\Foo\Blubb'));
		$index1->applyUnion($index2);
		$index1->sort();

		$this->assertEquals(array('\Foo\Bar', '\Foo\Baz', '\Foo\Blubb'), $index1->getClassNames());
	}

	/**
	 * @test
	 */
	public function filterByPrefixWork() {
		$index1 = new \TYPO3\FLOW3\Aop\Builder\ClassNameIndex();
		$index1->setClassNames(array('\Foo\Bar', '\Foo\Baz', '\Bar\Baz', '\Foo\Blubb'));
			// We need to call sort manually!
		$index1->sort();

		$filteredIndex = $index1->filterByPrefix('\Foo');

		$this->assertEquals(array('\Foo\Bar', '\Foo\Baz', '\Foo\Blubb'), $filteredIndex->getClassNames());
	}

}
?>