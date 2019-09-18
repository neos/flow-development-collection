<?php
namespace Neos\Flow\Http\Helper;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Http\Factories\FlowUploadedFile;
use Neos\Utility\Arrays;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Helper to re-organize uploaded file data for requests.
 */
abstract class UploadedFilesHelper
{
    /**
     * @param UploadedFileInterface[] $uploadedFiles
     * @param array $arguments
     * @param array $currentPath internal argument for recursion
     * @return array The nested array of paths and uploaded files
     */
    public static function upcastUploadedFiles(array $uploadedFiles, array $arguments, array $currentPath = []): array
    {
        $upcastedUploads = [];

        foreach ($uploadedFiles as $key => $value) {
            $currentPath[] = $key;
            if ($value instanceof UploadedFileInterface) {
                $originallySubmittedResourcePath = array_merge($currentPath, ['originallySubmittedResource']);
                $collectionNamePath = array_merge($currentPath, ['__collectionName']);
                $upcastedUploads[$key] = self::upcastUploadedFile(
                    $value,
                    Arrays::getValueByPath($arguments, $originallySubmittedResourcePath),
                    Arrays::getValueByPath($arguments, $collectionNamePath)
                );
            }

            if (is_array($value)) {
                $upcastedUploads[$key] = self::upcastUploadedFiles($value, $arguments, $currentPath);
            }
        }

        return $upcastedUploads;
    }

    /**
     * @param UploadedFileInterface $uploadedFile
     * @param string|array $originallySubmittedResource
     * @param string $collectionName
     * @return FlowUploadedFile
     */
    protected static function upcastUploadedFile(UploadedFileInterface $uploadedFile, $originallySubmittedResource = null, string $collectionName = null): FlowUploadedFile
    {
        $flowUploadedFile = new FlowUploadedFile($uploadedFile->getStream(), ($uploadedFile->getSize() ?: 0), $uploadedFile->getError(), $uploadedFile->getClientFilename(), $uploadedFile->getClientMediaType());
        if ($originallySubmittedResource) {
            $flowUploadedFile->setOriginallySubmittedResource($originallySubmittedResource);
        }

        if ($collectionName) {
            $flowUploadedFile->setCollectionName($collectionName);
        }

        return $flowUploadedFile;
    }

    /**
     * Transforms the convoluted _FILES superglobal into a manageable form.
     *
     * @param array $convolutedFiles The _FILES superglobal or something with the same structure
     * @return array Untangled files
     */
    public static function untangleFilesArray(array $convolutedFiles): array
    {
        $untangledFiles = [];

        $fieldPaths = [];
        foreach ($convolutedFiles as $firstLevelFieldName => $fieldInformation) {
            if (!is_array($fieldInformation['error'])) {
                $fieldPaths[] = [$firstLevelFieldName];
            } else {
                $newFieldPaths = self::calculateFieldPathsAsArray($fieldInformation['error'], $firstLevelFieldName);
                $fieldPaths = array_merge($fieldPaths, $newFieldPaths);
            }
        }

        foreach ($fieldPaths as $fieldPath) {
            if (count($fieldPath) === 1) {
                $fileInformation = $convolutedFiles[$fieldPath{0}];
            } else {
                $fileInformation = [];
                foreach ($convolutedFiles[$fieldPath{0}] as $key => $subStructure) {
                    $fileInformation[$key] = Arrays::getValueByPath($subStructure, array_slice($fieldPath, 1));
                }
            }
            if (isset($fileInformation['error']) && $fileInformation['error'] !== \UPLOAD_ERR_NO_FILE) {
                $untangledFiles = Arrays::setValueByPath($untangledFiles, $fieldPath, $fileInformation);
            }
        }

        return $untangledFiles;
    }

    /**
     * Returns an array of all possible "field paths" for the given array.
     *
     * @param array $structure The array to walk through
     * @param string $firstLevelFieldName
     * @return array An array of paths (as arrays) in the format ["key1", "key2", "key3"] ...
     */
    protected static function calculateFieldPathsAsArray(array $structure, string $firstLevelFieldName = null): array
    {
        $fieldPaths = [];
        foreach ($structure as $key => $subStructure) {
            $fieldPath = [];
            if ($firstLevelFieldName !== null) {
                $fieldPath[] = $firstLevelFieldName;
            }
            $fieldPath[] = $key;
            if (is_array($subStructure)) {
                foreach (self::calculateFieldPathsAsArray($subStructure) as $subFieldPath) {
                    $fieldPaths[] = array_merge($fieldPath, $subFieldPath);
                }
            } else {
                $fieldPaths[] = $fieldPath;
            }
        }

        return $fieldPaths;
    }
}
