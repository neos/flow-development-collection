<?php
namespace Neos\Flow\Security\Authorization\Privilege;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Exception as SecurityException;

/**
 * A privilege target
 */
class PrivilegeTarget
{
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
    public function __construct($identifier, $privilegeClassName, $matcher, array $parameterDefinitions = [])
    {
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
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }


    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getPrivilegeClassName()
    {
        return $this->privilegeClassName;
    }

    /**
     * @return string
     */
    public function getMatcher()
    {
        return $this->matcher;
    }

    /**
     * @return Parameter\PrivilegeParameterDefinition[]
     */
    public function getParameterDefinitions()
    {
        return $this->parameterDefinitions;
    }

    /**
     * @return boolean
     */
    public function hasParameters()
    {
        return $this->parameterDefinitions !== [];
    }

    /**
     * @param string $permission one of "GRANT", "DENY" or "ABSTAIN"
     * @param array $parameters Optional key/value array with parameter names and -values
     * @return PrivilegeInterface
     * @throws SecurityException
     */
    public function createPrivilege($permission, array $parameters = [])
    {
        $permission = strtolower($permission);
        if ($permission !== PrivilegeInterface::GRANT && $permission !== PrivilegeInterface::DENY && $permission !== PrivilegeInterface::ABSTAIN) {
            throw new SecurityException(sprintf('permission must be either "GRANT", "DENY" or "ABSTAIN", given: "%s"', $permission), 1401878462);
        }

        $privilegeParameters = [];
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
