<?php
namespace TYPO3\FLOW3\Persistence\Generic\Aspect;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Adds the aspect of lazy loading to relevant objects
 *
 * @aspect
 */
class LazyLoadingObjectAspect {

	/**
	 * @pointcut classTaggedWith(entity) || classTaggedWith(valueobject)
	 */
	public function isEntityOrValueObject() {}

	/**
	 * @pointcut TYPO3\FLOW3\Persistence\Generic\Aspect\LazyLoadingObjectAspect->isEntityOrValueObject && classTaggedWith(lazy)
	 */
	public function needsLazyLoadingObjectAspect() {}

	/**
	 * Before advice, making sure we initialize before use.
	 *
	 * This expects $proxy->FLOW3_Persistence_LazyLoadingObject_thawProperties
	 * to be a Closure that populates the object. That variable is unset after
	 * initializing the object!
	 *
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return void
	 * @before TYPO3\FLOW3\Persistence\Generic\Aspect\LazyLoadingObjectAspect->needsLazyLoadingObjectAspect && !method(.*->__construct())
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initialize(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$proxy = $joinPoint->getProxy();
		if (property_exists($proxy, 'FLOW3_Persistence_LazyLoadingObject_thawProperties') && $proxy->FLOW3_Persistence_LazyLoadingObject_thawProperties instanceof \Closure) {
			$proxy->FLOW3_Persistence_LazyLoadingObject_thawProperties->__invoke($proxy);
			unset($proxy->FLOW3_Persistence_LazyLoadingObject_thawProperties);
		}
	}

}
?>