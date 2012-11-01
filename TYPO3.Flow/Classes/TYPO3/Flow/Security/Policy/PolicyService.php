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

/**
 * The policy service reads the policy configuration. The security adivce asks this service which methods have to be intercepted by a security interceptor.
 * The access decision voters get the roles and privileges configured (in the security policy) for a specific method invocation from this service.
 *
 * @Flow\Scope("singleton")
 */
class PolicyService implements \TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface {

	const
		PRIVILEGE_ABSTAIN = 0,
		PRIVILEGE_GRANT = 1,
		PRIVILEGE_DENY = 2,
		MATCHER_ANY = 'ANY';

	/**
	 * The Flow settings
	 * @var array
	 */
	protected $settings;

	/**
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var array
	 */
	protected $policy = array();

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
	 */
	protected $cache;

	/**
	 * @var \TYPO3\Flow\Security\Policy\PolicyExpressionParser
	 */
	protected $policyExpressionParser;

	/**
	 * All configured resources
	 * @var array
	 */
	protected $resources = array();

	/**
	 * Array of pointcut filters used to match against the configured policy.
	 * @var array
	 */
	public $filters = array();

	/**
	 * A multidimensional array used containing the roles and privileges for each intercepted method
	 * @var array
	 */
	public $acls = array();

	/**
	 * The constraints for entity resources
	 * @var array
	 */
	protected $entityResourcesConstraints = array();

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Injects the Flow settings
	 *
	 * @param array $settings Settings of the Flow package
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Injects the configuration manager
	 *
	 * @param \TYPO3\Flow\Configuration\ConfigurationManager $configurationManager The configuration manager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\Flow\Configuration\ConfigurationManager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Injects the Cache Manager because we cannot inject an automatically factored cache during compile time.
	 *
	 * @param \TYPO3\Flow\Cache\CacheManager $cacheManager
	 * @return void
	 */
	public function injectCacheManager(\TYPO3\Flow\Cache\CacheManager $cacheManager) {
		$this->cache = $cacheManager->getCache('Flow_Security_Policy');
	}

	/**
	 * Injects the policy expression parser
	 *
	 * @param \TYPO3\Flow\Security\Policy\PolicyExpressionParser $parser
	 * @return void
	 */
	public function injectPolicyExpressionParser(\TYPO3\Flow\Security\Policy\PolicyExpressionParser $parser) {
		$this->policyExpressionParser = $parser;
	}

