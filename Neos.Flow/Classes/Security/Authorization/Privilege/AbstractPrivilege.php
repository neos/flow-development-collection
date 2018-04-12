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
use Neos\Flow\Security\Authorization\Privilege\Parameter\PrivilegeParameterInterface;

/**
 * An abstract base class for privileges
 */
abstract class AbstractPrivilege implements PrivilegeInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Unique identifier of this privilege (used for cache entries)
     *
     * @var string
     */
    protected $cacheEntryIdentifier;

    /**
     * @var PrivilegeTarget
     */
    protected $privilegeTarget;

    /**
     * @var PrivilegeParameterInterface[]
     */
    protected $parameters;

    /**
     * @var string
     */
    protected $matcher;

    /**
     * @var string
     */
    protected $parsedMatcher;

    /**
     * @var integer One of the constants ABSTAIN, GRANT or DENY
     */
    protected $permission;

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
     * @param PrivilegeTarget $privilegeTarget
     * @param string $matcher
     * @param string $permission One of the constants GRANT, DENY or ABSTAIN
     * @param PrivilegeParameterInterface[] $parameters
     */
    public function __construct(PrivilegeTarget $privilegeTarget, $matcher, $permission, array $parameters)
    {
        $this->privilegeTarget = $privilegeTarget;
        $this->matcher = $matcher;
        $this->permission = $permission;
        $this->parameters = $parameters;
        $this->buildCacheEntryIdentifier();
    }

    /**
     * Initializes the unique cache entry identifier
     *
     * @return void
     */
    protected function buildCacheEntryIdentifier()
    {
        $this->cacheEntryIdentifier = md5($this->getPrivilegeTargetIdentifier() . '|' . $this->getParsedMatcher());
    }

    /**
     * Unique identifier of this privilege
     *
     * @return string
     */
    public function getCacheEntryIdentifier()
    {
        return $this->cacheEntryIdentifier;
    }

    /**
     * @return PrivilegeParameterInterface[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return boolean
     */
    public function hasParameters()
    {
        return $this->parameters !== [];
    }

    /**
     * @return integer
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     * @return boolean
     */
    public function isGranted()
    {
        return $this->permission === self::GRANT;
    }

    /**
     * @return boolean
     */
    public function isAbstained()
    {
        return $this->permission === self::ABSTAIN;
    }

    /**
     * @return boolean
     */

    public function isDenied()
    {
        return $this->permission === self::DENY;
    }

    /**
     * The related privilege target
     *
     * @return PrivilegeTarget
     */
    public function getPrivilegeTarget()
    {
        return $this->privilegeTarget;
    }

    /**
     * Unique identifier for the related privilege target (e.g. "Neos.Flow:PublicMethods")
     *
     * @return string
     */
    public function getPrivilegeTargetIdentifier()
    {
        return $this->privilegeTarget->getIdentifier();
    }

    /**
     * A matcher string, describing the privilegeTarget (e.g. pointcut expression for methods or EEL expression for entities)
     *
     * Note: This returns the raw matcher string that might contain parameter placeholders. If you want to return the parsed
     * matcher with placeholders replaced, use getParsedMatcher() instead.
     *
     * @return string
     */
    public function getMatcher()
    {
        return $this->matcher;
    }

    /**
     * Returns the matcher string with replaced parameter markers. @see getMatcher()
     *
     * @return string
     */
    public function getParsedMatcher()
    {
        $parsedMatcher = $this->matcher;
        // TODO: handle parameters that are not strings
        foreach ($this->parameters as $parameter) {
            $parsedMatcher = str_replace('{parameters.' . $parameter->getName() . '}', $parameter->getValue(), $parsedMatcher);
        }
        return $parsedMatcher;
    }
}
