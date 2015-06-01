<?php
namespace TYPO3\Flow\Security\Policy;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Security\Authorization\Privilege\Parameter\PrivilegeParameterDefinition;
use TYPO3\Flow\Security\Authorization\Privilege\PrivilegeTarget;
use TYPO3\Flow\Security\Exception\NoSuchRoleException;
use TYPO3\Flow\Security\Exception as SecurityException;
use TYPO3\Flow\Security\Authorization\Privilege\PrivilegeInterface;

/**
 * The policy service reads the policy configuration. The security advice asks
 * this service which methods have to be intercepted by a security interceptor.
 *
 * The access decision voters get the roles and privileges configured (in the
 * security policy) for a specific method invocation from this service.
 *
 * @Flow\Scope("singleton")
 */
class PolicyService {

	/**
	 * @var boolean
	 */
	protected $initialized = FALSE;

	/**
	 * @var ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var array
	 */
	protected $policyConfiguration;

	/**
	 * @var PrivilegeTarget[]
	 */
	protected $privilegeTargets = array();

	/**
	 * @var Role[]
	 */
	protected $roles = array();

	/**
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * This object is created very early so we can't rely on AOP for the property injection
	 *
	 * @param ConfigurationManager $configurationManager The configuration manager
	 * @return void
	 */
	public function injectConfigurationManager(ConfigurationManager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * This object is created very early so we can't rely on AOP for the property injection
	 *
	 * @param ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Parses the global policy configuration and initializes roles and privileges accordingly
	 *
	 * @return void
	 * @throws SecurityException
	 */
	protected function initialize() {
		if ($this->initialized) {
			return;
		}

		$this->policyConfiguration = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_POLICY);
		$this->emitConfigurationLoaded($this->policyConfiguration);

		$this->initializePrivilegeTargets();

		$uncoveredPrivilegeTargets = $this->privilegeTargets;

		$this->roles = array();
		$everybodyRole = new Role('TYPO3.Flow:Everybody');
		$everybodyRole->setAbstract(TRUE);
		if (isset($this->policyConfiguration['roles'])) {
			foreach ($this->policyConfiguration['roles'] as $roleIdentifier => $roleConfiguration) {

				if ($roleIdentifier === 'TYPO3.Flow:Everybody') {
					$role = $everybodyRole;
				} else {
					$role = new Role($roleIdentifier);
					if (isset($roleConfiguration['abstract'])) {
						$role->setAbstract((boolean)$roleConfiguration['abstract']);
					}
				}

				if (isset($roleConfiguration['privileges'])) {
					foreach ($roleConfiguration['privileges'] as $privilegeConfiguration) {
						$privilegeTargetIdentifier = $privilegeConfiguration['privilegeTarget'];
						if (!isset($this->privilegeTargets[$privilegeTargetIdentifier])) {
							throw new SecurityException(sprintf('privilege target "%s", referenced in role configuration "%s" is not defined!', $privilegeTargetIdentifier, $roleIdentifier), 1395869320);
						}
						$privilegeTarget = $this->privilegeTargets[$privilegeTargetIdentifier];
						if (!isset($privilegeConfiguration['permission'])) {
							throw new SecurityException(sprintf('No permission set for privilegeTarget "%s" in Role "%s"', $privilegeTargetIdentifier, $roleIdentifier), 1395869331);
						}
						$privilegeParameters = isset($privilegeConfiguration['parameters']) ? $privilegeConfiguration['parameters'] : array();
						try {
							$privilege = $privilegeTarget->createPrivilege($privilegeConfiguration['permission'], $privilegeParameters);
						} catch (\Exception $exception) {
							throw new SecurityException(sprintf('Error for privilegeTarget "%s" in Role "%s": %s', $privilegeTargetIdentifier, $roleIdentifier, $exception->getMessage()), 1401886654, $exception);
						}
						$role->addPrivilege($privilege);

						if ($roleIdentifier !== 'TYPO3.Flow:Everybody') {
							$everybodyRole->addPrivilege($privilegeTarget->createPrivilege(PrivilegeInterface::ABSTAIN, $privilegeParameters));
						}
						unset($uncoveredPrivilegeTargets[$privilegeTargetIdentifier]);
					}
				}

				$this->roles[$roleIdentifier] = $role;
			}
		}

		// create ABSTAIN privilege for all uncovered privilegeTargets
		/** @var PrivilegeTarget $privilegeTarget */
		foreach ($uncoveredPrivilegeTargets as $privilegeTarget) {
			if ($privilegeTarget->hasParameters()) {
				continue;
			}
			$everybodyRole->addPrivilege($privilegeTarget->createPrivilege(PrivilegeInterface::ABSTAIN));
		}
		$this->roles['TYPO3.Flow:Everybody'] = $everybodyRole;

		// Set parent roles
		/** @var Role $role */
		foreach ($this->roles as $role) {
			if (isset($this->policyConfiguration['roles'][$role->getIdentifier()]['parentRoles'])) {
				foreach ($this->policyConfiguration['roles'][$role->getIdentifier()]['parentRoles'] as $parentRoleIdentifier) {
					$role->addParentRole($this->roles[$parentRoleIdentifier]);
				}
			}
		}

		$this->emitRolesInitialized($this->roles);

		$this->initialized = TRUE;
	}

