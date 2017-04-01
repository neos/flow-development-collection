<?php
namespace TYPO3\Flow\Utility;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Functions for determining the mime and media types from filenames
 *
 * Deprecated - please use TYPO3\Flow\Utility\MediaTypes instead.
 *
 * @deprecated since 1.1.0
 */
class FileTypes
{
    /**
     * Returns a mime type based on the filename extension
     *
     * @param  string $filename Filename to determine the mime type for
     * @return string
     * @deprecated since 1.1.0
     */
    public static function getMimeTypeFromFilename($filename)
    {
        return MediaTypes::getMediaTypeFromFilename($filename);
    }

    /**
     * Returns a filename extension (aka "format") based on the given mime type.
     *
     * @param string $mimeType Mime type
     * @return string filename extension
     * @deprecated since 1.1.0
     */
    public static function getFilenameExtensionFromMimeType($mimeType)
    {
        return MediaTypes::getFilenameExtensionFromMediaType($mimeType);
    }

    /**
     * Returns a media type based on the filename extension
     *
     * @param  string $filename Filename to determine the media type for
     * @return string
     * @deprecated since 1.1.0
     */
    public static function getMediaTypeFromFilename($filename)
    {
        return MediaTypes::getMediaTypeFromFilename($filename);
    }
}
