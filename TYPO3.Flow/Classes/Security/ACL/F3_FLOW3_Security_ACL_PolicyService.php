<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\ACL;

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
class PolicyService implements \F3\FLOW3\AOP\PointcutFilterInterface {

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface $objectManager The object manager
	 */
	protected $objectManager = NULL;

	/**
	 * The FLOW3 Settings
	 * @var array
	 */
	protected $settings = NULL;

	/**
	 * @var \F3\FLOW3\Cache\AbstractCache A reference to the cache factory
	 */
	protected $cacheFactory;

	/**
	 * @var \F3\FLOW3\Cache\AbstractCache The cached acl entries
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
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager The object manager
	 * @param \F3\FLOW3\Configuration\Manager $configurationManager The configuration manager
	 * @param \F3\FLOW3\Cache\Factory $cacheFactory The cache factory
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo cache the whole thing, if aop proxy cache is enabled
	 */
	public function __construct(\F3\FLOW3\Object\ManagerInterface $objectManager, \F3\FLOW3\Configuration\Manager $configurationManager, \F3\FLOW3\Cache\Factory $cacheFactory) {
		$this->cacheFactory = $cacheFactory;
		$this->objectManager = $objectManager;
		$this->settings = $configurationManager->getSettings('FLOW3');

		$this->roles = $this->settings['security']['policy']['roles'];

		if ($this->settings['aop']['cache']['enable']) {
			$this->aclCache = $this->cacheFactory->create('FLOW3_Security_Policy_ACLs', 'F3\FLOW3\Cache\VariableCache', $this->settings['security']['policy']['aclCache']['backend'], $this->settings['security']['policy']['aclCache']['backendOptions']);
			if ($this->aclCache->has('FLOW3_Security_Policy_ACLs')) {
				$this->acls = $this->aclCache->get('FLOW3_Security_Policy_ACLs');
			}
		}
	}

	/**
	 * Save the found matches to the cache.
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo could also be trigered by a hook/event after AOP initialization is finished. That would seem cleaner than using the destructor.
	 * @todo make sure that exceptions are handled properly in the destructor - or better remove the destructor alltogether
	 */
	public function __destruct() {
		if ($this->settings['aop']['cache']['enable'] === TRUE) {
			$tags = array('F3_FLOW3_AOP');
			try {
				$this->aclCache->set('FLOW3_Security_Policy_ACLs', $this->acls, $tags);
			} catch (Exception $exception) {
				echo ('<br />Exception thrown in ' . __FILE__ . ' in line ' . __LINE__ . ':<br />');
				var_dump($exception);
			}
		}
	}

	/**
	 * Checks if the specified class and method matches against the filter, i.e. if there is a policy entry to intercept this method.
	 * This method also creates a cache entry for every method, to cache the associated roles and privileges.
	 *
	 * @param \F3\FLOW3\Reflection\ClassReflection $class The class to check the name of
	 * @param \F3\FLOW3\Reflection\MethodReflection $method The method to check the name of
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the names match, otherwise FALSE
	 */
	public function matches(\F3\FLOW3\Reflection\ClassReflection $class, \F3\FLOW3\Reflection\MethodReflection $method, $pointcutQueryIdentifier) {
		$matches = FALSE;

		if ($this->settings['security']['enable'] === FALSE) return FALSE;

		if (count($this->filters) === 0) {
			$policyExpressionParser = $this->objectManager->getObject('F3\FLOW3\Security\ACL\PolicyExpressionParser');
			$policyExpressionParser->setResourcesTree($this->settings['security']['policy']['resources']);
			foreach ($this->settings['security']['policy']['acls'] as $role => $acl) {
				foreach ($acl as $resource => $privilege) $this->filters[$role][$resource] = $policyExpressionParser->parse($resource);
			}
		}

		foreach ($this->filters as $role => $filtersForRole) {
			foreach ($filtersForRole as $resource => $filter) {
				if ($filter->matches($class, $method, $pointcutQueryIdentifier)) {
					$methodIdentifier = $class->getName() . '->' . $method->getName();
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

		$resultRoles = array();
		foreach ($this->acls[$methodIdentifier] as $roleIdentifier => $notNeededEntry) {
			$resultRoles[] = $this->createNewRole($roleIdentifier);
		}

		return $resultRoles;
	}

	/**
	 * Returns the privileges a specific role has for the given joinpoint
	 *
	 * @param \F3\FLOW3\Security\ACL\Role The role for which the privileges should be returned
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The joinpoint for which the roles should be returned
	 * @param string $privilegeType If set we check only for this type of privilege
	 * @return array Array of privileges
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPrivileges(\F3\FLOW3\Security\ACL\Role $role, \F3\FLOW3\AOP\JoinPointInterface $joinPoint, $privilegeType = '') {
		$methodIdentifier = $joinPoint->getClassName() . '->' . $joinPoint->getMethodName();
		if (!isset($this->acls[$methodIdentifier])) throw new \F3\FLOW3\Security\Exception\NoEntryInPolicy('The given joinpoint was not found in the policy cache. Most likely you have to recreate the AOP proxy classes.', 1222100851);

		$privileges = array();

		foreach ($this->parsePrivileges($methodIdentifier, (string)$role, $privilegeType) as $privilegeString => $isGrant) {
			$privileges[] = $this->createNewPrivilege($privilegeString, $isGrant);
		}

		return $privileges;
	}

	/**
	 * Parses the privileges for the specified method identifier and role
	 *
	 * @param string $methodIdentifier The method identifier to parse the privileges for
	 * @param string $role The string representation of a role to parse the privileges for
	 * @param string $privilegeType If set, only the privilege of the given type will be parsed
	 * @return array Parsed privileges
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function parsePrivileges($methodIdentifier, $role, $privilegeType) {
		$privileges = array();

		foreach ($this->acls[$methodIdentifier][(string)$role] as $privilegeString) {
			preg_match('/^(.+)_(GRANT|DENY)$/', $privilegeString, $matches);

			if ($privilegeType !== '' && $privilegeType !== $matches[1]) continue;

			if ($matches[2] === 'GRANT') $privileges[$matches[1]] = TRUE;
			else $privileges[$matches[1]] = FALSE;
		}

		foreach ($this->roles[$role] as $parentRole) $privileges = array_merge($this->parsePrivileges($methodIdentifier, $parentRole, $privilegeType), $privileges);

		return $privileges;
	}

	/**
	 * Returns a new role object
	 *
	 * @param string $roleIdentifier The identifier for the new role
	 * @return \F3\FLOW3\Security\ACL\Role A new role object
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function createNewRole($roleIdentifier) {
		return $this->objectManager->getObject('F3\FLOW3\Security\ACL\Role', $roleIdentifier);
	}

	/**
	 * Returns a new privilege object
	 *
	 * @param string $privilegeIdentifier The identifier for the new privilege
	 * @param boolean $isGrant The isGrant flag of the privilege
	 * @return \F3\FLOW3\Security\ACL\Privilege A new role object
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function createNewPrivilege($privilegeIdentifier, $isGrant = FALSE) {
		return $this->objectManager->getObject('F3\FLOW3\Security\ACL\Privilege', $privilegeIdentifier, $isGrant);
	}
}

?>