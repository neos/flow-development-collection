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
 * Adjusts code to cache extraction
 */
class Version20161124224015 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.Flow-20161124224015';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('Neos\Flow\Cache\Frontend', 'Neos\Cache\Frontend');
        // HINT: backend will only be replaced in configuration files; because the *code* API changed and must be adjusted manually if people wrote own cache backends.
        $this->searchAndReplace('Neos\Flow\Cache\Backend', 'Neos\Cache\Backend', ['yaml']);

        $this->searchAndReplace('Neos\Flow\Cache\CacheAwareInterface', 'Neos\Cache\CacheAwareInterface');
    }
}
