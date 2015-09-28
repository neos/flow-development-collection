<?php
namespace TYPO3\Flow\Core\Migrations;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Replace FileTypes use with MediaTypes use.
 */
class Version20120529214500 extends AbstractMigration
{
    /**
     * Returns the identifier of this migration.
     *
     * Hardcoded to be stable after the rename to TYPO3 Flow.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return 'TYPO3.FLOW3-201205292145';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('FileTypes::getMimeTypeFromFilename', 'MediaTypes::getMediaTypeFromFilename');
        $this->searchAndReplace('FileTypes::getFilenameExtensionFromMimeType', 'MediaTypes::getFilenameExtensionFromMediaType');
        $this->searchAndReplace('FileTypes::getMediaTypeFromFilename', 'MediaTypes::getMediaTypeFromFilename');

        // Resource has been changed as well.
        $this->searchAndReplace('getMimeType()', 'getMediaType()');
    }
}
