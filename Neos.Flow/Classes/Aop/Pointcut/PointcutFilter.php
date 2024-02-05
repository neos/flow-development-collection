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
use Neos\Flow\Aop\Builder\ClassNameIndex;
use Neos\Flow\Aop\Builder\ProxyClassBuilder;
use Neos\Flow\Aop\Exception\UnknownPointcutException;

/**
 * A filter which refers to another pointcut.
 *
 * @Flow\Proxy(false)
 */
class PointcutFilter implements PointcutFilterInterface
{
    /**
     * Name of the aspect class where the pointcut was declared
     * @var string
     */
    protected $aspectClassName;

    /**
     * Name of the pointcut method
     * @var string
     */
    protected $pointcutMethodName;

    /**
     * The pointcut this filter is based on
     * @var Pointcut|null
     */
    protected $pointcut;

    /**
     * A reference to the AOP Proxy ClassBuilder
     * @var ProxyClassBuilder
     */
    protected $proxyClassBuilder;

    /**
     * The constructor - initializes the pointcut filter with the name of the pointcut we're referring to
     *
     * @param string $aspectClassName Name of the aspect class containing the pointcut
     * @param string $pointcutMethodName Name of the method which acts as an anchor for the pointcut name and expression
     */
    public function __construct(string $aspectClassName, string $pointcutMethodName)
    {
        $this->aspectClassName = $aspectClassName;
        $this->pointcutMethodName = $pointcutMethodName;
    }

    /**
     * Injects the AOP Proxy Class Builder
     *
     * @param ProxyClassBuilder $proxyClassBuilder
     * @return void
     */
    public function injectProxyClassBuilder(ProxyClassBuilder $proxyClassBuilder): void
    {
        $this->proxyClassBuilder = $proxyClassBuilder;
    }

    /**
     * Checks if the specified class and method matches with the pointcut
     *
     * @param string $className Name of the class to check against
     * @param string $methodName Name of the method - not used here
     * @param string $methodDeclaringClassName Name of the class the method was originally declared in
     * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
     * @return boolean true if the class matches, otherwise false
     * @throws UnknownPointcutException
     */
    public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier): bool
    {
        if ($this->pointcut === null) {
            $this->pointcut = $this->proxyClassBuilder->findPointcut($this->aspectClassName, $this->pointcutMethodName) ?: null;
        }
        if ($this->pointcut === null) {
            throw new UnknownPointcutException('No pointcut "' . $this->pointcutMethodName . '" found in aspect class "' . $this->aspectClassName . '" .', 1172223694);
        }
        return $this->pointcut->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier);
    }

    /**
     * Returns true if this filter holds runtime evaluations for a previously matched pointcut
     *
     * @return boolean true if this filter has runtime evaluations
     */
    public function hasRuntimeEvaluationsDefinition(): bool
    {
        return $this->pointcut?->hasRuntimeEvaluationsDefinition() ?? false;
    }

    /**
     * Returns runtime evaluations for the pointcut.
     *
     * @return array Runtime evaluations
     */
    public function getRuntimeEvaluationsDefinition(): array
    {
        if ($this->pointcut === null) {
            $this->pointcut = $this->proxyClassBuilder->findPointcut($this->aspectClassName, $this->pointcutMethodName) ?: null;
        }
        if ($this->pointcut === null) {
            return [];
        }

        return $this->pointcut->getRuntimeEvaluationsDefinition();
    }

    /**
     * This method is used to optimize the matching process.
     *
     * @param ClassNameIndex $classNameIndex
     * @return ClassNameIndex
     */
    public function reduceTargetClassNames(ClassNameIndex $classNameIndex): ClassNameIndex
    {
        if ($this->pointcut === null) {
            $this->pointcut = $this->proxyClassBuilder->findPointcut($this->aspectClassName, $this->pointcutMethodName) ?: null;
        }
        if ($this->pointcut === null) {
            return $classNameIndex;
        }
        return $this->pointcut->reduceTargetClassNames($classNameIndex);
    }
}
