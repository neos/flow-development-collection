<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\ACL;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The policy service reads the policy configuration. The security adivce asks this service which methods have to be intercepted by a security interceptor.
 * The access decision voters get the roles and privileges configured (in the security policy) for a specific method invocation from this service.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PolicyService implements \F3\FLOW3\AOP\Pointcut\PointcutFilterInterface {

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface $objectFactory The object manager
	 */
	protected $objectFactory = NULL;

	/**
	 * The FLOW3 Settings
	 * @var array
	 */
	protected $settings = NULL;

	/**
	 * @var \F3\FLOW3\Cache\Frontend\VariableFrontend The cached acl entries
	 */
	protected $cache;

	/**
	 * @var F3\FLOW3\Security\ACL\PolicyExpressionParser
	 */
	protected $policyExpressionParser;

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
	 * Injects the object factory
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Injects the FLOW3 settings
	 *
	 * @param array $settings Settings of the FLOW3 package
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Injects the ACL cache
	 *
	 * @param F3\FLOW3\Cache\Frontend\VariableFrontend $cache The cache
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectCache(\F3\FLOW3\Cache\Frontend\VariableFrontend $cache) {
		$this->cache = $cache;
	}

	/**
	 * Injects the policy expression parser
	 *
	 * @param F3\FLOW3\Security\ACL\PolicyExpressionParser $parser
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPolicyExpressionParser(\F3\FLOW3\Security\ACL\PolicyExpressionParser $parser) {
		$this->policyExpressionParser = $parser;
	}

	/**
	 * Initializes this Policy Service
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObject() {
		$this->roles = $this->settings['security']['policy']['roles'];
		if ($this->cache->has('acls')) {
			$this->acls = $this->cache->get('acls');
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
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier) {
		if ($this->settings['security']['enable'] === FALSE) return FALSE;

		$matches = FALSE;

		if (count($this->filters) === 0) {
			$this->policyExpressionParser->setResourcesTree($this->settings['security']['policy']['resources']);
			foreach ($this->settings['security']['policy']['acls'] as $role => $acl) {
				foreach ($acl as $resource => $privilege) {
					$resourceTrace = array();
					$this->filters[$role][$resource] = $this->policyExpressionParser->parse($resource, $resourceTrace);

					foreach ($resourceTrace as $currentResource) {
						$this->acls[$currentResource][$role][] = $privilege;
					}
				}
			}
		}

		foreach ($this->filters as $role => $filtersForRole) {
			foreach ($filtersForRole as $resource => $filter) {
				if ($filter->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)) {
					$methodIdentifier = $className . '->' . $methodName;
					$this->acls[$methodIdentifier][$role][] = $this->settings['security']['policy']['acls'][$role][$resource];
					$matches = TRUE;
				}
			}
		}

		return $matches;
	}

	/**
	 * Returns the configured roles for the given joinpoint
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The joinpoint for which the roles should be returned
	 * @return array Array of roles
	 * @throws \F3\FLOW3\Security\Exception\NoEntryInPolicy
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRoles(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$methodIdentifier = $joinPoint->getClassName() . '->' . $joinPoint->getMethodName();
		if (!isset($this->acls[$methodIdentifier])) throw new \F3\FLOW3\Security\Exception\NoEntryInPolicy('The given joinpoint was not found in the policy cache. Most likely you have to recreate the AOP proxy classes.', 1222084767);

		$roles = array();
		foreach (array_keys($this->acls[$methodIdentifier]) as $roleIdentifier) {
			$roles[] = $this->objectFactory->create('F3\FLOW3\Security\ACL\Role', $roleIdentifier);
		}

		return $roles;
	}

	/**
	 * Returns the privileges a specific role has for the given joinpoint
	 *
	 * @param \F3\FLOW3\Security\ACL\Role The role for which the privileges should be returned
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The joinpoint for which the privileges should be returned
	 * @param string $privilegeType If set we check only for this type of privilege
	 * @return array Array of privileges
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPrivilegesForJoinPoint(\F3\FLOW3\Security\ACL\Role $role, \F3\FLOW3\AOP\JoinPointInterface $joinPoint, $privilegeType = '') {
		$methodIdentifier = $joinPoint->getClassName() . '->' . $joinPoint->getMethodName();
		if (!isset($this->acls[$methodIdentifier])) throw new \F3\FLOW3\Security\Exception\NoEntryInPolicy('The given joinpoint was not found in the policy cache. Most likely you have to recreate the AOP proxy classes.', 1222100851);

		$privileges = $this->parsePrivileges($methodIdentifier, (string)$role, $privilegeType);
		if (!is_array($privileges)) return array();

		return $privileges;
	}

	/**
	 * Returns the privileges a specific role has for the given resource
	 *
	 * @param \F3\FLOW3\Security\ACL\Role The role for which the privileges should be returned
	 * @param string $resource The resource for which the privileges should be returned
	 * @return array Array of privileges
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPrivilegesForResource(\F3\FLOW3\Security\ACL\Role $role, $resource) {
		if (!isset($this->acls[$resource])) throw new \F3\FLOW3\Security\Exception\NoEntryInPolicy('The given resource was not found in the policy cache. Most likely you have to recreate the AOP proxy classes.', 1248348214);

		$privileges = $this->parsePrivileges($resource, (string)$role, '');
		if (!is_array($privileges)) return array();

		return $privileges;
	}

	/**
	 * Parses the privileges for the specified identifier and role
	 *
	 * @param string $identifier The identifier (class->method or resource) to parse the privileges for
	 * @param string $role The string representation of a role to parse the privileges for
	 * @param string $privilegeType If set, only the privilege of the given type will be parsed
	 * @return array Parsed privileges
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function parsePrivileges($identifier, $role, $privilegeType) {
		$privileges = array();

		if (!isset($this->acls[$identifier][$role])) return NULL;

		foreach ($this->acls[$identifier][$role] as $privilegeString) {
			preg_match('/^(.+)_(GRANT|DENY)$/', $privilegeString, $matches);

			if ($privilegeType !== '' && $privilegeType !== $matches[1]) continue;

			$privileges[] = $this->objectFactory->create('F3\FLOW3\Security\ACL\Privilege', $matches[1], ($matches[2] === 'GRANT' ? TRUE : FALSE));
		}

		foreach ($this->roles[$role] as $parentRole) $privileges = array_merge($this->parsePrivileges($identifier, $parentRole, $privilegeType), $privileges);

		return $privileges;
	}

	/**
	 * Save the found matches to the cache.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function shutdownObject() {
		$tags = array('F3_FLOW3_AOP');
		$this->cache->set('acls', $this->acls, $tags);
	}
}

?>