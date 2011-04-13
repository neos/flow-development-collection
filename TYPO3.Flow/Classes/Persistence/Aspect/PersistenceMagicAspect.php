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
 * Adds the aspect of persistence magic to relevant objects
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @aspect
 * @introduce F3\FLOW3\Persistence\Aspect\PersistenceMagicInterface, F3\FLOW3\Persistence\Aspect\PersistenceMagicAspect->needsPersistenceMagicAspect
 */
class PersistenceMagicAspect {

	/**
	 * If the extension "igbinary" is installed, use it for increased performance
	 *
	 * @var boolean
	 */
	protected $useIgBinary;

	/**
	 * The reflection service
	 *
	 * @var \F3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @pointcut classTaggedWith(entity) || classTaggedWith(valueobject)
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isEntityOrValueObject() {}

	/**
	 * @pointcut F3\FLOW3\Persistence\Aspect\PersistenceMagicAspect->isEntityOrValueObject && !within(F3\FLOW3\Persistence\Aspect\PersistenceMagicInterface)
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function needsPersistenceMagicAspect() {}

	/**
	 * @introduce F3\FLOW3\Persistence\Aspect\PersistenceMagicAspect->needsPersistenceMagicAspect
	 * @var string
	 */
	protected $FLOW3_Persistence_Identifier;

	/**
	 * Injects the reflection service
	 *
	 * @param \F3\FLOW3\Reflection\ReflectionService $reflectionService
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Initializes this aspect
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObject() {
		$this->useIgBinary = extension_loaded('igbinary');
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
		\F3\FLOW3\Reflection\ObjectAccess::setProperty($proxy, 'FLOW3_Persistence_Identifier', \F3\FLOW3\Utility\Algorithms::generateUUID(), TRUE);
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
			$propertyValue = \F3\FLOW3\Reflection\ObjectAccess::getProperty($proxy, $propertyName, TRUE);
			if (is_array($propertyValue)) {
				$hashSource .= ($this->useIgBinary === TRUE) ? igbinary_serialize($propertyValue) : serialize($propertyValue);
			} elseif (!is_object($propertyValue)) {
				$hashSource .= $propertyValue;
			} elseif (property_exists($propertyValue, 'FLOW3_Persistence_Identifier')) {
				$hashSource .= \F3\FLOW3\Reflection\ObjectAccess::getProperty($propertyValue, 'FLOW3_Persistence_Identifier', TRUE);
			}
		}
		\F3\FLOW3\Reflection\ObjectAccess::setProperty($proxy, 'FLOW3_Persistence_Identifier', sha1($hashSource), TRUE);
	}

	/**
	 * Around advice, implements the FLOW3_Persistence_isClone() method introduced above
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return boolean if the object is a clone
	 * @around F3\FLOW3\Persistence\Aspect\PersistenceMagicAspect->needsPersistenceMagicAspect && method(.*->FLOW3_Persistence_isClone())
	 * @see \F3\FLOW3\Persistence\Aspect\PersistenceMagicInterface
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isClone(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$joinPoint->getAdviceChain()->proceed($joinPoint);

		$proxy = $joinPoint->getProxy();
		return property_exists($proxy, 'FLOW3_Persistence_clone');
	}

	/**
	 * Mark object as cloned after cloning.
	 *
	 * Note: this is done even if an object explicitly implements the
	 * PersistenceMagicInterface to make sure it is proxied by the AOP
	 * framework (we need that to happen)
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return void
	 * @afterreturning F3\FLOW3\Persistence\Aspect\PersistenceMagicAspect->isEntityOrValueObject && method(.*->__clone())
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cloneObject(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$joinPoint->getProxy()->FLOW3_Persistence_clone = TRUE;
	}

}
?>
