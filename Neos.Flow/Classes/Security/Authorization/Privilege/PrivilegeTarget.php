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
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param class-string<PrivilegeInterface> $privilegeClassName
     * @param array<string, mixed> $options
     * @param Parameter\PrivilegeParameterDefinition[] $parameterDefinitions
     */
    public function __construct(
        public readonly string $identifier,
        public readonly string $privilegeClassName,
        public readonly array $options,
        public readonly array $parameterDefinitions = [],
        public readonly string $label = ''
    ) {
        if (!is_subclass_of($this->privilegeClassName, PrivilegeInterface::class)) {
            throw new SecurityException(sprintf('Expected instance of %s, got "%s"', PrivilegeInterface::class, $this->privilegeClassName), 1395869340);
        }
    }

    /**
     * This object is created very early so we can't rely on AOP for the property injection
     *
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager): void
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @deprecated with Flow 9.0 - use the public property {@see self::identifier}
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @deprecated with Flow 9.0 - use the public property {@see self::privilegeClassName}
     */
    public function getPrivilegeClassName(): string
    {
        return $this->privilegeClassName;
    }

    /**
     * @deprecated with Flow 9.0 - use the public property {@see self::parameterDefinitions}
     * @return Parameter\PrivilegeParameterDefinition[]
     */
    public function getParameterDefinitions(): array
    {
        return $this->parameterDefinitions;
    }

    /**
     * @return bool
     */
    public function hasParameters(): bool
    {
        return $this->parameterDefinitions !== [];
    }

    /**
     * @param Permission|string $permissionOrString a Permission instance (or a string of "GRANT", "DENY" or "ABSTAIN" for backwards compatibility)
     * @param array $parameters Optional key/value array with parameter names and -values
     * @return PrivilegeInterface
     * @throws SecurityException
     */
    public function createPrivilege(Permission|string $permissionOrString, array $parameters = []): PrivilegeInterface
    {
        if (is_string($permissionOrString)) {
            $permission = Permission::tryFrom($permissionOrString);
            if ($permission === null) {
                throw new SecurityException(sprintf('permission must be either "GRANT", "DENY" or "ABSTAIN", given: "%s"', $permissionOrString), 1401878462);
            }
        } else {
            $permission = $permissionOrString;
        }

        /** @var PrivilegeParameterInterface[] $privilegeParameters */
        $privilegeParameters = array_map(fn (PrivilegeParameterDefinition $parameterDefinition) => $this->createParameter($parameterDefinition, $parameters), $this->parameterDefinitions);
        $options = $this->options;
        foreach ($privilegeParameters as $parameter) {
            foreach ($options as $key => $option) {
                if (is_string($option)) {
                    $options[$key] = str_replace('{parameters.' . $parameter->getName() . '}', $parameter->getValue(), $option);
                }
            }
        }
        return ($this->privilegeClassName)::create($this, $options, $permission, $this->objectManager);
    }

    /**
     * @deprecated with Flow 9.0 - use the public property {@see self::label}
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param PrivilegeParameterDefinition $parameterDefinition
     * @param array $parameters
     * @return PrivilegeParameterInterface
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
