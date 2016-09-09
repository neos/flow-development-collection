<?php
namespace TYPO3\Flow\Security\Authorization\Privilege\Method;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\Builder\ClassNameIndex;
use TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite;
use TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface;
use TYPO3\Flow\Aop\Pointcut\RuntimeExpressionEvaluator;
use TYPO3\Flow\Cache\CacheManager;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Security\Policy\PolicyService;

/**
 * Pointcut filter which connects the method privileges to the AOP framework
 *
 * @Flow\Scope("singleton")
 */
class MethodPrivilegePointcutFilter implements PointcutFilterInterface
{
    /**
     * @var PointcutFilterComposite[]
     */
    protected $filters = null;

    /**
     * @var array
     */
    protected $methodPermissions = [];

    /**
     * @var VariableFrontend
     */
    protected $methodPermissionCache;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var RuntimeExpressionEvaluator
     */
    protected $runtimeExpressionEvaluator;

    /**
     * This object is created very early so we can't rely on AOP for the property injection
     *
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
        /** @var CacheManager $cacheManager */
        $cacheManager = $this->objectManager->get(CacheManager::class);
        $this->methodPermissionCache = $cacheManager->getCache('Flow_Security_Authorization_Privilege_Method');
        $this->runtimeExpressionEvaluator = $this->objectManager->get(RuntimeExpressionEvaluator::class);
    }

    /**
     * @return void
     */
    public function initializeObject()
    {
        if ($this->methodPermissionCache->has('methodPermission')) {
            $this->methodPermissions = $this->methodPermissionCache->get('methodPermission');
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
     */
    public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)
    {
        if ($this->filters === null) {
            $this->buildPointcutFilters();
        }

        $matches = false;
        /** @var PointcutFilterComposite $filter */
        foreach ($this->filters as $privilegeIdentifier => $filter) {
            if ($filter->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)) {
                $matches = true;
                $methodIdentifier = strtolower($className . '->' . $methodName);

                $hasRuntimeEvaluations = false;
                if ($filter->hasRuntimeEvaluationsDefinition() === true) {
                    $hasRuntimeEvaluations = true;
                    $this->runtimeExpressionEvaluator->addExpression($privilegeIdentifier, $filter->getRuntimeEvaluationsClosureCode());
                }

                $this->methodPermissions[$methodIdentifier][$privilegeIdentifier]['privilegeMatchesMethod'] = true;
                $this->methodPermissions[$methodIdentifier][$privilegeIdentifier]['hasRuntimeEvaluations'] = $hasRuntimeEvaluations;
            }
        }

        return $matches;
    }

    /**
     * Returns TRUE if this filter holds runtime evaluations for a previously matched pointcut
     *
     * @return boolean TRUE if this filter has runtime evaluations
     */
    public function hasRuntimeEvaluationsDefinition()
    {
        return false;
    }

    /**
     * Returns runtime evaluations for the pointcut.
     *
     * @return array Runtime evaluations
     */
    public function getRuntimeEvaluationsDefinition()
    {
        return [];
    }

    /**
     * This method is used to optimize the matching process.
     *
     * @param ClassNameIndex $classNameIndex
     * @return ClassNameIndex
     */
    public function reduceTargetClassNames(ClassNameIndex $classNameIndex)
    {
        if ($this->filters === null) {
            $this->buildPointcutFilters();
        }

        $result = new ClassNameIndex();
        foreach ($this->filters as $filter) {
            $result->applyUnion($filter->reduceTargetClassNames($classNameIndex));
        }
        return $result;
    }

    /**
     * Builds the needed pointcut filters for matching the policy privileges
     *
     * @return boolean
     */
    protected function buildPointcutFilters()
    {
        $this->filters = [];
        /** @var PolicyService $policyService */
        $policyService = $this->objectManager->get(PolicyService::class);
        /** @var MethodPrivilegeInterface[] $methodPrivileges */
        $methodPrivileges = $policyService->getAllPrivilegesByType(MethodPrivilegeInterface::class);
        foreach ($methodPrivileges as $privilege) {
            $this->filters[$privilege->getCacheEntryIdentifier()] = $privilege->getPointcutFilterComposite();
        }
    }

    /**
     * Save the found matches to the cache.
     *
     * @return void
     */
    public function savePolicyCache()
    {
        $tags = ['TYPO3_Flow_Aop'];
        if (!$this->methodPermissionCache->has('methodPermission')) {
            $this->methodPermissionCache->set('methodPermission', $this->methodPermissions, $tags);
        }
    }
}
