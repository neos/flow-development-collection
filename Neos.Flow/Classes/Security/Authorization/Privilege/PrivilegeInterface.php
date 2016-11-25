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
    const ABSTAIN = 'abstain';
    const GRANT = 'grant';
    const DENY = 'deny';

    /**
     * Note: We can't define constructors in interfaces, but this is assumed to exist in the concrete implementation!
     *
     * @param PrivilegeTarget $privilegeTarget
     * @param string $matcher
     * @param integer $permission One of the constants ABSTAIN, GRANT or DENY
     * @param PrivilegeParameterInterface[] $parameters
     */
    // public function __construct(PrivilegeTarget $privilegeTarget, $matcher, $permission, array $parameters) {

    /**
     * This object is created very early so we can't rely on AOP for the property injection
     *
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager);

    /**
     * @return string
     */
    public function getPermission();

    /**
     * @return boolean
     */
    public function isGranted();

    /**
     * @return boolean
     */
    public function isAbstained();

    /**
     * @return boolean
     */
    public function isDenied();

    /**
     * Returns the related privilege target
     *
     * @return PrivilegeTarget
     */
    public function getPrivilegeTarget();

    /**
     * Unique name of the related privilege target (for example "Neos.Flow:PublicMethods")
     *
     * @return string
     */
    public function getPrivilegeTargetIdentifier();

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
     * Returns TRUE, if this privilege covers the given subject
     *
     * @param PrivilegeSubjectInterface $subject
     * @return boolean
     * @throws InvalidPrivilegeTypeException if the given $subject is not supported by the privilege
     */
    public function matchesSubject(PrivilegeSubjectInterface $subject);
}
