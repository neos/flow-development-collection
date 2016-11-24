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
 * Replace DataNotSerializeableException with DataNotSerializableException.
 */
class Version20120627112800 extends AbstractMigration
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
        return 'TYPO3.FLOW3-201206271128';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('Session\Exception\DataNotSerializeableException', 'Session\Exception\DataNotSerializableException');
    }
}