	/**
	 * Initialized all configured privilege targets from the policy definitions
	 *
	 * @return void
	 * @throws SecurityException
	 */
	protected function initializePrivilegeTargets() {
		if (!isset($this->policyConfiguration['privilegeTargets'])) {
			return;
		}
		foreach ($this->policyConfiguration['privilegeTargets'] as $privilegeClassName => $privilegeTargetsConfiguration) {
			foreach ($privilegeTargetsConfiguration as $privilegeTargetIdentifier => $privilegeTargetConfiguration) {
				if (!isset($privilegeTargetConfiguration['matcher'])) {
					throw new SecurityException(sprintf('No "matcher" configured for privilegeTarget "%s"', $privilegeTargetIdentifier), 1401795388);
				}
				$parameterDefinitions = array();
				$privilegeParameterConfiguration = isset($privilegeTargetConfiguration['parameters']) ? $privilegeTargetConfiguration['parameters'] : array();
				foreach ($privilegeParameterConfiguration as $parameterName => $parameterValue) {
					if (!isset($privilegeTargetConfiguration['parameters'][$parameterName])) {
						throw new SecurityException(sprintf('No parameter definition found for parameter "%s" in privilegeTarget "%s"', $parameterName, $privilegeTargetIdentifier), 1395869330);
					}
					if (!isset($privilegeTargetConfiguration['parameters'][$parameterName]['className'])) {
						throw new SecurityException(sprintf('No "className" defined for parameter "%s" in privilegeTarget "%s"', $parameterName, $privilegeTargetIdentifier), 1396021782);
					}
					$parameterDefinitions[$parameterName] = new PrivilegeParameterDefinition($parameterName, $privilegeTargetConfiguration['parameters'][$parameterName]['className']);
				}
				$privilegeTarget = new PrivilegeTarget($privilegeTargetIdentifier, $privilegeClassName, $privilegeTargetConfiguration['matcher'], $parameterDefinitions);
				$privilegeTarget->injectObjectManager($this->objectManager);
				$this->privilegeTargets[$privilegeTargetIdentifier] = $privilegeTarget;
			}
		}
	}

	/**
	 * Checks if a role exists
	 *
	 * @param string $roleIdentifier The role identifier, format: (<PackageKey>:)<Role>
	 * @return boolean
	 */
	public function hasRole($roleIdentifier) {
		$this->initialize();
		return isset($this->roles[$roleIdentifier]);
	}

	/**
	 * Returns a Role object configured in the PolicyService
	 *
	 * @param string $roleIdentifier The role identifier of the role, format: (<PackageKey>:)<Role>
	 * @return Role
	 * @throws NoSuchRoleException
	 */
	public function getRole($roleIdentifier) {
		if ($this->hasRole($roleIdentifier)) {
			return $this->roles[$roleIdentifier];
		}
		throw new NoSuchRoleException();
	}

	/**
	 * Returns an array of all configured roles
	 *
	 * @param boolean $includeAbstract If TRUE the result includes abstract roles, otherwise those will be skipped
	 * @return Role[] Array of all configured roles, indexed by role identifier
	 */
	public function getRoles($includeAbstract = FALSE) {
		$this->initialize();
		if (!$includeAbstract) {
			return array_filter($this->roles, function (Role $role) {
				return $role->isAbstract() !== TRUE;
			});
		}
		return $this->roles;
	}

	/**
	 * Returns all privileges of the given type
	 *
	 * @param string $type Full qualified class or interface name
	 * @return array
	 */
	public function getAllPrivilegesByType($type) {
		$this->initialize();
		$privileges = array();
		foreach ($this->roles as $role) {
			$privileges = array_merge($privileges, $role->getPrivilegesByType($type));
		}
		return $privileges;
	}

	/**
	 * Returns all configured privilege targets
	 *
	 * @return PrivilegeTarget[]
	 */
	public function getPrivilegeTargets() {
		$this->initialize();
		return $this->privilegeTargets;
	}

	/**
	 * Returns the privilege target identified by the given string
	 *
	 * @param string $privilegeTargetIdentifier Identifier of a privilege target
	 * @return PrivilegeTarget
	 */
	public function getPrivilegeTargetByIdentifier($privilegeTargetIdentifier) {
		$this->initialize();
		return isset($this->privilegeTargets[$privilegeTargetIdentifier]) ? $this->privilegeTargets[$privilegeTargetIdentifier] : NULL;
	}

	/**
	 * Resets the PolicyService to behave transparently during
	 * functional testing.
	 *
	 * @return void
	 */
	public function reset() {
		$this->initialized = FALSE;
		$this->roles = array();
	}

	/**
	 * Emits a signal when the policy configuration has been loaded
	 *
	 * This signal can be used to add roles and/or privilegeTargets during runtime. In the slot make sure to receive the
	 * $policyConfiguration array by reference so you can alter it.
	 *
	 * @param array $policyConfiguration The policy configuration
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitConfigurationLoaded(array &$policyConfiguration) {}

	/**
	 * Emits a signal when roles have been initialized
	 *
	 * This signal can be used to register roles during runtime. In the slot make sure to receive the $roles array by
	 * reference so you can alter it.
	 *
	 * @param array<Role> $roles All initialized roles (even abstract roles)
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitRolesInitialized(array &$roles) {}
}
