<?php
namespace TYPO3\Flow\Core\Migrations;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
