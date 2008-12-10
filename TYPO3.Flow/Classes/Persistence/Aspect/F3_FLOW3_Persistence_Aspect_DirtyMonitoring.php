<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\Aspect;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 */

/**
 * Adds the aspect of persistence to repositories
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @aspect
 */
class DirtyMonitoring {

	/**
	 * The persistence manager
	 *
	 * @var \F3\FLOW3\Persistence\Manager
	 */
	protected $persistenceManager;

	/**
	 * Injects the persistence manager
	 *
	 * @param \F3\FLOW3\Persistence\Manager $persistenceManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPersistenceManager(\F3\FLOW3\Persistence\Manager $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * @pointcut classTaggedWith(entity) || classTaggedWith(valueobject)
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isEntityOrValueObject() {}

	/**
	 * Automatically call memorizeCleanState() after __wakeup()
	 *
	 * @afterreturning method(.*->__wakeup()) && F3\FLOW3\Persistence\Aspect\DirtyMonitoring->isEntityOrValueObject
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function autoMemorizeCleanState(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$joinPoint->getProxy()->memorizeCleanState($joinPoint);
	}

	/**
	 * @introduce F3\FLOW3\Persistence\Aspect\DirtyMonitoringInterface, F3\FLOW3\Persistence\Aspect\DirtyMonitoring->isEntityOrValueObject
	 */
	public $dirtyMonitoringInterface;

	/**
	 * Around advice, implements the isNew() method introduced above
	 *
	 * @param \F3\FLOW3\AOPJoinPointInterface $joinPoint The current join point
	 * @return boolean
	 * @around method(.*->isNew())
	 * @see \F3\FLOW3\Persistence\Aspect\DirtyMonitoringInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isNew(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$joinPoint->getAdviceChain()->proceed($joinPoint);

		$proxy = $joinPoint->getProxy();
		return !property_exists($proxy, 'FLOW3PersistenceCleanProperties');
	}

	/**
	 * Around advice, implements the isDirty() method introduced above
	 *
	 * @param \F3\FLOW3\AOPJoinPointInterface $joinPoint The current join point
	 * @return boolean
	 * @around method(.*->isDirty())
	 * @see \F3\FLOW3\Persistence\Aspect\DirtyMonitoringInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isDirty(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$joinPoint->getAdviceChain()->proceed($joinPoint);

		$proxy = $joinPoint->getProxy();
		$isDirty = FALSE;

		if (property_exists($proxy, 'FLOW3PersistenceCleanProperties')) {
			$cleanProperties = $proxy->FLOW3PersistenceCleanProperties;
			$identifierProperty = $this->persistenceManager->getClassSchema($joinPoint->getClassName())->getIdentifierProperty();
			if ($identifierProperty !== NULL && $proxy->AOPProxyGetProperty($identifierProperty) != $cleanProperties[$identifierProperty]) {
				throw new \F3\FLOW3\Persistence\Exception\TooDirty('My property "' . $identifierProperty . '" tagged as @identifier has been modified, that is simply too much.', 1222871239);
			}

			$propertyName = $joinPoint->getMethodArgument('propertyName');
			if ($cleanProperties[$propertyName] !== $proxy->AOPProxyGetProperty($propertyName)) {
				$isDirty = TRUE;
			}
		}

		return $isDirty;
	}

	/**
	 * Register an object's clean state, e.g. after it has been reconstituted
				from the FLOW3 persistence layer
	 *
	 * @before method(.*->memorizeCleanState())
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function memorizeCleanState(\F3\FLOW3\AOP\JoinPointInterface $joinPoint = NULL) {
		if ($joinPoint === NULL) {
			$proxy = $this;
		} else {
			$proxy = $joinPoint->getProxy();
		}
		$cleanProperties = array();
		$propertyNames = array_keys($this->persistenceManager->getClassSchema($joinPoint->getClassName())->getProperties());

		foreach ($propertyNames as $propertyName) {
			$cleanProperties[$propertyName] = $proxy->AOPProxyGetProperty($propertyName);
		}
		$proxy->FLOW3PersistenceCleanProperties = $cleanProperties;
	}

}
?>
