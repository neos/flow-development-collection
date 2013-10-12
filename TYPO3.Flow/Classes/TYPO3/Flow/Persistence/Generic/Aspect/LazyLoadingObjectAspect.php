<?php
namespace TYPO3\Flow\Persistence\Generic\Aspect;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Adds the aspect of lazy loading to relevant objects
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class LazyLoadingObjectAspect {

	/**
	 * @Flow\Pointcut("classAnnotatedWith(TYPO3\Flow\Annotations\Entity) || classAnnotatedWith(Doctrine\ORM\Mapping\Entity) || classAnnotatedWith(TYPO3\Flow\Annotations\ValueObject)")
	 */
	public function isEntityOrValueObject() {}

	/**
	 * @Flow\Pointcut("TYPO3\Flow\Persistence\Generic\Aspect\LazyLoadingObjectAspect->isEntityOrValueObject && classAnnotatedWith(TYPO3\Flow\Annotations\Lazy)")
	 */
	public function needsLazyLoadingObjectAspect() {}

	/**
	 * Before advice, making sure we initialize before use.
	 *
	 * This expects $proxy->Flow_Persistence_LazyLoadingObject_thawProperties
	 * to be a Closure that populates the object. That variable is unset after
	 * initializing the object!
	 *
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
	 * @return void
	 * @Flow\Before("TYPO3\Flow\Persistence\Generic\Aspect\LazyLoadingObjectAspect->needsLazyLoadingObjectAspect && !method(.*->__construct())")
	 */
	public function initialize(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$proxy = $joinPoint->getProxy();
		if (property_exists($proxy, 'Flow_Persistence_LazyLoadingObject_thawProperties') && $proxy->Flow_Persistence_LazyLoadingObject_thawProperties instanceof \Closure) {
			$proxy->Flow_Persistence_LazyLoadingObject_thawProperties->__invoke($proxy);
			unset($proxy->Flow_Persistence_LazyLoadingObject_thawProperties);
		}
	}

}
