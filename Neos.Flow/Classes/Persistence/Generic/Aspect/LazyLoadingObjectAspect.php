<?php
namespace Neos\Flow\Persistence\Generic\Aspect;

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

/**
 * Adds the aspect of lazy loading to relevant objects
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class LazyLoadingObjectAspect
{
    /**
     * @Flow\Pointcut("classAnnotatedWith(Neos\Flow\Annotations\Entity) || classAnnotatedWith(Doctrine\ORM\Mapping\Entity) || classAnnotatedWith(Neos\Flow\Annotations\ValueObject)")
     */
    public function isEntityOrValueObject()
    {
    }

    /**
     * @Flow\Pointcut("Neos\Flow\Persistence\Generic\Aspect\LazyLoadingObjectAspect->isEntityOrValueObject && classAnnotatedWith(Neos\Flow\Annotations\Lazy)")
     */
    public function needsLazyLoadingObjectAspect()
    {
    }

    /**
     * Before advice, making sure we initialize before use.
     *
     * This expects $proxy->Flow_Persistence_LazyLoadingObject_thawProperties
     * to be a Closure that populates the object. That variable is unset after
     * initializing the object!
     *
     * @param JoinPointInterface $joinPoint The current join point
     * @return void
     * @Flow\Before("Neos\Flow\Persistence\Generic\Aspect\LazyLoadingObjectAspect->needsLazyLoadingObjectAspect && !method(.*->__construct())")
     */
    public function initialize(JoinPointInterface $joinPoint)
    {
        $proxy = $joinPoint->getProxy();
        if (property_exists($proxy, 'Flow_Persistence_LazyLoadingObject_thawProperties') && $proxy->Flow_Persistence_LazyLoadingObject_thawProperties instanceof \Closure) {
            $proxy->Flow_Persistence_LazyLoadingObject_thawProperties->__invoke($proxy);
            unset($proxy->Flow_Persistence_LazyLoadingObject_thawProperties);
        }
    }
}
