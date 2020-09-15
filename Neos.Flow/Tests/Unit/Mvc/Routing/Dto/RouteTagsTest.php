<?php
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

/**
 * Testcase for the RouteTags DTO
 */
class RouteTagsTest extends UnitTestCase
{
    public function createFromTagThrowsExceptionForInvalidTagsDataProvider()
    {
        return [
            ['tag' => 'späcial'],
            ['tag' => 'tag with spaces'],
            ['tag' => 'verylongtagvaluewithmorethan150charactersshouldnotbeallowedverylongtagvaluewithmorethan150charactersshouldnotbeallowedverylongtagvaluewithmorethan150charactersshouldnotbeallowedverylongtagvaluewithmorethan150charactersshouldnotbeallowedverylongtagvaluewithmorethan150charactersshouldnotbeallowed'],
        ];
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @dataProvider createFromTagThrowsExceptionForInvalidTagsDataProvider
     */
    public function createFromTagThrowsExceptionForInvalidTags($tag)
    {
        RouteTags::createFromTag($tag);
    }

    /**
     * @test
     */
    public function createFromTagCreatesANewInstanceWithTheGivenTag()
    {
        $tags = RouteTags::createFromTag('foo');
        $this->assertSame(['foo'], $tags->getTags());
    }

    /**
     * @test
     */
    public function createFromArrayCreatesAnInstanceWithAllGivenTags()
    {
        $tags = RouteTags::createFromArray(['foo', 'bar', 'baz']);
        $this->assertSame(['foo', 'bar', 'baz'], $tags->getTags());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function createFromArrayDoesNotAcceptIntegerValues()
    {
        RouteTags::createFromArray([123]);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function createFromArrayDoesNotAcceptObjectValues()
    {
        RouteTags::createFromArray([new \stdClass()]);
    }

    /**
     * @test
     */
    public function mergeUnifiesTags()
    {
        $tags1 = RouteTags::createEmpty()->withTag('foo')->withTag('bar');
        $tags2 = RouteTags::createEmpty()->withTag('foo')->withTag('baz');
        $mergedTags = $tags1->merge($tags2);
        $this->assertSame(['foo', 'bar', 'baz'], $mergedTags->getTags());
    }

    /**
     * @test
     */
    public function withTagReturnsTheSameInstanceIfTheTagAlreadyExists()
    {
        $tags1 = RouteTags::createEmpty()->withTag('foo');
        $tags2 = $tags1->withTag('foo');

        $this->assertSame($tags1, $tags2);
    }

    /**
     * @test
     */
    public function withTagReturnsAnInstanceWithTheNewTag()
    {
        $tags1 = RouteTags::createEmpty()->withTag('foo');
        $tags2 = $tags1->withTag('bar');

        $this->assertTrue($tags2->has('bar'));
    }

    /**
     * @test
     */
    public function withTagDoesNotMutateTheInstance()
    {
        $tags1 = RouteTags::createEmpty()->withTag('foo');
        $tags1->withTag('bar');

        $this->assertFalse($tags1->has('bar'));
    }
}