	/**
	 * Injects the object manager
	 *
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\Flow\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Initializes this Policy Service
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->policy = $this->configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY);

		$this->setAclsForEverybodyRole();

		if ($this->cache->has('acls')) {
			$this->acls = $this->cache->get('acls');
		} else {
			$this->parseEntityAcls();
		}

		if ($this->cache->has('entityResourcesConstraints')) {
			$this->entityResourcesConstraints = $this->cache->get('entityResourcesConstraints');
		} else {
			if (array_key_exists('resources', $this->policy) && array_key_exists('entities', $this->policy['resources'])) {
				$this->entityResourcesConstraints = $this->policyExpressionParser->parseEntityResources($this->policy['resources']['entities']);
			}
		}
	}

	/**
	 * Checks if the specified class and method matches against the filter, i.e. if there is a policy entry to intercept this method.
	 * This method also creates a cache entry for every method, to cache the associated roles and privileges.
	 *
	 * @param string $className Name of the class to check the name of
	 * @param string $methodName Name of the method to check the name of
	 * @param string $methodDeclaringClassName Name of the class the method was originally declared in
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the names match, otherwise FALSE
	 * @throws \TYPO3\Flow\Security\Exception\InvalidPrivilegeException
	 */
	public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier) {
		if ($this->settings['security']['enable'] === FALSE) {
			return FALSE;
		}

		if ($this->filters === array()) {
			$this->buildPointcutFilters();
		}

		$matches = FALSE;

		foreach ($this->filters as $role => $filtersForRole) {
			foreach ($filtersForRole as $resource => $filter) {
				if ($filter->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)) {
					$matches = TRUE;
					$methodIdentifier = strtolower($className . '->' . $methodName);

					$policyForJoinPoint = array();
					switch ($this->policy['acls'][$role]['methods'][$resource]) {
						case 'GRANT':
							$policyForJoinPoint['privilege'] = self::PRIVILEGE_GRANT;
							break;
						case 'DENY':
							$policyForJoinPoint['privilege'] = self::PRIVILEGE_DENY;
							break;
						case 'ABSTAIN':
							$policyForJoinPoint['privilege'] = self::PRIVILEGE_ABSTAIN;
							break;
						default:
							throw new \TYPO3\Flow\Security\Exception\InvalidPrivilegeException('Invalid privilege defined in security policy. An ACL entry may have only one of the privileges ABSTAIN, GRANT or DENY, but we got:' . $this->policy['acls'][$role]['methods'][$resource] . ' for role : ' . $role . ' and resource: ' . $resource, 1267308533);
					}

					if ($filter->hasRuntimeEvaluationsDefinition() === TRUE) {
						$policyForJoinPoint['runtimeEvaluationsClosureCode'] = $filter->getRuntimeEvaluationsClosureCode();
					} else {
						$policyForJoinPoint['runtimeEvaluationsClosureCode'] = FALSE;
					}

					$this->acls[$methodIdentifier][$role][$resource] = $policyForJoinPoint;
				}
			}
		}

		return $matches;
	}

	/**
	 * Returns TRUE if this filter holds runtime evaluations for a previously matched pointcut
	 *
	 * @return boolean TRUE if this filter has runtime evaluations
	 */
	public function hasRuntimeEvaluationsDefinition() {
		return FALSE;
	}

	/**
	 * Returns runtime evaluations for the pointcut.
	 *
	 * @return array Runtime evaluations
	 */
	public function getRuntimeEvaluationsDefinition() {
		return array();
	}

	/**
	 * Returns an array of all configured roles
	 *
	 * @return array Array of all configured roles
	 */
	public function getRoles() {
		$roles = array(
			new \TYPO3\Flow\Security\Policy\Role('Everybody'),
			new \TYPO3\Flow\Security\Policy\Role('Anonymous')
		);
		foreach ($this->policy['roles'] as $roleIdentifier => $parentRoles) {
			$roles[] = new \TYPO3\Flow\Security\Policy\Role($roleIdentifier);
		}
		return $roles;
	}

	/**
	 * Returns all parent roles for the given role, that are configured in the policy.
	 *
	 * @param \TYPO3\Flow\Security\Policy\Role $role The role to get the parents for
	 * @return array<TYPO3\Security\Policy\Role> Array of parent roles
	 */
	public function getAllParentRoles(\TYPO3\Flow\Security\Policy\Role $role) {
		$result = array();

		$parentRoles = \TYPO3\Flow\Utility\Arrays::getValueByPath($this->policy, 'roles.' . $role);
		if (is_array($parentRoles)) {
			foreach ($this->policy['roles'][(string)$role] as $currentIdentifier) {
				$currentParent = new \TYPO3\Flow\Security\Policy\Role($currentIdentifier);
				if (!in_array($currentParent, $result)) $result[] = $currentParent;
				foreach ($this->getAllParentRoles($currentParent) as $currentGrandParent) {
					if (!in_array($currentGrandParent, $result)) $result[] = $currentGrandParent;
				}
			}
		}
		return $result;
	}

	/**
	 * Returns the configured roles for the given joinpoint
	 *
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The joinpoint for which the roles should be returned
	 * @return array Array of roles
	 * @throws \TYPO3\Flow\Security\Exception\NoEntryInPolicyException
	 */
	public function getRolesForJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$methodIdentifier = strtolower($joinPoint->getClassName() . '->' . $joinPoint->getMethodName());
		if (!isset($this->acls[$methodIdentifier])) throw new \TYPO3\Flow\Security\Exception\NoEntryInPolicyException('The given joinpoint was not found in the policy cache. Most likely you have to recreate the AOP proxy classes.', 1222084767);

		$roles = array();
		foreach (array_keys($this->acls[$methodIdentifier]) as $roleIdentifier) {
			$roles[] = new \TYPO3\Flow\Security\Policy\Role($roleIdentifier);
		}

		return $roles;
	}

	/**
	 * Returns the privileges a specific role has for the given joinpoint. The returned array
	 * contains the privilege's resource as key of each privilege.
	 *
	 * @param \TYPO3\Flow\Security\Policy\Role $role The role for which the privileges should be returned
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The joinpoint for which the privileges should be returned
	 * @return array Array of privileges
	 * @throws \TYPO3\Flow\Security\Exception\NoEntryInPolicyException
	 */
	public function getPrivilegesForJoinPoint(\TYPO3\Flow\Security\Policy\Role $role, \TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$methodIdentifier = strtolower($joinPoint->getClassName() . '->' . $joinPoint->getMethodName());
		$roleIdentifier = (string)$role;

		if (!isset($this->acls[$methodIdentifier])) throw new \TYPO3\Flow\Security\Exception\NoEntryInPolicyException('The given joinpoint was not found in the policy cache. Most likely you have to recreate the AOP proxy classes.', 1222100851);
		if (!isset($this->acls[$methodIdentifier][$roleIdentifier])) {
			return array();
		}

		$privileges = array();
		foreach ($this->acls[$methodIdentifier][$roleIdentifier] as $resource => $privilegeConfiguration) {
			if ($privilegeConfiguration['runtimeEvaluationsClosureCode'] !== FALSE) {
					// Make object manager usable as closure variable
				$objectManager = $this->objectManager;
				eval('$runtimeEvaluator = ' . $privilegeConfiguration['runtimeEvaluationsClosureCode'] . ';');
				if ($runtimeEvaluator->__invoke($joinPoint) === FALSE) {
					continue;
				}
			}

			$privileges[$resource] = $privilegeConfiguration['privilege'];
		}

		return $privileges;
	}

	/**
	 * Returns the privilege a specific role has for the given resource.
	 * Note: Resources with runtime evaluations return always a PRIVILEGE_DENY!
	 * @see getPrivilegesForJoinPoint() instead, if you need privileges for them.
	 *
	 * @param \TYPO3\Flow\Security\Policy\Role $role The role for which the privileges should be returned
	 * @param string $resource The resource for which the privileges should be returned
	 * @return integer One of: PRIVILEGE_GRANT, PRIVILEGE_DENY
	 * @throws \TYPO3\Flow\Security\Exception\NoEntryInPolicyException
	 */
	public function getPrivilegeForResource(\TYPO3\Flow\Security\Policy\Role $role, $resource) {
		if (!isset($this->acls[$resource])) {
			if (isset($this->resources[$resource])) {
				return self::PRIVILEGE_DENY;
			} else {
				throw new \TYPO3\Flow\Security\Exception\NoEntryInPolicyException('The given resource ("' . $resource . '") was not found in the policy cache. Most likely you have to recreate the AOP proxy classes.', 1248348214);
			}
		}

		$roleIdentifier = (string)$role;
		if (!array_key_exists($roleIdentifier, $this->acls[$resource])) {
			return NULL;
		}

		if ($this->acls[$resource][$roleIdentifier]['runtimeEvaluationsClosureCode'] !== FALSE) {
			return self::PRIVILEGE_DENY;
		}

		return $this->acls[$resource][$roleIdentifier]['privilege'];
	}

	/**
	 * Checks if the given method has a policy entry. If $roles are given
	 * this method returns only TRUE, if there is an acl entry for the method for
	 * at least one of the given roles.
	 *
	 * @param string $className The class name to check the policy for
	 * @param string $methodName The method name to check the policy for
	 * @param array $roles
	 * @return boolean TRUE if the given controller action has a policy entry
	 */
	public function hasPolicyEntryForMethod($className, $methodName, array $roles = array()) {
		$methodIdentifier = strtolower($className . '->' . $methodName);

		if (isset($this->acls[$methodIdentifier])) {
			if (count($roles) > 0) {
				foreach ($roles as $roleIdentifier) {
					if (isset($this->acls[$methodIdentifier][$roleIdentifier])) {
						return TRUE;
					}
				}
			} else {
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Checks if the given entity type has a policy entry for at least one of the given roles
	 *
	 * @param string $entityType The entity type (object name) to be checked
	 * @param array $roles The roles to be checked
	 * @return boolean TRUE if the given entity type has a policy entry
	 */
	public function hasPolicyEntryForEntityType($entityType, array $roles) {
		$entityType = str_replace('\\', '_', $entityType);

		if (isset($this->entityResourcesConstraints[$entityType])) {
			foreach ($this->entityResourcesConstraints[$entityType] as $resource => $constraint) {
				foreach ($roles as $role) {
					if (isset($this->acls[$resource][(string)$role])) {
						return TRUE;
					}
				}
			}
		}

		return FALSE;
	}

	/**
	 * Checks if the given there is any policy entry for entities
	 *
	 * @return boolean TRUE if the a resource entry for entities exist
	 */
	public function hasPolicyEntriesForEntities() {
		return (count($this->entityResourcesConstraints) > 0);
	}

	/**
	 * Returns an array of not GRANTED or explicitly DENIED resource constraints, which are
	 * configured for the given entity type and for at least one of the given roles.
	 * Note: If two roles have conflicting privileges for the same resource the GRANT priviliege
	 * has precedence.
	 *
	 * @param string $entityType The entity type (object name)
	 * @param array $roles An array of roles the resources have to be configured for
	 * @return array An array resource constraints
	 */
	public function getResourcesConstraintsForEntityTypeAndRoles($entityType, array $roles) {
		$deniedResources = array();
		$grantedResources = array();
		$abstainedResources = array();

		$entityType = str_replace('\\', '_', $entityType);

		foreach ($this->entityResourcesConstraints[$entityType] as $resource => $constraint) {
			if ($constraint === self::MATCHER_ANY) {
				continue;
			}

			foreach ($roles as $roleIdentifier) {
				if (!isset($this->acls[$resource][(string)$roleIdentifier]['privilege'])
					|| $this->acls[$resource][(string)$roleIdentifier]['privilege'] === self::PRIVILEGE_ABSTAIN) {

					$abstainedResources[$resource] = $constraint;
				} elseif ($this->acls[$resource][(string)$roleIdentifier]['privilege'] === self::PRIVILEGE_DENY) {
					$deniedResources[$resource] = $constraint;
				} else {
					$grantedResources[] = $resource;
				}
			}
		}

		foreach ($grantedResources as $grantedResource) {
			if (isset($abstainedResources[$grantedResource])) {
				unset($abstainedResources[$grantedResource]);
			}
		}

		return array_merge($abstainedResources, $deniedResources);
	}

	/**
	 * Builds the needed pointcut filters for matching the policy resources
	 *
	 * @return boolean
	 * @throws \TYPO3\Flow\Security\Exception\MissingConfigurationException
	 * @throws \TYPO3\Flow\Security\Exception\InvalidPrivilegeException
	 */
	protected function buildPointcutFilters() {
		if (isset($this->policy['resources']['methods']) === FALSE) {
			return FALSE;
		}

		$parsedMethodResources = array();

		foreach ($this->policy['acls'] as $role => $acl) {
			if (!isset($acl['methods'])) {
				continue;
			}
			if (!is_array($acl['methods'])) {
				throw new \TYPO3\Flow\Security\Exception\MissingConfigurationException('The ACL configuration for role "' . $role . '" on method resources is not correctly defined. Make sure to use the correct syntax in the Policy.yaml files.', 1277383564);
			}
			foreach ($acl['methods'] as $resource => $privilege) {
				if (!isset($parsedMethodResources[$resource])) {
					$resourceTrace = array();
					$parsedMethodResources[$resource]['filters'] = $this->policyExpressionParser->parseMethodResources($resource, $this->policy['resources']['methods'], $resourceTrace);
					$parsedMethodResources[$resource]['trace'] = $resourceTrace;
				}
				$this->filters[$role][$resource] = $parsedMethodResources[$resource]['filters'];

				foreach ($parsedMethodResources[$resource]['trace'] as $currentResource) {
					$policyForResource = array();
					switch ($privilege) {
						case 'GRANT':
							$policyForResource['privilege'] = self::PRIVILEGE_GRANT;
							break;
						case 'DENY':
							$policyForResource['privilege'] = self::PRIVILEGE_DENY;
							break;
						case 'ABSTAIN':
							$policyForResource['privilege'] = self::PRIVILEGE_ABSTAIN;
							break;
						default:
							throw new \TYPO3\Flow\Security\Exception\InvalidPrivilegeException('Invalid privilege defined in security policy. An ACL entry may have only one of the privileges ABSTAIN, GRANT or DENY, but we got "' . $privilege . '" for role "' . $role . '" and resource "' . $resource . '"', 1267311437);
					}

					if ($this->filters[$role][$resource]->hasRuntimeEvaluationsDefinition() === TRUE) {
						$policyForResource['runtimeEvaluationsClosureCode'] = $this->filters[$role][$resource]->getRuntimeEvaluationsClosureCode();
					} else {
						$policyForResource['runtimeEvaluationsClosureCode'] = FALSE;
					}

					$this->acls[$currentResource][$role] = $policyForResource;
				}
			}
		}

		return TRUE;
	}

	/**
	 * Checks if there is a special resource definition covering all objects of the
	 * given type and if this resource has been granted to at least one of the
	 * given roles.
	 *
	 * @param string $entityType The entity type (object name)
	 * @param array $roles An array of roles the resources have to be configured for
	 * @return array TRUE if general access is granted, FALSE otherwise
	 */
	public function isGeneralAccessForEntityTypeGranted($entityType, array $roles) {
		$entityType = str_replace('\\', '_', $entityType);

		$foundGeneralResourceDefinition = FALSE;
		foreach ($this->entityResourcesConstraints[$entityType] as $resource => $constraint) {
			if ($constraint === self::MATCHER_ANY) {
				$foundGeneralResourceDefinition = TRUE;
				$foundGrantPrivilege = FALSE;
				foreach ($roles as $roleIdentifier) {
					if (!isset($this->acls[$resource][(string)$roleIdentifier]['privilege'])) {
						continue;
					} elseif ($this->acls[$resource][(string)$roleIdentifier]['privilege'] === self::PRIVILEGE_DENY) {
						return FALSE;
					} elseif ($this->acls[$resource][(string)$roleIdentifier]['privilege'] === self::PRIVILEGE_GRANT) {
						$foundGrantPrivilege = TRUE;
					}
				}
				if ($foundGrantPrivilege === TRUE) {
					return TRUE;
				}
			}
		}

		if ($foundGeneralResourceDefinition === FALSE) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Parses the policy and stores the configured entity acls in the internal acls array
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception\InvalidPrivilegeException
	 */
	protected function parseEntityAcls() {
		foreach ($this->policy['acls'] as $role => $aclEntries) {
			if (!array_key_exists('entities', $aclEntries)) {
				continue;
			}

			foreach ($aclEntries['entities'] as $resource => $privilege) {
				if (!isset($this->acls[$resource])) {
					$this->acls[$resource] = array();
				}
				$this->acls[$resource][$role] = array();
				switch ($privilege) {
					case 'GRANT':
						$this->acls[$resource][$role]['privilege'] = self::PRIVILEGE_GRANT;
						break;
					case 'DENY':
						$this->acls[$resource][$role]['privilege'] = self::PRIVILEGE_DENY;
						break;
					case 'ABSTAIN':
						$this->acls[$resource][$role]['privilege'] = self::PRIVILEGE_ABSTAIN;
						break;
					default:
						throw new \TYPO3\Flow\Security\Exception\InvalidPrivilegeException('Invalid privilege defined in security policy. An ACL entry may have only one of the privileges ABSTAIN, GRANT or DENY, but we got:' . $privilege . ' for role : ' . $role . ' and resource: ' . $resource, 1267311437);
				}
			}
		}
	}

	/**
	 * Sets the default ACLs for the Everybody role
	 *
	 * @return void
	 */
	protected function setAclsForEverybodyRole() {
		$this->policy['roles']['Everybody'] = array();

		if (!isset($this->policy['acls']['Everybody'])) {
			$this->policy['acls']['Everybody'] = array();
		}
		if (!isset($this->policy['acls']['Everybody']['methods'])) {
			$this->policy['acls']['Everybody']['methods'] = array();
		}
		if (!isset($this->policy['acls']['Everybody']['entities'])) {
			$this->policy['acls']['Everybody']['entities'] = array();
		}

		foreach (array_keys($this->policy['resources']['methods']) as $resource) {
			if (!isset($this->policy['acls']['Everybody']['methods'][$resource])) {
				$this->policy['acls']['Everybody']['methods'][$resource] = 'ABSTAIN';
			}
		}
		foreach ($this->policy['resources']['entities'] as $resourceDefinition) {
			foreach (array_keys($resourceDefinition) as $resource) {
				if (!isset($this->policy['acls']['Everybody']['entities'][$resource])) {
					$this->policy['acls']['Everybody']['entities'][$resource] = 'ABSTAIN';
				}
			}
		}
	}

	/**
	 * Save the found matches to the cache.
	 *
	 * @return void
	 */
	public function savePolicyCache() {
		$tags = array('TYPO3_Flow_Aop');
		if (!$this->cache->has('acls')) {
			$this->cache->set('acls', $this->acls, $tags);
		}
		if (!$this->cache->has('entityResourcesConstraints')) {
			$this->cache->set('entityResourcesConstraints', $this->entityResourcesConstraints);
		}
	}

	/**
	 * This method is used to optimize the matching process.
	 *
	 * @param \TYPO3\Flow\Aop\Builder\ClassNameIndex $classNameIndex
	 * @return \TYPO3\Flow\Aop\Builder\ClassNameIndex
	 */
	public function reduceTargetClassNames(\TYPO3\Flow\Aop\Builder\ClassNameIndex $classNameIndex) {
		if ($this->filters === array()) {
			$this->buildPointcutFilters();
		}

		$result = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();
		foreach ($this->filters as $resources) {
			foreach ($resources as $filterForResource) {
				$result->applyUnion($filterForResource->reduceTargetClassNames($classNameIndex));
			}
		}
		return $result;
	}
}

?>