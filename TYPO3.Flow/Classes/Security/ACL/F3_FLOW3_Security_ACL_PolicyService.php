<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Security::ACL;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 */

/**
 * The policy service reads the policy configuration. The security adivce asks this service which methods have to be intercepted by a security interceptor.
 * The access decision voters get the roles and privileges configured (in the security policy) for a specific method invocation from this service.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class PolicyService implements F3::FLOW3::AOP::PointcutFilterInterface {

	/**
	 * @var F3::FLOW3::Component::FactoryInterface $componentFactory The component factory
	 */
	protected $componentFactory = NULL;

	/**
	 * @var F3::FLOW3::Configuration::Container The policy configuration
	 */
	protected $configuration = NULL;

	/**
	 * @var F3::FLOW3::Cache::AbstractCache A reference to the cache factory
	 */
	protected $cacheFactory;

	/**
	 * @var F3::FLOW3::Cache::AbstractCache The cached acl entries
	 */
	protected $aclCache;

	/**
	 * @var array The roles tree array
	 */
	protected $roles = array();

	/**
	 * @var array Array of pointcut filters used to match against the configured policy.
	 */
	public $filters = array();

	/**
	 * @var array A multidimensional array used containing the roles and privileges for each intercepted method
	 */
	public $acls = array();

	/**
	 * Constructor.
	 *
	 * @param F3::FLOW3::Component::FactoryInterface $componentFactory The component factory
	 * @param F3::FLOW3::Configuration::Manager $configurationManager The configuration manager
	 * @param F3::FLOW3::Cache::Factory $cacheFactory The cache factory
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo cache the whole thing, if aop proxy cache is enabled
	 */
	public function __construct(F3::FLOW3::Component::FactoryInterface $componentFactory, F3::FLOW3::Configuration::Manager $configurationManager, F3::FLOW3::Cache::Factory $cacheFactory) {
		$this->cacheFactory = $cacheFactory;
		$this->componentFactory = $componentFactory;
		$this->configuration = $configurationManager->getSettings('FLOW3');

		$this->roles = $this->configuration->security->policy->roles;

		if ($this->configuration->aop->proxyCache->enable) {
			$this->aclCache = $this->cacheFactory->create('FLOW3_Security_Policy_ACLs', 'F3::FLOW3::Cache::VariableCache', $this->configuration->security->policy->aclCache->backend, $this->configuration->security->policy->aclCache->backendOptions);
			if ($this->aclCache->has('FLOW3_Security_Policy_ACLs')) $this->acls = $this->aclCache->load('FLOW3_Security_Policy_ACLs');
		}
	}

	/**
	 * Checks if the specified class and method matches against the filter, i.e. if there is a policy entry to intercept this method.
	 * This method also creates a cache entry for every method, to cache the associated roles and privileges.
	 *
	 * @param F3::FLOW3::Reflection::ClassReflection $class The class to check the name of
	 * @param F3::FLOW3::Reflection::MethodReflection $method The method to check the name of
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the names match, otherwise FALSE
	 */
	public function matches(F3::FLOW3::Reflection::ClassReflection $class, F3::FLOW3::Reflection::MethodReflection $method, $pointcutQueryIdentifier) {
		$matches = FALSE;

		if (count($this->filters) === 0) {
			$policyExpressionParser = $this->createPolicyExpressionParser();
			$policyExpressionParser->setResourcesTree($this->configuration->security->policy->resources);
			foreach ($this->configuration->security->policy->acls as $role => $acl) {
				foreach ($acl as $resource => $privilege) $this->filters[$role][$resource] = $policyExpressionParser->parse($resource);
			}
		}

		foreach ($this->filters as $role => $filtersForRole) {
			foreach ($filtersForRole as $resource => $filter) {
				if ($filter->matches($class, $method, $pointcutQueryIdentifier)) {
					$methodIdentifier = $class->getName() . '->' . $method->getName();
					$this->acls[$methodIdentifier][$role][] = $this->configuration->security->policy->acls[$role][$resource];
					$matches = TRUE;
				}
			}
		}

		if ($this->configuration->aop->proxyCache->enable) {
			$tags = array('F3_FLOW3_AOP');
			$this->aclCache->save('FLOW3_Security_Policy_ACLs', $this->acls, $tags);
		}

		return $matches;
	}

	/**
	 * Returns the configured roles for the given joinpoint
	 *
	 * @param F3::FLOW3::AOP::JoinPointInterface $joinPoint The joinpoint for which the roles should be returned
	 * @return array Array of roles
	 * @throws F3::FLOW3::Security::Exception::NoEntryInPolicy
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRoles(F3::FLOW3::AOP::JoinPointInterface $joinPoint) {
		$methodIdentifier = $joinPoint->getClassName() . '->' . $joinPoint->getMethodName();
		if (!isset($this->acls[$methodIdentifier])) throw new F3::FLOW3::Security::Exception::NoEntryInPolicy('The given joinpoint was not found in the policy cache. Most likely you have to recreate the AOP proxy classes.', 1222084767);

		return array_keys($this->acls[$methodIdentifier]);
	}

	/**
	 * Returns the privileges a specific role has for the given joinpoint
	 *
	 * @param string The role for which the privileges should be returned
	 * @param F3::FLOW3::AOP::JoinPointInterface $joinPoint The joinpoint for which the roles should be returned
	 * @return array Array of privileges
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPrivileges($role, F3::FLOW3::AOP::JoinPointInterface $joinPoint) {
		$methodIdentifier = $joinPoint->getClassName() . '->' . $joinPoint->getMethodName();
		if (!isset($this->acls[$methodIdentifier])) throw new F3::FLOW3::Security::Exception::NoEntryInPolicy('The given joinpoint was not found in the policy cache. Most likely you have to recreate the AOP proxy classes.', 1222100851);

		$privileges = array();
		if (!isset($this->acls[$methodIdentifier][$role])) {
			foreach ($this->roles[$role] as $parentRole) $privileges = array_merge($privileges, $this->getPrivileges($parentRole, $joinPoint));
		} else {
			$privileges = $this->acls[$methodIdentifier][$role];
		}

		return $privileges;
	}

	/**
	 * Creates a new policy expression parser object
	 *
	 * @return F3::FLOW3::Security::ACL::PolicyExpressionParser A policy expression parser object
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function createPolicyExpressionParser() {
		return $this->componentFactory->getComponent('F3::FLOW3::Security::ACL::PolicyExpressionParser');
	}
}

?>