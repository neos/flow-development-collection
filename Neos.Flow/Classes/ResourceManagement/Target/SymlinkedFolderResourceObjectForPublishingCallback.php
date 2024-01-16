<?php

declare(strict_types=1);

namespace Neos\Flow\ResourceManagement\Target;

use Neos\Flow\ResourceManagement\ResourceMetaDataInterface;

/**
 * @internal only to satisfy the callback {@see TargetInterface::onPublish()}
 * Note that the callback is only invoked once per resource public directory of each package, instead of the actual contents
 */
class SymlinkedFolderResourceObjectForPublishingCallback implements ResourceMetaDataInterface
{
    private function __construct(private readonly string $path)
    {
    }

    /**
     * @internal
     */
    public static function createForPublishingCallback(string $path)
    {
        return new self($path);
    }

    public function setFilename($filename)
    {
        throw new \BadMethodCallException(__FUNCTION__ . ' is not supported');
    }

    public function getFilename()
    {
        return $this->path;
    }

    public function getFileSize()
    {
        throw new \BadMethodCallException(__FUNCTION__ . ' is not supported');
    }

    public function setFileSize($fileSize)
    {
        throw new \BadMethodCallException(__FUNCTION__ . ' is not supported');
    }

    public function setRelativePublicationPath($path)
    {
        throw new \BadMethodCallException(__FUNCTION__ . ' is not supported');
    }

    public function getRelativePublicationPath()
    {
        throw new \BadMethodCallException(__FUNCTION__ . ' is not supported');
    }

    public function getMediaType()
    {
        throw new \BadMethodCallException(__FUNCTION__ . ' is not supported');
    }

    public function getSha1()
    {
        throw new \BadMethodCallException(__FUNCTION__ . ' is not supported');
    }

    public function setSha1($sha1)
    {
        throw new \BadMethodCallException(__FUNCTION__ . ' is not supported');
    }
}
