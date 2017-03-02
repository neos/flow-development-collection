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
 * Adjust to the renaming of the Object namespace in Flow 4.0
 */
class Version20161115140430 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'TYPO3.Flow-20161115140430';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TYPO3\Flow\Object\\', 'TYPO3\Flow\ObjectManagement\\');
    }
}
