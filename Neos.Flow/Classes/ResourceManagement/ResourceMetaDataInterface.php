<?php
namespace Neos\Flow\ResourceManagement;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * Interface which defines the basic meta data getters and setters for PersistentResource
 * and Storage/Object objects.
 */
interface ResourceMetaDataInterface
{
    /**
     * Sets the filename
     *
     * @param string $filename
     * @return void
     */
    public function setFilename($filename);

    /**
     * Gets the filename
     *
     * @return string The filename
     */
    public function getFilename();

    /**
     * Returns the size of the content of this storage object
     *
     * @return string The md5 hash
     */
    public function getFileSize();

    /**
     * Sets the size of the content of this storage object
     *
     * @param string $fileSize The content size
     * @return void
     */
    public function setFileSize($fileSize);

    /**
     * @param string $path
     * @return void
     */
    public function setRelativePublicationPath($path);

    /**
     * @return string
     */
    public function getRelativePublicationPath();

    /**
     * Returns the Media Type for this storage object
     *
     * @return string The IANA Media Type
     */
    public function getMediaType();

    /**
     * Returns the sha1 hash of the content of this storage object
     *
     * @return string The sha1 hash
     */
    public function getSha1();

    /**
     * Sets the sha1 hash of the content of this storage object
     *
     * @param string $sha1 The sha1 hash
     * @return void
     */
    public function setSha1($sha1);

    /**
     * Returns the md5 hash of the content of this storage object
     *
     * @return string The md5 hash
     * @deprecated since 6.3, will be removed with 7.0 – use getSha1() instead or md5(stream_get_contents($resource->getStream())) if you need an MD5 hash
     */
    public function getMd5();

    /**
     * Sets the md5 hash of the content of this storage object
     *
     * @param string $md5 The md5 hash
     * @return void
     * @deprecated since 6.3, will be removed with 7.0 – @see getMd5()
     */
    public function setMd5($md5);
}
