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

use Neos\Cache\CacheAwareInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class Tags implements CacheAwareInterface
{

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

    public static function createFromTag(string $tagName, string $tagValue): self
    {
        return new static([$tagName => $tagValue]);
    }

    public function merge(Tags $tags): self
    {
        $mergedTags = array_merge($this->tags, $tags->tags);
        return new static($mergedTags);
    }

    public function withTag(string $tagName, string $tagValue): self
    {
        $newTags = $this->tags;
        $newTags[$tagName] = $tagValue;
        return new static($newTags);
    }

    public function has(string $tagName)
    {
        return isset($this->tags[$tagName]);
    }

    public function getCacheEntryIdentifier(): string
    {
        $cacheIdentifierParts = [];
        foreach($this->tags as $tagName => $tagValue) {
            $cacheIdentifierParts[] = $tagName . ':' . $tagValue;
        }
        return md5(implode('|', $cacheIdentifierParts));
    }

}
