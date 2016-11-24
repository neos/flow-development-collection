<?php
namespace Neos\Flow\Core\Migrations;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
