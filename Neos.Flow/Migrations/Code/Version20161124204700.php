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

use Neos\Flow\Configuration\ConfigurationManager;

/**
 * Adjusts code to package renaming from "TYPO3.Flow" to "Neos.Flow"
 *
 * TODO: Roles are not yet adjusted in Flow core; but they are already replaced here!!
 */
class Version20161124204700 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.Flow-20161124204700';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TYPO3\Flow', 'Neos\Flow');
        $this->searchAndReplace('TYPO3.Flow', 'Neos.Flow');
        $this->searchAndReplace('typo3/flow', 'neos/flow');
        $this->searchAndReplace('typo3-flow-framework', 'neos-framework');
        $this->searchAndReplace('typo3-flow-package', 'neos-package');
        $this->searchAndReplace('typo3-flow-site', 'neos-site');
        $this->searchAndReplace('typo3-flow-plugin', 'neos-plugin');

        $this->moveSettingsPaths('TYPO3.Flow', 'Neos.Flow');
    }
}
