<?php
declare(strict_types=1);

namespace Neos\Http\Factories;

use GuzzleHttp\Psr7\UploadedFile;

/**
 * A Flow specific uploaded file.
 */
class FlowUploadedFile extends UploadedFile
{
    /**
     * This is either the persistent identifier of a previously submitted resource file
     * or an array with the "__identity" key set to the persistent identifier.
     *
     * @var array{__identity: string}|string
     */
    protected $originallySubmittedResource;

    /**
     * @var string
     */
    protected $collectionName;

    /**
     * This is either the persistent identifier of a previously submitted resource file
     * or an array with the "__identity" key set to the persistent identifier.
     *
     * @return array{__identity: string}|string
     */
    public function getOriginallySubmittedResource()
    {
        return $this->originallySubmittedResource;
    }

    /**
     * Sets a previously submitted resource reference.
     *
     * This is either the persistent identifier of a previously submitted resource file
     * or an array with the "__identity" key set to the persistent identifier.
     *
     * @param array{__identity: string}|string $originallySubmittedResource
     */
    public function setOriginallySubmittedResource($originallySubmittedResource): void
    {
        $this->originallySubmittedResource = $originallySubmittedResource;
    }

    /**
     * @return string
     */
    public function getCollectionName()
    {
        return $this->collectionName;
    }

    /**
     * @param string $collectionName
     */
    public function setCollectionName($collectionName): void
    {
        $this->collectionName = $collectionName;
    }
}
