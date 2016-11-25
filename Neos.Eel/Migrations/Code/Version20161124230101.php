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
 * Adjusts code to Eel Renaming
 */
class Version20161124230101 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.Eel-20161124230101';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TYPO3\Eel', 'Neos\Eel');
        $this->searchAndReplace('TYPO3.Eel', 'Neos.Eel');
    }
}
