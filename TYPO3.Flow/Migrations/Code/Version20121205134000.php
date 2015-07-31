<?php
namespace TYPO3\Flow\Core\Migrations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Change entity resource definitions from using _ to \
 */
class Version20121205134000 extends AbstractMigration {

	/**
	 * NOTE: This method is overridden for historical reasons. Previously code migrations were expected to consist of the
	 * string "Version" and a 12-character timestamp suffix. The suffix has been changed to a 14-character timestamp.
	 * For new migrations the classname pattern should be "Version<YYYYMMDDhhmmss>" (14-character timestamp) and this method should *not* be implemented
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return 'TYPO3.Flow-201212051340';
	}

	/**
	 * @return void
	 */
	public function up() {
		$this->processConfiguration(
			\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY,
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
			TRUE
		);
	}

}
