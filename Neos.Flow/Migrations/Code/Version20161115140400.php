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
 * Adjust to the renaming of the Resource namespace and class in Flow 4.0
 */
class Version20161115140400 extends AbstractMigration
{
    public function getIdentifier()
    {
        return 'TYPO3.Flow-20161115140400';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->searchAndReplace('TYPO3\Flow\Resource', 'TYPO3\Flow\ResourceManagement');
        $this->searchAndReplaceRegex('/ResourceManagement\\\\Resource(?![a-zA-Z])/', 'ResourceManagement\\PersistentResource');
        $this->searchAndReplaceRegex('/(?<![a-zA-Z])Resource::class/', 'PersistentResource::class');

        $this->searchAndReplaceRegex('/ResourceManagement\\\\Storage\\\\Object/', 'ResourceManagement\\Storage\\StorageObject');
        $this->searchAndReplace('Storage\Object::class', 'Storage\StorageObject::class');

        $this->showWarning('Standalone uses of "Resource" or (Storage) "Object" are hard to adjust automatically, so you might need to do some more adjustments.');
    }
}
