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
 * RouteTags to be associated with matched/resolved routes
 *
 * RouteTags can be added by Route Part handlers via the ResolveResult/MatchResult return values
 * The tags will be added to the corresponding cache entries, so that they can be flushed selectively using the RouterCachingService
 *
 * @Flow\Proxy(false)
 */
final class RouteTags
{

    /**
     * Pattern a tag must match. @see \Neos\Cache\Frontend\FrontendInterface::PATTERN_TAG
     */
    const PATTERN_TAG = '/^[a-zA-Z0-9_%\-&]{1,250}$/';

    /**
     * @var string[] numeric array of strings satisfying the PATTERN_TAG regex
     */
    private $tags = [];

    /**
     * @param string[] $tags numeric array of strings satisfying the PATTERN_TAG regex
     */
    private function __construct(array $tags)
    {
        $this->tags = $tags;
    }

    /**
     * Creates an instance without any tags
     *
     * @return RouteTags
     */
    public static function createEmpty(): self
    {
        return new static([]);
    }

    /**
     * Creates an instance with one given tag
     *
     * @param string $tag Tag value satisfying the PATTERN_TAG regex
     * @return RouteTags
     */
    public static function createFromTag(string $tag): self
    {
        self::validateTag($tag);
        return new static([$tag]);
    }

    /**
     * Creates an instance with one given tags
     *
     * @param string[] $tags An array of strings satisfying the PATTERN_TAG regex
     * @return RouteTags
     */
    public static function createFromArray(array $tags): self
    {
        array_walk($tags, 'static::validateTag');
        return new static($tags);
    }

    /**
     * Merges two instances of this class combining and unifying all tags
     *
     * @param RouteTags $tags
     * @return RouteTags
     */
    public function merge(RouteTags $tags): self
    {
        $mergedTags = array_unique(array_merge($this->tags, $tags->tags));
        return new static($mergedTags);
    }

    /**
     * Creates a new instance with the given $tag added
     * If the $tag has been added already, this instance is returned
     *
     * @param string $tag
     * @return RouteTags
     */
    public function withTag(string $tag): self
    {
        if ($this->has($tag)) {
            return $this;
        }
        self::validateTag($tag);
        $newTags = $this->tags;
        $newTags[] = $tag;
        return new static($newTags);
    }

    /**
     * Checks the format of a given $tag string and throws an exception if it does not conform to the PATTERN_TAG regex
     *
     * @param string $tag
     * @throws \InvalidArgumentException
     */
    private static function validateTag($tag)
    {
        if (!is_string($tag)) {
            throw new \InvalidArgumentException(sprintf('RouteTags have to be strings, %s given', is_object($tag) ? get_class($tag) : gettype($tag)), 1512553153);
        }
        if (preg_match(self::PATTERN_TAG, $tag) !== 1) {
            throw new \InvalidArgumentException(sprintf('The given string "%s" is not a valid tag', $tag), 1511807639);
        }
    }

    /**
     * Whether a given $tag is contained in the collection of this instance
     *
     * @param string $tag
     * @return bool
     */
    public function has(string $tag)
    {
        return in_array($tag, $this->tags, true);
    }

    /**
     * Returns the tags of this tag collection as value array
     *
     * @return array
     */
    public function getTags(): array
    {
        return array_values($this->tags);
    }
}
