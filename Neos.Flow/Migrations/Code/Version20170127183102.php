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
 * Migrate bootstep names.
 */
class Version20170127183102 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getIdentifier()
    {
        return 'Neos.Flow-20170127183102';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('typo3.flow:annotationregistry', 'neos.flow:annotationregistry', ['php']);
        $this->searchAndReplace('typo3.flow:configuration', 'neos.flow:configuration', ['php']);
        $this->searchAndReplace('typo3.flow:systemlogger', 'neos.flow:systemlogger', ['php']);
        $this->searchAndReplace('typo3.flow:errorhandling', 'neos.flow:errorhandling', ['php']);
        $this->searchAndReplace('typo3.flow:cachemanagement', 'neos.flow:cachemanagement', ['php']);
        $this->searchAndReplace('typo3.flow:cachemanagement:forceflush', 'neos.flow:cachemanagement:forceflush', ['php']);
        $this->searchAndReplace('typo3.flow:objectmanagement:compiletime:create', 'neos.flow:objectmanagement:compiletime:create', ['php']);
        $this->searchAndReplace('typo3.flow:systemfilemonitor', 'neos.flow:systemfilemonitor', ['php']);
        $this->searchAndReplace('typo3.flow:reflectionservice', 'neos.flow:reflectionservice', ['php']);
        $this->searchAndReplace('typo3.flow:objectmanagement:compiletime:finalize', 'neos.flow:objectmanagement:compiletime:finalize', ['php']);
        $this->searchAndReplace('typo3.flow:objectmanagement:proxyclasses', 'neos.flow:objectmanagement:proxyclasses', ['php']);
        $this->searchAndReplace('typo3.flow:classloader:cache', 'neos.flow:classloader:cache', ['php']);
        $this->searchAndReplace('typo3.flow:objectmanagement:runtime', 'neos.flow:objectmanagement:runtime', ['php']);
        $this->searchAndReplace('typo3.flow:resources', 'neos.flow:resources', ['php']);
        $this->searchAndReplace('typo3.flow:session', 'neos.flow:session', ['php']);
    }
}
