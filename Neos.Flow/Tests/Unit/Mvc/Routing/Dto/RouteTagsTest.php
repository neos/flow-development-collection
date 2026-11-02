<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Mvc\Routing\Dto;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Mvc\Routing\Dto\RouteTags;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the RouteTags DTO
 */
final class RouteTagsTest extends UnitTestCase
{
    public static function createFromTagThrowsExceptionForInvalidTagsDataProvider(): \Iterator
    {
        yield ['tag' => 'späcial'];
        yield ['tag' => 'tag with spaces'];
        yield ['tag' => 'verylongtagvaluewithmorethan150charactersshouldnotbeallowedverylongtagvaluewithmorethan150charactersshouldnotbeallowedverylongtagvaluewithmorethan150charactersshouldnotbeallowedverylongtagvaluewithmorethan150charactersshouldnotbeallowedverylongtagvaluewithmorethan150charactersshouldnotbeallowed'];
    }

    #[DataProvider('createFromTagThrowsExceptionForInvalidTagsDataProvider')]
    #[Test]
    public function createFromTagThrowsExceptionForInvalidTags($tag)
    {
        $this->expectException(\InvalidArgumentException::class);
        RouteTags::createFromTag($tag);
    }

    #[Test]
    public function createFromTagCreatesANewInstanceWithTheGivenTag()
    {
        $tags = RouteTags::createFromTag('foo');
        self::assertSame(['foo'], $tags->getTags());
    }

    #[Test]
    public function createFromArrayCreatesAnInstanceWithAllGivenTags()
    {
        $tags = RouteTags::createFromArray(['foo', 'bar', 'baz']);
        self::assertSame(['foo', 'bar', 'baz'], $tags->getTags());
    }

    #[Test]
    public function createFromArrayDoesNotAcceptIntegerValues()
    {
        $this->expectException(\InvalidArgumentException::class);
        RouteTags::createFromArray([123]);
    }

    #[Test]
    public function createFromArrayDoesNotAcceptObjectValues()
    {
        $this->expectException(\InvalidArgumentException::class);
        RouteTags::createFromArray([new \stdClass()]);
    }

    #[Test]
    public function mergeUnifiesTags()
    {
        $tags1 = RouteTags::createEmpty()->withTag('foo')->withTag('bar');
        $tags2 = RouteTags::createEmpty()->withTag('foo')->withTag('baz');
        $mergedTags = $tags1->merge($tags2);
        self::assertSame(['foo', 'bar', 'baz'], $mergedTags->getTags());
    }

    #[Test]
    public function withTagReturnsTheSameInstanceIfTheTagAlreadyExists()
    {
        $tags1 = RouteTags::createEmpty()->withTag('foo');
        $tags2 = $tags1->withTag('foo');

        self::assertSame($tags1, $tags2);
    }

    #[Test]
    public function withTagReturnsAnInstanceWithTheNewTag()
    {
        $tags1 = RouteTags::createEmpty()->withTag('foo');
        $tags2 = $tags1->withTag('bar');

        self::assertTrue($tags2->has('bar'));
    }

    #[Test]
    public function withTagDoesNotMutateTheInstance()
    {
        $tags1 = RouteTags::createEmpty()->withTag('foo');
        $tags1->withTag('bar');

        self::assertFalse($tags1->has('bar'));
    }
}
