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
 * Check for globally defined role identifiers in Policy.yaml files
 */
class Version20121115110100 extends AbstractMigration
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
        return 'TYPO3.Flow-201211151101';
    }

    /**
     * @return void
     */
    public function up()
    {
        $policyExaminationResult = array();
        $this->processConfiguration(\Neos\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY,
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
                        if (strpos($parentRoleIdentifier, ':') === false && !in_array($parentRoleIdentifier, $localRoles, true)) {
                            $policyExaminationResult[] = '"' . $parentRoleIdentifier . '" is used as parent role for "' . $roleIdentifier . '"';
                        }
                    }
                }

                if (!isset($configuration['acls']) || !is_array($configuration['acls'])) {
                    return;
                }
                foreach ($configuration['acls'] as $roleIdentifier => $acl) {
                    if (strpos($roleIdentifier, ':') === false && !in_array($roleIdentifier, $localRoles, true)) {
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
