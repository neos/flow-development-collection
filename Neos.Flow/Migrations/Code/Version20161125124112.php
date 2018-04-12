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
 * Adjusts code to Neos\Flow\Utility\Unicode adjustment
 */
class Version20161125124112 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.Flow-20161125124112';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('Neos\Flow\Utility\Unicode', 'Neos\Utility\Unicode');
    }
}
