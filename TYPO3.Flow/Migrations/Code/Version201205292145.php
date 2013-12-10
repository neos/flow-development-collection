<?php
namespace TYPO3\Flow\Core\Migrations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Replace FileTypes use with MediaTypes use.
 */
class Version201205292145 extends AbstractMigration {

	/**
	 * Returns the identifier of this migration.
	 *
	 * Hardcoded to be stable after the rename to TYPO3 Flow.
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return 'TYPO3.FLOW3-201205292145';
	}

	/**
	 * @return void
	 */
	public function up() {
		$this->searchAndReplace('FileTypes::getMimeTypeFromFilename', 'MediaTypes::getMediaTypeFromFilename');
		$this->searchAndReplace('FileTypes::getFilenameExtensionFromMimeType', 'MediaTypes::getFilenameExtensionFromMediaType');
		$this->searchAndReplace('FileTypes::getMediaTypeFromFilename', 'MediaTypes::getMediaTypeFromFilename');

		// Resource has been changed as well.
		$this->searchAndReplace('getMimeType()', 'getMediaType()');
	}

}
