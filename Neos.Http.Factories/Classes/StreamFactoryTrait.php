<?php

declare(strict_types=1);

namespace Neos\Http\Factories;

use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\StreamInterface;

/**
 *
 */
trait StreamFactoryTrait
{
    /**
     * @inheritDoc
     */
    public function createStream(string $content = ''): StreamInterface
    {
        $fileHandle = fopen('php://temp', 'r+');
        if (!is_resource($fileHandle)) {
            throw new \Exception('unable to open php://temp', 1743846274);
        }
        fwrite($fileHandle, $content);
        rewind($fileHandle);

        return $this->createStreamFromResource($fileHandle);
    }

    /**
     * @inheritDoc
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        $fileHandle = fopen($filename, $mode);
        if (!is_resource($fileHandle)) {
            throw new \Exception('unable to open ' . $filename, 1743846243);
        }
        return $this->createStreamFromResource($fileHandle);
    }

    /**
     * @inheritDoc
     * @param resource $resource
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        return new Stream($resource);
    }

}
