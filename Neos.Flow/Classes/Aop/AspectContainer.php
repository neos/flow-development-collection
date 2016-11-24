<?php
namespace Neos\Flow\Aop;

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
use Neos\Flow\Aop\Pointcut\Pointcut;

/**
 * An aspect is represented by class tagged with the "aspect" annotation.
 * The aspect class may contain advices and pointcut declarations. Aspect
 * classes are wrapped by this Aspect Container.
 *
 * For each advice a pointcut expression (not declaration!) is required to define
 * when an advice should apply. The combination of advice and pointcut
 * expression is called "advisor".
 *
 * A pointcut declaration only contains a pointcut expression and is used to
 * make pointcut expressions reusable and combinable.
 *
 * An introduction declaration on the class level contains an interface name
 * and a pointcut expression and is used to introduce a new interface to the
 * target class.
 *
 * If used on a property an introduction contains a pointcut expression and is
 * used to introduce the annotated property into the target class.
 *
 * @Flow\Proxy(false)
 */
class AspectContainer
{
    /**
     * @var string
     */
    protected $className;

    /**
     * An array of \Neos\Flow\Aop\Advisor objects
     * @var array
     */
    protected $advisors = [];

    /**
     * An array of \Neos\Flow\Aop\InterfaceIntroduction objects
     * @var array
     */
    protected $interfaceIntroductions = [];

    /**
     * An array of \Neos\Flow\Aop\PropertyIntroduction objects
     * @var array
     */
    protected $propertyIntroductions = [];

    /**
     * An array of \Neos\Flow\Aop\TraitIntroduction objects
     *
     * @var array
     */
    protected $traitIntroductions = array();

    /**
     * An array of explicitly declared \Neos\Flow\Pointcut objects
     * @var array
     */
    protected $pointcuts = [];

    /**
     * @var \Neos\Flow\Aop\Builder\ClassNameIndex
     */
    protected $cachedTargetClassNameCandidates;

    /**
     * The constructor
     *
     * @param string $className Name of the aspect class
     */
    public function __construct($className)
    {
        $this->className = $className;
    }

    /**
     * Returns the name of the aspect class
     *
     * @return string Name of the aspect class
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Returns the advisors which were defined in the aspect
     *
     * @return array Array of \Neos\Flow\Aop\Advisor objects
     */
    public function getAdvisors()
    {
        return $this->advisors;
    }

    /**
     * Returns the interface introductions which were defined in the aspect
     *
     * @return array Array of \Neos\Flow\Aop\InterfaceIntroduction objects
     */
    public function getInterfaceIntroductions()
    {
        return $this->interfaceIntroductions;
    }

    /**
     * Returns the property introductions which were defined in the aspect
     *
     * @return array Array of \Neos\Flow\Aop\PropertyIntroduction objects
     */
    public function getPropertyIntroductions()
    {
        return $this->propertyIntroductions;
    }

    /**
     * Returns the trait introductions which were defined in the aspect
     *
     * @return array Array of \Neos\Flow\Aop\TraitIntroduction objects
     */
    public function getTraitIntroductions()
    {
        return $this->traitIntroductions;
    }

    /**
     * Returns the pointcuts which were declared in the aspect. This
     * does not contain the pointcuts which were made out of the pointcut
     * expressions for the advisors!
     *
     * @return array Array of \Neos\Flow\Aop\Pointcut\Pointcut objects
     */
    public function getPointcuts()
    {
        return $this->pointcuts;
    }

    /**
     * Adds an advisor to this aspect container
     *
     * @param Advisor $advisor The advisor to add
     * @return void
     */
    public function addAdvisor(Advisor $advisor)
    {
        $this->advisors[] = $advisor;
    }

    /**
     * Adds an introduction declaration to this aspect container
     *
     * @param InterfaceIntroduction $introduction
     * @return void
     */
    public function addInterfaceIntroduction(InterfaceIntroduction $introduction)
    {
        $this->interfaceIntroductions[] = $introduction;
    }

    /**
     * Adds an introduction declaration to this aspect container
     *
     * @param PropertyIntroduction $introduction
     * @return void
     */
    public function addPropertyIntroduction(PropertyIntroduction $introduction)
    {
        $this->propertyIntroductions[] = $introduction;
    }

    /**
     * Adds an introduction declaration to this aspect container
     *
     * @param TraitIntroduction $introduction
     * @return void
     */
    public function addTraitIntroduction(TraitIntroduction $introduction)
    {
        $this->traitIntroductions[] = $introduction;
    }

    /**
     * Adds a pointcut (from a pointcut declaration) to this aspect container
     *
     * @param Pointcut $pointcut The pointcut to add
     * @return void
     */
    public function addPointcut(Pointcut $pointcut)
    {
        $this->pointcuts[] = $pointcut;
    }

    /**
     * This method is used to optimize the matching process.
     *
     * @param \Neos\Flow\Aop\Builder\ClassNameIndex $classNameIndex
     * @return \Neos\Flow\Aop\Builder\ClassNameIndex
     */
    public function reduceTargetClassNames(Builder\ClassNameIndex $classNameIndex)
    {
        $result = new Builder\ClassNameIndex();
        foreach ($this->advisors as $advisor) {
            $result->applyUnion($advisor->getPointcut()->reduceTargetClassNames($classNameIndex));
        }
        foreach ($this->interfaceIntroductions as $interfaceIntroduction) {
            $result->applyUnion($interfaceIntroduction->getPointcut()->reduceTargetClassNames($classNameIndex));
        }
        foreach ($this->propertyIntroductions as $propertyIntroduction) {
            $result->applyUnion($propertyIntroduction->getPointcut()->reduceTargetClassNames($classNameIndex));
        }
        foreach ($this->traitIntroductions as $traitIntroduction) {
            $result->applyUnion($traitIntroduction->getPointcut()->reduceTargetClassNames($classNameIndex));
        }
        $this->cachedTargetClassNameCandidates = $result;
        return $result;
    }

    /**
     * @return \Neos\Flow\Aop\Builder\ClassNameIndex
     */
    public function getCachedTargetClassNameCandidates()
    {
        return $this->cachedTargetClassNameCandidates;
    }
}
