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
 * Adjusts code to Kickstarter Renaming
 */
class Version20161124230102 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.Kickstart-20161124230102';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TYPO3\Kickstart', 'Neos\Kickstart');
        $this->searchAndReplace('TYPO3.Kickstart', 'Neos.Kickstart');
    }
}
