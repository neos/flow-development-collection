<?php
namespace Neos\Http\Factories;

use Neos\Flow\Http\FlowUploadedFile;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 *
 */
class UploadedFileFactory implements UploadedFileFactoryInterface
{
    /**
     * @inheritDoc
     */
    public function createUploadedFile(StreamInterface $stream, int $size = null, int $error = \UPLOAD_ERR_OK, string $clientFilename = null, string $clientMediaType = null): UploadedFileInterface
    {
        // FIXME: Dependency problem, Move FlowUploadedFile ?
        return new FlowUploadedFile($stream, $size, $error);
    }

}
