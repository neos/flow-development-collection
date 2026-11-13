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
use Neos\Flow\Security\Authorization\Privilege\Parameter\PrivilegeParameterDefinition;
use Neos\Flow\Security\Authorization\Privilege\Parameter\PrivilegeParameterInterface;
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
     * @var string
     */
    protected $label;

    /**
     * @param Parameter\PrivilegeParameterDefinition[] $parameterDefinitions
     */
    public function __construct(string $identifier, string $privilegeClassName, string $matcher, array $parameterDefinitions = [], string $label = '')
    {
        $this->identifier = $identifier;
        $this->label = empty($label) ? $identifier : $label;
        $this->privilegeClassName = $privilegeClassName;
        $this->matcher = $matcher;
        $this->parameterDefinitions = $parameterDefinitions;
    }

    /**
     * This object is created very early so we can't rely on AOP for the property injection
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager): void
    {
        $this->objectManager = $objectManager;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getPrivilegeClassName(): string
    {
        return $this->privilegeClassName;
    }

    public function getMatcher(): string
    {
        return $this->matcher;
    }

    /**
     * @return Parameter\PrivilegeParameterDefinition[]
     */
    public function getParameterDefinitions(): array
    {
        return $this->parameterDefinitions;
    }

    public function hasParameters(): bool
    {
        return $this->parameterDefinitions !== [];
    }

    /**
     * @param string $permission one of "GRANT", "DENY" or "ABSTAIN"
     * @param array<string,mixed> $parameters Optional key/value array with parameter names and -values
     * @throws SecurityException
     */
    public function createPrivilege(string $permission, array $parameters = []): PrivilegeInterface
    {
        $permission = strtolower($permission);
        if ($permission !== PrivilegeInterface::GRANT && $permission !== PrivilegeInterface::DENY && $permission !== PrivilegeInterface::ABSTAIN) {
            throw new SecurityException(sprintf('permission must be either "GRANT", "DENY" or "ABSTAIN", given: "%s"', $permission), 1401878462);
        }

        $privilegeParameters = array_map($this->createParameterMapper($parameters), $this->parameterDefinitions);
        $privilege = new $this->privilegeClassName($this, $this->matcher, $permission, $privilegeParameters);
        if (!$privilege instanceof PrivilegeInterface) {
            throw new SecurityException(sprintf('Expected instance of PrivilegeInterface, got "%s"', $privilege::class), 1395869340);
        }
        $privilege->injectObjectManager($this->objectManager);

        return $privilege;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param array<string,mixed> $parameters
     */
    protected function createParameterMapper(array $parameters): \Closure
    {
        return fn (PrivilegeParameterDefinition $parameterDefinition) => $this->createParameter($parameterDefinition, $parameters);
    }

    /**
     * @param array<string,mixed> $parameters
     * @throws SecurityException
     */
    protected function createParameter(PrivilegeParameterDefinition $parameterDefinition, array $parameters): PrivilegeParameterInterface
    {
        $parameterName = $parameterDefinition->getName();
        if (!isset($parameters[$parameterName])) {
            throw new SecurityException(sprintf('The parameter "%s" is not specified', $parameterName), 1401794982);
        }

        $privilegeParameterClassName = $parameterDefinition->getParameterClassName();
        return new $privilegeParameterClassName($parameterName, $parameters[$parameterName]);
    }
}
