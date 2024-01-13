<?php
namespace Neos\Flow\Mvc\Routing\Dto;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * The result of a RoutePartInterface::match() call if the corresponding Route Part matched
 *
 * @Flow\Proxy(false)
 */
final class MatchResult
{
    /**
     * @var mixed
     */
    private $matchedValue;

    /**
     * @var RouteTags|null
     */
    private $tags;

    /**
     * @var RouteLifetime|null
     */
    private $lifetime;

    /**
     * @param mixed $matchedValue
     * @param RouteTags $tags
     */
    public function __construct($matchedValue, RouteTags $tags = null, RouteLifetime $lifetime = null)
    {
        $this->matchedValue = $matchedValue;
        $this->tags = $tags;
        $this->lifetime = $lifetime;
    }

    /**
     * The actual matched value of the respective Route Part
     *
     * @return mixed
     */
    public function getMatchedValue()
    {
        return $this->matchedValue;
    }

    /**
     * Whether this result is tagged
     *
     * @return bool
     */
    public function hasTags(): bool
    {
        return $this->tags !== null;
    }

    /**
     * RouteTags to be associated with the MatchResult, or NULL
     *
     * @return RouteTags|null
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Whether this has a lifetime
     *
     * @return bool
     * @phpstan-assert-if-true RouteLifetime $this->getLifetime()
     */
    public function hasLifetime(): bool
    {
        return $this->lifetime !== null;
    }

    /**
     * RouteLifetime to be associated with the MatchResult, or NULL
     *
     * @return RouteLifetime|null
     */
    public function getLifetime(): ?RouteLifetime
    {
        return $this->lifetime;
    }
}
