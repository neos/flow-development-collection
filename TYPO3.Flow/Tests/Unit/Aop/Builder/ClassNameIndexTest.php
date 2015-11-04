<?php
namespace TYPO3\Flow\Tests\Unit\Aop\Builder;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the ClassNameIndex
 */
class ClassNameIndexTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function intersectOfTwoIndicesWorks()
    {
        $index1 = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();
        $index1->setClassNames(array('\Foo\Bar', '\Foo\Baz'));
        $index2 = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();
        $index2->setClassNames(array('\Foo\Baz', '\Foo\Blubb'));
        $intersectedIndex = $index1->intersect($index2);

        $this->assertEquals(array('\Foo\Baz'), $intersectedIndex->getClassNames());
    }

    /**
     * @test
     */
    public function applyIntersectWorks()
    {
        $index1 = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();
        $index1->setClassNames(array('\Foo\Bar', '\Foo\Baz'));
        $index2 = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();
        $index2->setClassNames(array('\Foo\Baz', '\Foo\Blubb'));
        $index1->applyIntersect($index2);

        $this->assertEquals(array('\Foo\Baz'), $index1->getClassNames());
    }

    /**
     * @test
     */
    public function unionOfTwoIndicesWorks()
    {
        $index1 = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();
        $index1->setClassNames(array('\Foo\Bar', '\Foo\Baz'));
        $index2 = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();
        $index2->setClassNames(array('\Foo\Baz', '\Foo\Blubb'));
        $intersectedIndex = $index1->union($index2);
        $intersectedIndex->sort();

        $this->assertEquals(array('\Foo\Bar', '\Foo\Baz', '\Foo\Blubb'), $intersectedIndex->getClassNames());
    }

    /**
     * @test
     */
    public function applyUnionWorks()
    {
        $index1 = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();
        $index1->setClassNames(array('\Foo\Bar', '\Foo\Baz'));
        $index2 = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();
        $index2->setClassNames(array('\Foo\Baz', '\Foo\Blubb'));
        $index1->applyUnion($index2);
        $index1->sort();

        $this->assertEquals(array('\Foo\Bar', '\Foo\Baz', '\Foo\Blubb'), $index1->getClassNames());
    }

    /**
     * @test
     */
    public function filterByPrefixWork()
    {
        $index1 = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();
        $index1->setClassNames(array('\Foo\Bar', '\Foo\Baz', '\Bar\Baz', '\Foo\Blubb'));
        // We need to call sort manually!
        $index1->sort();

        $filteredIndex = $index1->filterByPrefix('\Foo');

        $this->assertEquals(array('\Foo\Bar', '\Foo\Baz', '\Foo\Blubb'), $filteredIndex->getClassNames());
    }
}
