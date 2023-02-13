<?php
declare(strict_types=1);

namespace Neos\Cache\Backend;

final class FileBackendEntryDto
{
    private const EXPIRYTIME_LENGTH = 14;
    private const DATASIZE_DIGITS = 10;

    /**
     * @var string
     */
    private $data;

    /**
     * @var string[]
     */
    private $tags;

    /**
     * @var int
     */
    private $expiryTime;


    public function __construct(string $data, array $tags, int $expiryTime)
    {
        $this->data = $data;
        $this->tags = $tags;
        $this->expiryTime = $expiryTime;
    }

    public static function fromString(string $cacheData): FileBackendEntryDto
    {
        $entrySize = strlen($cacheData);
        $dataSize = (int)substr($cacheData, -(static::DATASIZE_DIGITS));
        $expiryTime = (int)substr($cacheData, -(static::DATASIZE_DIGITS + static::EXPIRYTIME_LENGTH), static::EXPIRYTIME_LENGTH);
        $tagString = substr($cacheData, $dataSize, $entrySize - $dataSize - static::EXPIRYTIME_LENGTH - static::DATASIZE_DIGITS);
        $tags = empty($tagString) ? [] : explode(' ', $tagString);
        return new static(substr($cacheData, 0, $dataSize), $tags, $expiryTime);
    }

    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getExpiryTime(): int
    {
        return $this->expiryTime;
    }

    public function isExpired(): bool
    {
        return ($this->getExpiryTime() !== 0 && $this->getExpiryTime() < time());
    }

    public function isTaggedWith(string $tag): bool
    {
        return !empty($this->tags) && in_array($tag, $this->tags, true);
    }

    public function __toString(): string
    {
        $metaData = implode(' ', $this->tags) . str_pad((string)$this->expiryTime, static::EXPIRYTIME_LENGTH) . str_pad((string)strlen($this->data), static::DATASIZE_DIGITS);
        return $this->data . $metaData;
    }
}
