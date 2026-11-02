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
use Neos\Flow\Mvc\Routing\Dto\MatchResult;
use Neos\Flow\Mvc\Routing\Dto\RouteTags;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the MatchResult DTO
 */
final class MatchResultTest extends UnitTestCase
{
    #[Test]
    public function matchedValueCanBeRetrieved()
    {
        $matchedValue = new \stdClass();
        $matchResult = new MatchResult($matchedValue);
        self::assertSame($matchedValue, $matchResult->getMatchedValue());
    }

    #[Test]
    public function hasTagsIsFalseByDefault()
    {
        $matchResult = new MatchResult('matchedValue');
        self::assertFalse($matchResult->hasTags());
    }

    #[Test]
    public function hasTagsIsTrueIfTagsAreSet()
    {
        $tags = RouteTags::createEmpty();
        $matchResult = new MatchResult('matchedValue', $tags);
        self::assertTrue($matchResult->hasTags());
    }

    #[Test]
    public function getTagsReturnsNullByDefault()
    {
        $matchResult = new MatchResult('matchedValue');
        self::assertNull($matchResult->getTags());
    }

    #[Test]
    public function getTagsReturnsSpecifiedTags()
    {
        $tags = RouteTags::createEmpty()->withTag('foo');
        $matchResult = new MatchResult('matchedValue', $tags);
        self::assertSame($tags, $matchResult->getTags());
    }
}
