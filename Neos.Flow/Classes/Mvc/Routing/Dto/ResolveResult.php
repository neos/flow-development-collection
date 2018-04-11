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
 * The result of a RoutePartInterface::resolve() call if the corresponding Route Part resolved
 *
 * @Flow\Proxy(false)
 */
final class ResolveResult
{

    /**
     * The resolved string value, or NULL if the corresponding Route Part doesn't affect the uri path
     *
     * @var string|null
     */
    private $resolvedValue;

    /**
     * The resolved URI constraints, or NULL if the corresponding Route Part doesn't add any constraints
     *
     * @var UriConstraints|null
     */
    private $uriConstraints;

    /**
     * RouteTags to be associated with the result, or NULL
     *
     * @var RouteTags|null
     */
    private $tags;

    /**
     * @param string $resolvedValue
     * @param UriConstraints $uriConstraints
     * @param RouteTags $tags
     */
    public function __construct(string $resolvedValue, UriConstraints $uriConstraints = null, RouteTags $tags = null)
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
     * @return RouteTags|null
     */
    public function getTags()
    {
        return $this->tags;
    }
}
