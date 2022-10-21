<?php
declare(strict_types=1);

namespace Neos\Cache\Backend;

class FileBackendDataDto
{
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

    public static function parseFromCacheData(string $cacheData): static
    {
        $entrySize = strlen($cacheData);
        $dataSize = (int)substr($cacheData, -(FileBackend::DATASIZE_DIGITS));
        $expiryTime = (int)substr($cacheData, -(FileBackend::DATASIZE_DIGITS + FileBackend::EXPIRYTIME_LENGTH), FileBackend::EXPIRYTIME_LENGTH);
        $tagString = substr($cacheData, $dataSize, $entrySize - $dataSize - FileBackend::EXPIRYTIME_LENGTH - FileBackend::DATASIZE_DIGITS);
        $tags = explode(' ', $tagString);
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

    public function __toString(): string
    {
        $metaData = implode(' ', $this->tags) . str_pad((string)$this->expiryTime, FileBackend::EXPIRYTIME_LENGTH) . str_pad((string)strlen($this->data), FileBackend::DATASIZE_DIGITS);
        return $this->data . $metaData;
    }
}
