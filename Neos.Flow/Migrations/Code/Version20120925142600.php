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
 * Rename FLOW3 to TYPO3 Flow
 */
class Version20120925142600 extends AbstractMigration
{
    /**
     * NOTE: This method is overridden for historical reasons. Previously code migrations were expected to consist of the
     * string "Version" and a 12-character timestamp suffix. The suffix has been changed to a 14-character timestamp.
     * For new migrations the classname pattern should be "Version<YYYYMMDDhhmmss>" (14-character timestamp) and this method should *not* be implemented
     *
     * @return string
     */
    public function getIdentifier()
    {
        return 'TYPO3.Flow-201209251426';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TYPO3.FLOW3', 'TYPO3.Flow');
        $this->searchAndReplace('TYPO3\FLOW3', 'Neos\Flow');

        $this->searchAndReplace('FLOW3_PATH_FLOW3', 'FLOW_PATH_FLOW');
        $this->searchAndReplace('FLOW3_PATH', 'FLOW_PATH');
        $this->searchAndReplace('FLOW3_ROOTPATH', 'FLOW_ROOTPATH');
        $this->searchAndReplace('FLOW3_CONTEXT', 'FLOW_CONTEXT');
        $this->searchAndReplace('FLOW3_SAPITYPE', 'FLOW_SAPITYPE');
        $this->searchAndReplace('FLOW3_WEBPATH', 'FLOW_WEBPATH');

        $this->searchAndReplace('as FLOW3;', 'as Flow;');
        $this->searchAndReplace('@FLOW3\\', '@Flow\\');

        $this->searchAndReplace('typo3/flow3', 'typo3/flow', array('json'));

        $this->showNote('You should check the changes this migration applied. Feel free to beautify the file docblock headers and make sure to check for leftover "FLOW3" and "flow3" use.');
        $this->showWarning('In schema migrations that existed prior to this, do not replace "flow3" by "flow"!');
    }
}
