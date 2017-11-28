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
final class Tags
{

    /**
     * Pattern a tag must match. @see \Neos\Cache\Frontend\FrontendInterface::PATTERN_TAG
     */
    const PATTERN_TAG = '/^[a-zA-Z0-9_%\-&]{1,250}$/';

    /**
     * @var string[]
     */
    private $tags = [];

    /**
     * @param string[] $tags
     */
    private function __construct(array $tags)
    {
        $this->tags = $tags;
    }


    public static function createEmpty(): self
    {
        return new static([]);
    }

    public static function createFromTag(string $tag): self
    {
        self::validateTag($tag);
        return new static([$tag]);
    }

    public function merge(Tags $tags): self
    {
        $mergedTags = array_unique(array_merge($this->tags, $tags->tags));
        return new static($mergedTags);
    }

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

    private static function validateTag(string $tag)
    {
        if (preg_match(self::PATTERN_TAG, $tag) !== 1) {
            throw new \InvalidArgumentException(sprintf('The given string "%s" is not a valid tag', $tag), 1511807639);
        }
    }

    public function has(string $tag)
    {
        return in_array($tag, $this->tags, true);
    }

    public function getTags(): array
    {
        return array_values($this->tags);
    }
}
