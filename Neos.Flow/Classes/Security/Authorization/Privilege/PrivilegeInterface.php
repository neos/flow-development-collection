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
use Neos\Cache\CacheAwareInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Authorization\Privilege\Parameter\PrivilegeParameterInterface;
use Neos\Flow\Security\Exception\InvalidPrivilegeTypeException;

/**
 * Contract for a privilege
 */
interface PrivilegeInterface extends CacheAwareInterface
{
    /**
     * @param array<string, mixed> $options privilege options with parameters replaced
     */
    public static function create(PrivilegeTarget $privilegeTarget, array $options, Permission $permission, ObjectManagerInterface $objectManager): self;

    public function getPermission(): Permission;

    /**
     * @deprecated with Flow 9.0 - use `$privilege::getPermission() === Permission::GRANT`
     */
    public function isGranted(): bool;

    /**
     * @deprecated with Flow 9.0 - use `$privilege::getPermission() === Permission::ABSTAIN`
     */
    public function isAbstained(): bool;

    /**
     * @deprecated with Flow 9.0 - use `$privilege::getPermission() === Permission::DENY`
     */
    public function isDenied(): bool;

    /**
     * Returns the related privilege target
     */
    public function getPrivilegeTarget(): PrivilegeTarget;

    /**
     * Unique name of the related privilege target (for example "Neos.Flow:PublicMethods")
     */
    public function getPrivilegeTargetIdentifier(): string;

    /**
     * A matcher string, describing the privilegeTarget (e.g. pointcut expression for methods or EEL expression for entities)
     *
     * @return string
     */
    public function getMatcher();

    /**
     * @return PrivilegeParameterInterface[]
     */
    public function getParameters();

    /**
     * @return boolean
     */
    public function hasParameters();

    /**
     * Returns true, if this privilege covers the given subject
     *
     * @param PrivilegeSubjectInterface $subject
     * @return boolean
     * @throws InvalidPrivilegeTypeException if the given $subject is not supported by the privilege
     */
    public function matchesSubject(PrivilegeSubjectInterface $subject);
}
