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

use Neos\Flow\Configuration\ConfigurationManager;

/**
 * Adjusts code to package renaming from "Neos.Flow.Utility.Files" to "Neos.Utility.Files" and other extractions of the "Utility" packages.
 */
class Version20161124204701 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.Flow-20161124204701';
    }

    /**
     * @return void
     */
    public function up()
    {
        // Arrays
        $this->searchAndReplace('Neos\Flow\Utility\Arrays', 'Neos\Utility\Arrays');
        $this->searchAndReplace('Neos\Flow\Utility\PositionalArraySorter', 'Neos\Utility\PositionalArraySorter');
        $this->searchAndReplace('Neos\Flow\Utility\Exception\InvalidPositionException', 'Neos\Utility\Exception\InvalidPositionException');
        $this->searchAndReplace('Neos\Flow\Utility\Exception\InvalidPositionException', 'Neos\Utility\Exception\InvalidPositionException');

        // Files
        $this->searchAndReplace('Neos\Flow\Utility\Files', 'Neos\Utility\Files');
        $this->searchAndReplace('Neos\Flow\Utility\Exception\FilesException', 'Neos\Utility\Exception\FilesException');

        // Lock
        $this->searchAndReplace('Neos\Flow\Utility\Lock', 'Neos\Utility\Lock');

        // MediaTypes
        $this->searchAndReplace('Neos\Flow\Utility\MediaTypes', 'Neos\Utility\MediaTypes');

        // ObjectHandling
        $this->searchAndReplace('Neos\Flow\Reflection\ObjectAccess', 'Neos\Utility\ObjectAccess');
        $this->searchAndReplace('Neos\Flow\Utility\TypeHandling', 'Neos\Utility\TypeHandling');
        $this->searchAndReplace('Neos\Flow\Utility\Exception\InvalidTypeException', 'Neos\Utility\Exception\InvalidTypeException');
        $this->searchAndReplace('Neos\Flow\Reflection\Exception\PropertyNotAccessibleException', 'Neos\Utility\Exception\PropertyNotAccessibleException');

        // OpcodeCache
        $this->searchAndReplace('Neos\Flow\Utility\OpcodeCacheHelper', 'Neos\Utility\OpcodeCacheHelper');

        // PdoHelper
        $this->searchAndReplace('Neos\Flow\Utility\PdoHelper', 'Neos\Utility\PdoHelper');

        // Schema
        $this->searchAndReplace('Neos\Flow\Utility\SchemaGenerator', 'Neos\Utility\SchemaGenerator');
        $this->searchAndReplace('Neos\Flow\Utility\SchemaValidator', 'Neos\Utility\SchemaValidator');

        // Unicode -- WRONG - is fixed in a later core migration
        $this->searchAndReplace('Neos\Flow\Error\Unicode', 'Neos\Utility\Unicode');

        // Error
        $this->searchAndReplace('Neos\Flow\Error\Error', 'Neos\Error\Messages\Error');
        $this->searchAndReplace('Neos\Flow\Error\Message', 'Neos\Error\Messages\Message');
        $this->searchAndReplace('Neos\Flow\Error\Notice', 'Neos\Error\Messages\Notice');
        $this->searchAndReplace('Neos\Flow\Error\Result', 'Neos\Error\Messages\Result');
        $this->searchAndReplace('Neos\Flow\Error\Warning', 'Neos\Error\Messages\Warning');
    }
}
