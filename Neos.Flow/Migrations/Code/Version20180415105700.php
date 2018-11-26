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
 * Add scalar type hint to CacheAwareInterface implementations.
 */
class Version20180415105700 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getIdentifier()
    {
        return 'Neos.Flow-20180415105700';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplaceRegex('~(CacheAwareInterface.*public function getCacheEntryIdentifier\\(\\))([^{:]*{)~s', '${1}: string${2}', ['php']);
    }
}
