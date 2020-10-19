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
 * Make default ValueObjects embedded=false
 */
class Version20201003165200 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getIdentifier()
    {
        return 'Neos.Flow-20201003165200';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplaceRegex('~(@Flow\\\\ValueObject)(?:([^(]*)|\(\s*\))$~mi', '${1}(embedded=false)${2}', ['php']);
    }
}
