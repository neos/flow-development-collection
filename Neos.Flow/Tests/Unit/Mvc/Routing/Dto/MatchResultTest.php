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

use Neos\Flow\Mvc\Routing\Dto\MatchResult;
use Neos\Flow\Mvc\Routing\Dto\RouteTags;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the MatchResult DTO
 */
class MatchResultTest extends UnitTestCase
{
    /**
     * @test
     */
    public function matchedValueCanBeRetrieved()
    {
        $matchedValue = new \stdClass();
        $matchResult = new MatchResult($matchedValue);
        self::assertSame($matchedValue, $matchResult->getMatchedValue());
    }

    /**
     * @test
     */
    public function hasTagsIsFalseByDefault()
    {
        $matchResult = new MatchResult('matchedValue');
        self::assertFalse($matchResult->hasTags());
    }

    /**
     * @test
     */
    public function hasTagsIsTrueIfTagsAreSet()
    {
        $tags = RouteTags::createEmpty();
        $matchResult = new MatchResult('matchedValue', $tags);
        self::assertTrue($matchResult->hasTags());
    }

    /**
     * @test
     */
    public function getTagsReturnsNullByDefault()
    {
        $matchResult = new MatchResult('matchedValue');
        self::assertNull($matchResult->getTags());
    }

    /**
     * @test
     */
    public function getTagsReturnsSpecifiedTags()
    {
        $tags = RouteTags::createEmpty()->withTag('foo');
        $matchResult = new MatchResult('matchedValue', $tags);
        self::assertSame($tags, $matchResult->getTags());
    }
}
