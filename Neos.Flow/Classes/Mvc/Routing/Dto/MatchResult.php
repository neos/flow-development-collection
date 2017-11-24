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
 * @Flow\Proxy(false)
 */
final class MatchResult
{

    /**
     * @var mixed
     */
    private $matchedValue;

    /**
     * @var Tags|null
     */
    private $tags;

    /**
     * @param mixed $matchedValue
     * @param Tags $tags
     */
    public function __construct($matchedValue, Tags $tags = null)
    {
        $this->matchedValue = $matchedValue;
        $this->tags = $tags;
    }

    /**
     * @return mixed
     */
    public function getMatchedValue()
    {
        return $this->matchedValue;
    }

    /**
     * @return bool
     */
    public function hasTags(): bool
    {
        return $this->tags !== null;
    }

    /**
     * @return Tags|null
     */
    public function getTags()
    {
        return $this->tags;
    }
}
