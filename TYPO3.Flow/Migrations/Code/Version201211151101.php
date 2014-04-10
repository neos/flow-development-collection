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
 * Check for globally defined role identifiers in Policy.yaml files
 */
class Version201211151101 extends AbstractMigration {

	/**
	 * @return void
	 */
	public function up() {
		$policyExaminationResult = array();
		$this->processConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY,
			function ($configuration) use (&$policyExaminationResult) {
				if (!isset($configuration['roles'])) {
					return;
				}
				$localRoles = array();
				foreach ($configuration['roles'] as $roleIdentifier => $roleConfiguration) {
					$localRoles[] = $roleIdentifier;
				}

				foreach ($configuration['roles'] as $roleIdentifier => $roleConfiguration) {
					if (!is_array($roleConfiguration) || $roleConfiguration === array()) {
						continue;
					}
					foreach ($roleConfiguration as $parentRoleIdentifier) {
						if (strpos($parentRoleIdentifier, ':') === FALSE && !in_array($parentRoleIdentifier, $localRoles, TRUE)) {
							$policyExaminationResult[] = '"' . $parentRoleIdentifier . '" is used as parent role for "' . $roleIdentifier . '"';
						}
					}
				}

				foreach ($configuration['acls'] as $roleIdentifier => $acl) {
					if (strpos($roleIdentifier, ':') === FALSE && !in_array($roleIdentifier, $localRoles, TRUE)) {
							$policyExaminationResult[] = '"' . $roleIdentifier . '" is used in ACL definition';
					}
				}

			}
		);
		if ($policyExaminationResult !== array()) {
			$this->showWarning('The Policy.yaml file(s) for this package use roles that are not defined locally. You must prefix them with the source package key.' . PHP_EOL . PHP_EOL . implode('* ' . PHP_EOL, $policyExaminationResult));
		}
	}

}
