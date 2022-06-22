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
 * Adjusts code to FlashMessageContainer renaming from "\Neos\Flow\Mvc" to "\Neos\Flow\Mvc\FlashMessage".
 *
 */
class Version20190425144900 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getIdentifier()
    {
        return 'Neos.Flow-20190425144900';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('Neos\Flow\Mvc\FlashMessageContainer', 'Neos\Flow\Mvc\FlashMessage\FlashMessageContainer');
    }
}
