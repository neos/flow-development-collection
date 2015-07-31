<?php
namespace TYPO3\Flow\Security\Authorization\Privilege;

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
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Security\Exception as SecurityException;

/**
 * A privilege target
 */
class PrivilegeTarget {

	/**
	 * @var string
	 */
	protected $identifier;

	/**
	 * @var string
	 */
	protected $privilegeClassName;

	/**
	 * @var string
	 */
	protected $matcher;

	/**
	 * @var Parameter\PrivilegeParameterDefinition[]
	 */
	protected $parameterDefinitions;

	/**
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @param string $identifier
	 * @param string $privilegeClassName
	 * @param string $matcher
	 * @param Parameter\PrivilegeParameterDefinition[] $parameterDefinitions
	 */
	public function __construct($identifier, $privilegeClassName, $matcher, array $parameterDefinitions = array()) {
		$this->identifier = $identifier;
		$this->privilegeClassName = $privilegeClassName;
		$this->matcher = $matcher;
		$this->parameterDefinitions = $parameterDefinitions;
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
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * @return string
	 */
	public function getPrivilegeClassName() {
		return $this->privilegeClassName;
	}

	/**
	 * @return string
	 */
	public function getMatcher() {
		return $this->matcher;
	}

	/**
	 * @return Parameter\PrivilegeParameterDefinition[]
	 */
	public function getParameterDefinitions() {
		return $this->parameterDefinitions;
	}

	/**
	 * @return boolean
	 */
	public function hasParameters() {
		return $this->parameterDefinitions !== array();
	}

	/**
	 * @param string $permission one of "GRANT", "DENY" or "ABSTAIN"
	 * @param array $parameters Optional key/value array with parameter names and -values
	 * @return PrivilegeInterface
	 * @throws SecurityException
	 */
	public function createPrivilege($permission, array $parameters = array()) {
		$permission = strtolower($permission);
		if ($permission !== PrivilegeInterface::GRANT && $permission !== PrivilegeInterface::DENY && $permission !== PrivilegeInterface::ABSTAIN) {
			throw new SecurityException(sprintf('permission must be either "GRANT", "DENY" or "ABSTAIN", given: "%s"', $permission), 1401878462);
		}

		$privilegeParameters = array();
		foreach ($this->parameterDefinitions as $parameterDefinition) {
			$parameterName = $parameterDefinition->getName();
			if (!isset($parameters[$parameterName])) {
				throw new SecurityException(sprintf('The parameter "%s" is not specified', $parameterName), 1401794982);
			}
			$privilegeParameterClassName = $parameterDefinition->getParameterClassName();
			$privilegeParameters[$parameterName] = new $privilegeParameterClassName($parameterName, $parameters[$parameterName]);
		}
		$privilege = new $this->privilegeClassName($this, $this->matcher, $permission, $privilegeParameters);
		if (!$privilege instanceof PrivilegeInterface) {
			throw new SecurityException(sprintf('Expected instance of PrivilegeInterface, got "%s"', get_class($privilege)), 1395869340);
		}
		$privilege->injectObjectManager($this->objectManager);

		return $privilege;
	}

}