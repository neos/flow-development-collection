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
class Version20121115110100 extends AbstractMigration {

	/**
	 * NOTE: This method is overridden for historical reasons. Previously code migrations were expected to consist of the
	 * string "Version" and a 12-character timestamp suffix. The suffix has been changed to a 14-character timestamp.
	 * For new migrations the classname pattern should be "Version<YYYYMMDDhhmmss>" (14-character timestamp) and this method should *not* be implemented
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return 'TYPO3.Flow-201211151101';
	}

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
						if (!is_string($parentRoleIdentifier)) {
							continue;
						}
						if (strpos($parentRoleIdentifier, ':') === FALSE && !in_array($parentRoleIdentifier, $localRoles, TRUE)) {
							$policyExaminationResult[] = '"' . $parentRoleIdentifier . '" is used as parent role for "' . $roleIdentifier . '"';
						}
					}
				}

				if (!isset($configuration['acls']) || !is_array($configuration['acls'])) {
					return;
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
