<?php
declare(strict_types=1);

namespace Neos\Http\Factories;

use GuzzleHttp\Psr7\BufferStream;
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
        $stream = new BufferStream();
        $stream->write($content);
        return $stream;
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
