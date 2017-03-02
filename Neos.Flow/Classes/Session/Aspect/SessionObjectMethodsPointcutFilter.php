<?php
namespace Neos\Flow\Session\Aspect;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Aop\Builder\ClassNameIndex;
use Neos\Flow\Aop\Pointcut\PointcutFilterInterface;
use Neos\Flow\ObjectManagement\CompileTimeObjectManager;
use Neos\Flow\ObjectManagement\Configuration\Configuration as ObjectConfiguration;
use Neos\Flow\Annotations as Flow;

/**
 * Pointcut filter matching proxyable methods in objects of scope session
 *
 * @Flow\Scope("singleton")
 */
class SessionObjectMethodsPointcutFilter implements PointcutFilterInterface
{
    /**
     * @var CompileTimeObjectManager
     */
    protected $objectManager;

    /**
     * @param CompileTimeObjectManager $objectManager
     * @return void
     */
    public function injectObjectManager(CompileTimeObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Checks if the specified class and method matches against the filter
     *
     * @param string $className Name of the class to check against
     * @param string $methodName Name of the method to check against
     * @param string $methodDeclaringClassName Name of the class the method was originally declared in
     * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
     * @return boolean TRUE if the class / method match, otherwise FALSE
     */
    public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)
    {
        if ($methodName === null) {
            return false;
        }

        $objectName = $this->objectManager->getObjectNameByClassName($className);
        if (empty($objectName)) {
            return false;
        }

        if ($this->objectManager->getScope($objectName) !== ObjectConfiguration::SCOPE_SESSION) {
            return false;
        }

        if (preg_match('/^__wakeup|__construct|__destruct|__sleep|__serialize|__unserialize|__clone|shutdownObject|initializeObject|inject.*$/', $methodName) !== 0) {
            return false;
        }

        return true;
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
     * Returns runtime evaluations for a previously matched pointcut
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
        $sessionClasses = new ClassNameIndex();
        $sessionClasses->setClassNames($this->objectManager->getClassNamesByScope(ObjectConfiguration::SCOPE_SESSION));
        return $classNameIndex->intersect($sessionClasses);
    }
}
