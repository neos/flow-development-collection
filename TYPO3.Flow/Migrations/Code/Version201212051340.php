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

use TYPO3\Flow\Utility\Files;

/**
 * Change entity resource definitions from using _ to \
 */
class Version201212051340 extends AbstractMigration {

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
