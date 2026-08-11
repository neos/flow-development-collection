<?php
namespace Neos\Flow\Mvc;

/**
 * @implements \IteratorAggregate<RequestTag>
 */
final readonly class RequestTags implements \IteratorAggregate, \Countable
{
    /**
     * @var RequestTag[]
     */
    public array $tags;

    /**
     */
    public function __construct(RequestTag ...$tags) {
        $this->tags = $tags;
    }

    public static function fromString(string $json): RequestTags
    {
        $tagsAsString = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        $tags = [];
        foreach ($tagsAsString as $tagString) {
            $tag = RequestTag::fromString($tagString);
            $tags[] = $tag;
        }

        return new self(...$tags);
    }

    public static function empty(): RequestTags
    {
        return new self();
    }

    public function __toString(): string
    {
        return json_encode($this->tags, JSON_THROW_ON_ERROR);
    }

    /**
     * @return \Traversable<RequestTag>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->tags;
    }

    public function count(): int
    {
        return count($this->tags);
    }
}
