<?php
namespace F3\FLOW3\Persistence\Generic\Aspect;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Adds the aspect of lazy loading to relevant objects
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @aspect
 */
class LazyLoadingObjectAspect {

	/**
	 * @pointcut classTaggedWith(entity) || classTaggedWith(valueobject)
	 */
	public function isEntityOrValueObject() {}

	/**
	 * @pointcut F3\FLOW3\Persistence\Generic\Aspect\LazyLoadingObjectAspect->isEntityOrValueObject && classTaggedWith(lazy)
	 */
	public function needsLazyLoadingObjectAspect() {}

	/**
	 * Before advice, making sure we initialize before use.
	 *
	 * This expects $proxy->FLOW3_Persistence_LazyLoadingObject_thawProperties
	 * to be a Closure that populates the object. That variable is unset after
	 * initializing the object!
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return void
	 * @before F3\FLOW3\Persistence\Generic\Aspect\LazyLoadingObjectAspect->needsLazyLoadingObjectAspect && !method(.*->__construct())
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initialize(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$proxy = $joinPoint->getProxy();
		if (property_exists($proxy, 'FLOW3_Persistence_LazyLoadingObject_thawProperties') && $proxy->FLOW3_Persistence_LazyLoadingObject_thawProperties instanceof \Closure) {
			$proxy->FLOW3_Persistence_LazyLoadingObject_thawProperties->__invoke($proxy);
			unset($proxy->FLOW3_Persistence_LazyLoadingObject_thawProperties);
		}
	}

}
?>