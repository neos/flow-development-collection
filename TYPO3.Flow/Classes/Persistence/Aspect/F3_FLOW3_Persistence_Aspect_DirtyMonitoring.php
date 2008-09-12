<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Persistence::Aspect;

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
	 * @var F3::FLOW3::Persistence::Manager
	 */
	protected $persistenceManager;

	/**
	 * Injects the persistence manager
	 *
	 * @param F3::FLOW3::Persistence::Manager $persistenceManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPersistenceManager(F3::FLOW3::Persistence::Manager $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * @pointcut classTaggedWith(entity) || classTaggedWith(valueobject)
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function entityOrValueObject() {}

	/**
	 * Register an object as new with the FLOW3 persistence manager session
	 *
	 * @afterreturning method(.*->__construct()) && F3::FLOW3::Persistence::Aspect::DirtyMonitoring->entityOrValueObject
	 * @param F3::FLOW3::AOP::JoinPointInterface $joinPoint
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerNewObject(F3::FLOW3::AOP::JoinPointInterface $joinPoint) {
		$this->persistenceManager->getSession()->registerNewObject($joinPoint->getProxy());
	}

	/**
	 * Register an object's clean state after it has been reconstituted from the FLOW3 persistence layer
	 *
	 * @afterreturning method(.*->__wakeup()) && F3::FLOW3::Persistence::Aspect::DirtyMonitoring->entityOrValueObject
	 * @param F3::FLOW3::AOP::JoinPointInterface $joinPoint
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function memorizeCleanState(F3::FLOW3::AOP::JoinPointInterface $joinPoint) {
		$proxy = $joinPoint->getProxy();
		$cleanProperties = array();
		$propertyNames = array_keys($this->persistenceManager->getClassSchema($joinPoint->getClassName())->getProperties());

		foreach ($propertyNames as $propertyName) {
			$cleanProperties[$propertyName] = $proxy->AOPProxyGetProperty($propertyName);
		}
		$proxy->AOPProxySetProperty('FLOW3PersistenceCleanProperties', $cleanProperties);
	}

	/**
	 * @introduce F3::FLOW3::Persistence::Aspect::DirtyMonitoringInterface, F3::FLOW3::Persistence::Aspect::DirtyMonitoring->entityOrValueObject
	 */
	public $dirtyMonitoringInterface;

	/**
	 * Around advice, implements the isDirty() method introduced above
	 *
	 * @param F3::FLOW3::AOPJoinPointInterface $joinPoint The current join point
	 * @return boolean
	 * @around method(.*->isDirty())
	 * @see F3::FLOW3::Persistence::Aspect::DirtyMonitoringInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isDirty(F3::FLOW3::AOP::JoinPointInterface $joinPoint) {
		$joinPoint->getAdviceChain()->proceed($joinPoint);

		$isDirty = FALSE;
		$proxy = $joinPoint->getProxy();
		$cleanProperties = $proxy->AOPProxyGetProperty('FLOW3PersistenceCleanProperties');

		foreach ($cleanProperties as $propertyName => $cleanValue) {
			if ($cleanValue !== $proxy->AOPProxyGetProperty($propertyName)) {
				$isDirty = TRUE;
				break;
			}
		}

		return $isDirty;
	}
}
?>