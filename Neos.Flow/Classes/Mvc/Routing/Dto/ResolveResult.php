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
final class ResolveResult
{

    /**
     * @var string|null
     */
    private $resolvedValue;

    /**
     * @var UriConstraints|null
     */
    private $uriConstraints;

    /**
     * @var Tags|null
     */
    private $tags;

    /**
     * @param string $resolvedValue
     * @param UriConstraints $uriConstraints
     * @param Tags $tags
     */
    public function __construct(string $resolvedValue, UriConstraints $uriConstraints = null, Tags $tags = null)
    {
        $this->resolvedValue = $resolvedValue;
        $this->uriConstraints = $uriConstraints;
        $this->tags = $tags;
    }

    /**
     * @return string|null
     */
    public function getResolvedValue()
    {
        return $this->resolvedValue;
    }

    /**
     * @return bool
     */
    public function hasUriConstraints(): bool
    {
        return $this->uriConstraints !== null;
    }

    /**
     * @return UriConstraints|null
     */
    public function getUriConstraints()
    {
        return $this->uriConstraints;
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
