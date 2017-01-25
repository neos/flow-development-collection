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
 * Migrate usages of the path [TYPO3][Flow][Security][Authentication] to [Neos][Flow][Security][Authentication]
 */
class Version20170125103800 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getIdentifier()
    {
        return 'Neos.Flow-20170125103800';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('[TYPO3][Flow][Security][Authentication]', '[Neos][Flow][Security][Authentication]', ['php', 'ts2', 'fusion', 'js', 'json', 'html']);
        $this->searchAndReplace('[\'TYPO3\'][\'Flow\'][\'Security\'][\'Authentication\']', '[\'Neos\'][\'Flow\'][\'Security\'][\'Authentication\']', ['php', 'ts2', 'fusion', 'js', 'json', 'html']);
        $this->searchAndReplace('["TYPO3"]["Flow"]["Security"]["Authentication"]', '["Neos"]["Flow"]["Security"]["Authentication"]', ['php', 'ts2', 'fusion', 'js', 'json', 'html']);
        $this->searchAndReplace('TYPO3.Flow.Security.Authentication', 'TYPO3.Flow.Security.Authentication', ['php', 'ts2', 'fusion', 'js', 'json', 'html']);
    }
}
