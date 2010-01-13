<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\Aspect;

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
 * Adds the aspect of persistence to repositories
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @aspect
 */
class DirtyMonitoring {

	/**
	 * The reflection service
	 *
	 * @var \F3\FLOW3\Reflection\Service
	 */
	protected $reflectionService;

	/**
	 * @pointcut classTaggedWith(entity) || classTaggedWith(valueobject)
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isEntityOrValueObject() {}

	/**
	 * @pointcut F3\FLOW3\Persistence\Aspect\DirtyMonitoring->isEntityOrValueObject && !within(F3\FLOW3\Persistence\Aspect\DirtyMonitoringInterface)
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function needsDirtyCheckingAspect() {}

	/**
	 * @introduce F3\FLOW3\Persistence\Aspect\DirtyMonitoringInterface, F3\FLOW3\Persistence\Aspect\DirtyMonitoring->needsDirtyCheckingAspect
	 */
	public $dirtyMonitoringInterface;

	/**
	 * Injects the reflection service
	 *
	 * @param \F3\FLOW3\Reflection\Service $reflectionService
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * After returning advice, making sure we have an UUID for each and every entity.
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return void
	 * @afterreturning classTaggedWith(entity) && method(.*->__construct())
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function generateUUID(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$proxy = $joinPoint->getProxy();
		$proxy->FLOW3_Persistence_Entity_UUID = \F3\FLOW3\Utility\Algorithms::generateUUID();
	}

	/**
	 * After returning advice, generates the value hash for the object
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return void
	 * @afterreturning classTaggedWith(valueobject) && method(.*->__construct())
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function generateValueHash(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$proxy = $joinPoint->getProxy();
		$hashSource = '';
		foreach (array_keys($this->reflectionService->getClassSchema($joinPoint->getClassName())->getProperties()) as $propertyName) {
			if (!is_object($proxy->FLOW3_AOP_Proxy_getProperty($propertyName))) {
				$hashSource .= $proxy->FLOW3_AOP_Proxy_getProperty($propertyName);
			} elseif (property_exists($proxy, 'FLOW3_Persistence_Entity_UUID')) {
				$hashSource .= $proxy->FLOW3_Persistence_Entity_UUID;
			} elseif (property_exists($proxy, 'FLOW3_Persistence_ValueObject_Hash')) {
				$hashSource .= $proxy->FLOW3_Persistence_Entity_UUID;
			}
		}
		$proxy->FLOW3_Persistence_ValueObject_Hash = sha1($hashSource);
	}

	/**
	 * Around advice, implements the FLOW3_Persistence_isNew() method introduced above
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return boolean
	 * @around F3\FLOW3\Persistence\Aspect\DirtyMonitoring->needsDirtyCheckingAspect && method(.*->FLOW3_Persistence_isNew())
	 * @see \F3\FLOW3\Persistence\Aspect\DirtyMonitoringInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isNew(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$joinPoint->getAdviceChain()->proceed($joinPoint);

		$proxy = $joinPoint->getProxy();
		return (!property_exists($proxy, 'FLOW3_Persistence_cleanProperties') || property_exists($proxy, 'FLOW3_Persistence_clone'));
	}

	/**
	 * Around advice, implements the FLOW3_Persistence_isClone() method introduced above
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return boolean if the object is a clone
	 * @around F3\FLOW3\Persistence\Aspect\DirtyMonitoring->needsDirtyCheckingAspect && method(.*->FLOW3_Persistence_isClone())
	 * @see \F3\FLOW3\Persistence\Aspect\DirtyMonitoringInterface
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isClone(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$joinPoint->getAdviceChain()->proceed($joinPoint);

		$proxy = $joinPoint->getProxy();
		return property_exists($proxy, 'FLOW3_Persistence_clone');
	}

	/**
	 * Around advice, implements the FLOW3_Persistence_isDirty() method introduced above
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return boolean
	 * @around F3\FLOW3\Persistence\Aspect\DirtyMonitoring->needsDirtyCheckingAspect && method(.*->FLOW3_Persistence_isDirty())
	 * @see \F3\FLOW3\Persistence\Aspect\DirtyMonitoringInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isDirty(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$joinPoint->getAdviceChain()->proceed($joinPoint);

		$proxy = $joinPoint->getProxy();

		if (property_exists($proxy, 'FLOW3_Persistence_cleanProperties') && !property_exists($proxy, 'FLOW3_Persistence_clone')) {
			$uuidPropertyName = $this->reflectionService->getClassSchema($joinPoint->getClassName())->getUUIDPropertyName();
			if ($uuidPropertyName !== NULL && !property_exists($proxy, 'FLOW3_Persistence_clone') && $proxy->FLOW3_AOP_Proxy_getProperty($uuidPropertyName) !== $proxy->FLOW3_Persistence_cleanProperties[$uuidPropertyName]) {
				throw new \F3\FLOW3\Persistence\Exception\TooDirty('My property "' . $uuidPropertyName . '" tagged as @uuid has been modified, that is simply too much.', 1222871239);
			}

			if (is_object($proxy->FLOW3_Persistence_cleanProperties[$joinPoint->getMethodArgument('propertyName')])) {
				if ($this->areObjectsDifferent(
						$proxy->FLOW3_Persistence_cleanProperties[$joinPoint->getMethodArgument('propertyName')],
						$proxy->FLOW3_AOP_Proxy_getProperty($joinPoint->getMethodArgument('propertyName'))
					)) {
					return TRUE;
				}
			} else {
				if ($proxy->FLOW3_Persistence_cleanProperties[$joinPoint->getMethodArgument('propertyName')] !== $proxy->FLOW3_AOP_Proxy_getProperty($joinPoint->getMethodArgument('propertyName'))) {
					return TRUE;
				}
			}
		} else {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Compares two objects non-recursively by the properties known in their
	 * class schema if possible. Otherwise a regular non-strict comparison is
	 * made.
	 *
	 * @param object $object1
	 * @param object $object2
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function areObjectsDifferent($object1, $object2) {
		if (!($object1 instanceof $object2)) {
			return TRUE;
		}

		if (!($object1 instanceof \F3\FLOW3\AOP\ProxyInterface) || $object1 instanceof \DateTime || $object1 instanceof \SplObjectStorage) {
			return $object1 != $object2;
		}
		
		$propertyNames = array_keys($this->reflectionService->getClassSchema($object1->FLOW3_AOP_Proxy_getProxyTargetClassName())->getProperties());
		foreach ($propertyNames as $propertyName) {
			if (!(property_exists($object1, $propertyName) && property_exists($object2, $propertyName))) {
				return TRUE;
			}
			$p1 = $object1->FLOW3_AOP_Proxy_getProperty($propertyName);
			$p2 = $object2->FLOW3_AOP_Proxy_getProperty($propertyName);
			if ($p1 !== $p2) {
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Register an object's clean state, e.g. after it has been reconstituted
	 * from the FLOW3 persistence layer
	 *
	 * The method takes an optional argument $propertyName to mark only the
	 * specified property as clean. This was used in conjunction with lazy
	 * loading...
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return void
	 * @before F3\FLOW3\Persistence\Aspect\DirtyMonitoring->needsDirtyCheckingAspect && method(.*->FLOW3_Persistence_memorizeCleanState())
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function memorizeCleanState(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$proxy = $joinPoint->getProxy();

		if ($joinPoint->getMethodArgument('propertyName') !== NULL) {
			$propertyNames = array($joinPoint->getMethodArgument('propertyName'));
		} else {
			$propertyNames = array_keys($this->reflectionService->getClassSchema($joinPoint->getClassName())->getProperties());
		}

		foreach ($propertyNames as $propertyName) {
			if (is_object($proxy->FLOW3_AOP_Proxy_getProperty($propertyName))) {
				$proxy->FLOW3_Persistence_cleanProperties[$propertyName] = clone $proxy->FLOW3_AOP_Proxy_getProperty($propertyName);
			} else {
				$proxy->FLOW3_Persistence_cleanProperties[$propertyName] = $proxy->FLOW3_AOP_Proxy_getProperty($propertyName);
			}
		}
	}

	/**
	 * Mark object as cloned after cloning.
	 *
	 * Note: this is done even if an object explicitly implements the
	 * DirtyMonitoringInterface to make sure it is proxied by the AOP
	 * framework (we need that to happen)
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return void
	 * @afterreturning method(.*->__clone())
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cloneObject(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$proxy = $joinPoint->getProxy();
		$proxy->FLOW3_Persistence_clone = TRUE;
	}
}
?>
