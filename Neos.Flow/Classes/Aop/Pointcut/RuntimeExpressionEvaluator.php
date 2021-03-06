<?php
namespace Neos\Flow\Aop\Pointcut;

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
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Exception;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * An evaluator for AOP runtime expressions
 *
 * We expect that ALL runtime expressions are regenerated during compiletime. This currently does not support adding of expressions. See shutdownObject()
 *
 * @Flow\Scope("singleton")
 */
class RuntimeExpressionEvaluator
{
    /**
     * @var StringFrontend
     */
    protected $runtimeExpressionsCache;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Currently existing runtime expressions loaded from cache.
     *
     * @var array
     */
    protected $runtimeExpressions = [];

    /**
     * This object is created very early and is part of the excluded "Neos\Flow\Aop" namespace so we can't rely on AOP for the property injection.
     *
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager): void
    {
        if ($this->objectManager === null) {
            $this->objectManager = $objectManager;
            /** @var CacheManager $cacheManager */
            $cacheManager = $this->objectManager->get(CacheManager::class);
            $this->runtimeExpressionsCache = $cacheManager->getCache('Flow_Aop_RuntimeExpressions');
        }
    }

    /**
     * Evaluate an expression with the given JoinPoint
     *
     * @param string $privilegeIdentifier MD5 hash that identifies a privilege
     * @param JoinPointInterface $joinPoint
     * @return mixed
     * @throws Exception
     */
    public function evaluate(string $privilegeIdentifier, JoinPointInterface $joinPoint)
    {
        $functionName = $this->generateExpressionFunctionName($privilegeIdentifier);
        if (isset($this->runtimeExpressions[$functionName])) {
            return $this->runtimeExpressions[$functionName]->__invoke($joinPoint, $this->objectManager);
        }

        $expression = $this->runtimeExpressionsCache->get($functionName);

        if (!$expression) {
            throw new Exception('Runtime expression "' . $functionName . '" does not exist. Flushing the code caches may help to solve this.', 1428694144);
        }

        $this->runtimeExpressions[$functionName] = eval($expression);
        return $this->runtimeExpressions[$functionName]->__invoke($joinPoint, $this->objectManager);
    }

    /**
     * Add expression to the evaluator
     *
     * @param string $privilegeIdentifier MD5 hash that identifies a privilege
     * @param string $expression
     * @return void
     */
    public function addExpression(string $privilegeIdentifier, string $expression): void
    {
        $functionName = $this->generateExpressionFunctionName($privilegeIdentifier);
        $wrappedExpression = 'return ' . $expression . ';';
        $this->runtimeExpressionsCache->set($functionName, $wrappedExpression);
        $this->runtimeExpressions[$functionName] = eval($wrappedExpression);
    }

    /**
     * @param string $privilegeIdentifier MD5 hash that identifies a privilege
     * @return string
     */
    protected function generateExpressionFunctionName(string $privilegeIdentifier): string
    {
        return 'flow_aop_expression_' . $privilegeIdentifier;
    }

    /**
     * Flush all runtime expressions
     *
     * @return void
     */
    public function flush(): void
    {
        $this->runtimeExpressionsCache->flush();
    }
}
