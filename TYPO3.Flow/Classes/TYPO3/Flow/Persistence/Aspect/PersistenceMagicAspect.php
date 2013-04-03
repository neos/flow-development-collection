<?php
namespace TYPO3\Flow\Persistence\Aspect;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Utility\Algorithms;

/**
 * Adds the aspect of persistence magic to relevant objects
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 * @Flow\Introduce("TYPO3\Flow\Persistence\Aspect\PersistenceMagicAspect->isEntityOrValueObject", interfaceName="TYPO3\Flow\Persistence\Aspect\PersistenceMagicInterface")
 */
class PersistenceMagicAspect {

	/**
	 * If the extension "igbinary" is installed, use it for increased performance
	 *
	 * @var boolean
	 */
	protected $useIgBinary;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @Flow\Pointcut("classAnnotatedWith(TYPO3\Flow\Annotations\Entity) || classAnnotatedWith(Doctrine\ORM\Mapping\Entity)")
	 */
	public function isEntity() {}

	/**
	 * @Flow\Pointcut("TYPO3\Flow\Persistence\Aspect\PersistenceMagicAspect->isEntity || classAnnotatedWith(TYPO3\Flow\Annotations\ValueObject)")
	 */
	public function isEntityOrValueObject() {}

	/**
	 * @var string
	 * @ORM\Id
	 * @ORM\Column(length=40)
	 * @Flow\Introduce("TYPO3\Flow\Persistence\Aspect\PersistenceMagicAspect->isEntityOrValueObject && filter(TYPO3\Flow\Persistence\Doctrine\Mapping\Driver\FlowAnnotationDriver)")
	 */
	protected $Persistence_Object_Identifier;

	/**
	 * Initializes this aspect
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->useIgBinary = extension_loaded('igbinary');
	}

	/**
	 * After returning advice, making sure we have an UUID for each and every entity.
	 *
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
	 * @return void
	 * @Flow\Before("TYPO3\Flow\Persistence\Aspect\PersistenceMagicAspect->isEntity && method(.*->(__construct|__clone)()) && filter(TYPO3\Flow\Persistence\Doctrine\Mapping\Driver\FlowAnnotationDriver)")
	 */
	public function generateUuid(JoinPointInterface $joinPoint) {
		/** @var $proxy \TYPO3\Flow\Persistence\Aspect\PersistenceMagicInterface */
		$proxy = $joinPoint->getProxy();
		ObjectAccess::setProperty($proxy, 'Persistence_Object_Identifier', Algorithms::generateUUID(), TRUE);
		$this->persistenceManager->registerNewObject($proxy);
	}

	/**
	 * After returning advice, generates the value hash for the object
	 *
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
	 * @return void
	 * @Flow\Before("classAnnotatedWith(TYPO3\Flow\Annotations\ValueObject) && method(.*->__construct()) && filter(TYPO3\Flow\Persistence\Doctrine\Mapping\Driver\FlowAnnotationDriver)")
	 */
	public function generateValueHash(JoinPointInterface $joinPoint) {
		$proxy = $joinPoint->getProxy();
		$hashSource = get_class($proxy);
		if (property_exists($proxy, 'Persistence_Object_Identifier')) {
			$hashSource .= ObjectAccess::getProperty($proxy, 'Persistence_Object_Identifier', TRUE);
		}
		foreach ($joinPoint->getMethodArguments() as $argumentValue) {
			if (is_array($argumentValue)) {
				$hashSource .= ($this->useIgBinary === TRUE) ? igbinary_serialize($argumentValue) : serialize($argumentValue);
			} elseif (!is_object($argumentValue)) {
				$hashSource .= $argumentValue;
			} elseif (property_exists($argumentValue, 'Persistence_Object_Identifier')) {
				$hashSource .= ObjectAccess::getProperty($argumentValue, 'Persistence_Object_Identifier', TRUE);
			} elseif ($argumentValue instanceof \DateTime) {
				$hashSource .= $argumentValue->getTimestamp();
			}
		}
		$proxy = $joinPoint->getProxy();
		ObjectAccess::setProperty($proxy, 'Persistence_Object_Identifier', sha1($hashSource), TRUE);
	}

	/**
	 * Mark object as cloned after cloning.
	 *
	 * Note: this is not used by anything in the Flow base distribution,
	 * but might be needed by custom backends (like TYPO3.CouchDB).
	 *
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return void
	 * @Flow\AfterReturning("TYPO3\Flow\Persistence\Aspect\PersistenceMagicAspect->isEntityOrValueObject && method(.*->__clone())")
	 */
	public function cloneObject(JoinPointInterface $joinPoint) {
		$joinPoint->getProxy()->Flow_Persistence_clone = TRUE;
	}

}
?>
