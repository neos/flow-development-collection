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
 * Change entity resource definitions from using _ to \
 */
class Version20121205134000 extends AbstractMigration
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
        return 'TYPO3.Flow-201212051340';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->processConfiguration(
            \Neos\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY,
            function (&$configuration) {
                if (isset($configuration['resources']['entities'])) {
                    $updatedResourcesEntities = array();
                    foreach ($configuration['resources']['entities'] as $entityType => $entityConfiguration) {
                        $entityType = str_replace('_', '\\', $entityType);
                        $updatedResourcesEntities[$entityType] = $entityConfiguration;
                    }
                    $configuration['resources']['entities'] = $updatedResourcesEntities;
                }
            },
            true
        );
    }
}
