<?php
namespace Neos\Http\Factories;

use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 *
 */
class StreamFactory implements StreamFactoryInterface
{
    /**
     * @inheritDoc
     */
    public function createStream(string $content = ''): StreamInterface
    {
        $fileHandle = fopen('php://temp', 'r+');
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
        return $this->createStreamFromResource($fileHandle);
    }

    /**
     * @inheritDoc
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        return new Stream($resource);
    }

}
