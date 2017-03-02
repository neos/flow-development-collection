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
class Version20161125110814 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.Kickstarter-20161125110814';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('Neos\Kickstart', 'Neos\Kickstarter');
        $this->searchAndReplace('Neos.Kickstart', 'Neos.Kickstarter');
    }
}
