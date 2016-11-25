<?php
namespace Neos\Flow\Security\Authorization\Privilege\Method;

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
use Neos\Flow\Aop\Pointcut\PointcutFilter;
use Neos\Flow\Aop\Pointcut\PointcutFilterComposite;
use Neos\Flow\Aop\Pointcut\RuntimeExpressionEvaluator;
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Authorization\Privilege\AbstractPrivilege;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;
use Neos\Flow\Security\Exception\InvalidPrivilegeTypeException;

/**
 * A method privilege, able to restrict method calls based on pointcut expressions
 * @Flow\Proxy(false)
 */
class MethodPrivilege extends AbstractPrivilege implements MethodPrivilegeInterface
{
    /**
     * @var array
     */
    protected static $methodPermissions;

    /**
     * @var PointcutFilter
     */
    protected $pointcutFilter;

    /**
     * @var RuntimeExpressionEvaluator
     */
    protected $runtimeExpressionEvaluator;

    /**
     * This object is created very early so we can't rely on AOP for the property injection
     * This method also takes care of initializing caches and other dependencies.
     *
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
        $this->initialize();
    }
    /**
     * @return void
     */
    protected function initialize()
    {
        if ($this->runtimeExpressionEvaluator !== null) {
            return;
        }

        /** @var CacheManager $cacheManager */
        $cacheManager = $this->objectManager->get(CacheManager::class);
        $this->runtimeExpressionEvaluator = $this->objectManager->get(RuntimeExpressionEvaluator::class);
        $this->runtimeExpressionEvaluator->injectObjectManager($this->objectManager);

        if (static::$methodPermissions !== null) {
            return;
        }
        static::$methodPermissions = $cacheManager->getCache('Flow_Security_Authorization_Privilege_Method')->get('methodPermission');
    }

    /**
     * Returns TRUE, if this privilege covers the given subject (join point)
     *
     * @param PrivilegeSubjectInterface $subject
     * @return boolean
     * @throws InvalidPrivilegeTypeException
     */
    public function matchesSubject(PrivilegeSubjectInterface $subject)
    {
        if ($subject instanceof MethodPrivilegeSubject === false) {
            throw new InvalidPrivilegeTypeException(sprintf('Privileges of type "%s" only support subjects of type "%s", but we got a subject of type: "%s".', MethodPrivilegeInterface::class, MethodPrivilegeSubject::class, get_class($subject)), 1416241148);
        }

        $this->initialize();
        $joinPoint = $subject->getJoinPoint();

        $methodIdentifier = strtolower($joinPoint->getClassName() . '->' . $joinPoint->getMethodName());

        if (isset(static::$methodPermissions[$methodIdentifier][$this->getCacheEntryIdentifier()])) {
            if (static::$methodPermissions[$methodIdentifier][$this->getCacheEntryIdentifier()]['hasRuntimeEvaluations']) {
                if ($this->runtimeExpressionEvaluator->evaluate($this->getCacheEntryIdentifier(), $joinPoint) === false) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Returns TRUE, if this privilege covers the given method
     *
     * @param string $className
     * @param string $methodName
     * @return boolean
     */
    public function matchesMethod($className, $methodName)
    {
        $this->initialize();

        $methodIdentifier = strtolower($className . '->' . $methodName);
        if (isset(static::$methodPermissions[$methodIdentifier][$this->getCacheEntryIdentifier()])) {
            return true;
        }

        return false;
    }

    /**
     * Returns the pointcut filter composite, matching all methods covered by this privilege
     *
     * @return PointcutFilterComposite
     */
    public function getPointcutFilterComposite()
    {
        if ($this->pointcutFilter === null) {
            /** @var MethodTargetExpressionParser $methodTargetExpressionParser */
            $methodTargetExpressionParser = $this->objectManager->get(MethodTargetExpressionParser::class);
            $this->pointcutFilter = $methodTargetExpressionParser->parse($this->getParsedMatcher(), 'Policy privilege "' . $this->getPrivilegeTargetIdentifier() . '"');
        }

        return $this->pointcutFilter;
    }
}
