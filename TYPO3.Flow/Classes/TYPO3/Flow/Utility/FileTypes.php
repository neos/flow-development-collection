<?php
namespace TYPO3\Flow\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Utility\MediaTypes;

/**
 * Functions for determining the mime and media types from filenames
 *
 * Deprecated - please use TYPO3\Flow\Utility\MediaTypes instead.
 *
 * @deprecated since 1.1.0
 */
class FileTypes {

	/**
	 * Returns a mime type based on the filename extension
	 *
	 * @param  string $filename Filename to determine the mime type for
	 * @return string
	 * @deprecated since 1.1.0
	 */
	static public function getMimeTypeFromFilename($filename) {
		return MediaTypes::getMediaTypeFromFilename($filename);
	}

	/**
	 * Returns a filename extension (aka "format") based on the given mime type.
	 *
	 * @param string $mimeType Mime type
	 * @return string filename extension
	 * @deprecated since 1.1.0
	 */
	static public function getFilenameExtensionFromMimeType($mimeType) {
		return MediaTypes::getFilenameExtensionFromMediaType($mimeType);
	}

	/**
	 * Returns a media type based on the filename extension
	 *
	 * @param  string $filename Filename to determine the media type for
	 * @return string
	 * @deprecated since 1.1.0
	 */
	static public function getMediaTypeFromFilename($filename) {
		return MediaTypes::getMediaTypeFromFilename($filename);
	}
}
