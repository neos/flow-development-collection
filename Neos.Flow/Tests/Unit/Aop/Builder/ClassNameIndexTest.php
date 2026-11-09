<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Aop\Builder;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Aop\Builder\ClassNameIndex;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the ClassNameIndex
 */
final class ClassNameIndexTest extends UnitTestCase
{
    #[Test]
    public function intersectOfTwoIndicesWorks()
    {
        $index1 = new ClassNameIndex();
        $index1->setClassNames(['\Foo\Bar', '\Foo\Baz']);
        $index2 = new ClassNameIndex();
        $index2->setClassNames(['\Foo\Baz', '\Foo\Blubb']);
        $intersectedIndex = $index1->intersect($index2);

        self::assertSame(['\Foo\Baz'], $intersectedIndex->getClassNames());
    }

    #[Test]
    public function applyIntersectWorks()
    {
        $index1 = new ClassNameIndex();
        $index1->setClassNames(['\Foo\Bar', '\Foo\Baz']);
        $index2 = new ClassNameIndex();
        $index2->setClassNames(['\Foo\Baz', '\Foo\Blubb']);
        $index1->applyIntersect($index2);

        self::assertSame(['\Foo\Baz'], $index1->getClassNames());
    }

    #[Test]
    public function unionOfTwoIndicesWorks()
    {
        $index1 = new ClassNameIndex();
        $index1->setClassNames(['\Foo\Bar', '\Foo\Baz']);
        $index2 = new ClassNameIndex();
        $index2->setClassNames(['\Foo\Baz', '\Foo\Blubb']);
        $intersectedIndex = $index1->union($index2);
        $intersectedIndex->sort();

        self::assertSame(['\Foo\Bar', '\Foo\Baz', '\Foo\Blubb'], $intersectedIndex->getClassNames());
    }

    #[Test]
    public function applyUnionWorks()
    {
        $index1 = new ClassNameIndex();
        $index1->setClassNames(['\Foo\Bar', '\Foo\Baz']);
        $index2 = new ClassNameIndex();
        $index2->setClassNames(['\Foo\Baz', '\Foo\Blubb']);
        $index1->applyUnion($index2);
        $index1->sort();

        self::assertSame(['\Foo\Bar', '\Foo\Baz', '\Foo\Blubb'], $index1->getClassNames());
    }

    #[Test]
    public function filterByPrefixWork()
    {
        $index1 = new ClassNameIndex();
        $index1->setClassNames(['\Foo\Bar', '\Foo\Baz', '\Bar\Baz', '\Foo\Blubb']);
        // We need to call sort manually!
        $index1->sort();

        $filteredIndex = $index1->filterByPrefix('\Foo');

        self::assertSame(['\Foo\Bar', '\Foo\Baz', '\Foo\Blubb'], $filteredIndex->getClassNames());
    }
}
