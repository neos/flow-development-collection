<?php
namespace TYPO3\Flow\Http;

/**
 * A Flow specific uploaded file.
 */
class FlowUploadedFile extends UploadedFile
{
    /**
     * @var array|string
     */
    protected $originallySubmittedResource;

    /**
     * @var string
     */
    protected $collectionName;

    /**
     * @return array|string
     */
    public function getOriginallySubmittedResource()
    {
        return $this->originallySubmittedResource;
    }

    /**
     * @param array|string $originallySubmittedResource
     */
    public function setOriginallySubmittedResource($originallySubmittedResource)
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
    public function setCollectionName($collectionName)
    {
        $this->collectionName = $collectionName;
    }
}
