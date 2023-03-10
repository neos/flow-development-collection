<?php
declare(strict_types=1);

namespace Neos\Http\Factories;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 *
 */
trait UploadedFileFactoryTrait
{
    /**
     * @inheritDoc
     */
    public function createUploadedFile(StreamInterface $stream, int $size = null, int $error = \UPLOAD_ERR_OK, string $clientFilename = null, string $clientMediaType = null): UploadedFileInterface
    {
        return new FlowUploadedFile($stream, $size ?: 0, $error, $clientFilename, $clientMediaType);
    }
}
