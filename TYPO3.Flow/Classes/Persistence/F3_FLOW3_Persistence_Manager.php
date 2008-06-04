<?php
declare(ENCODING = 'utf-8');

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
 * The FLOW3 Persistence Manager
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Persistence_Manager {

	/**
	 * The component manager
	 *
	 * @var F3_FLOW3_Component_ManagerInterface
	 */
	protected $componentManager;

	/**
	 * Schemata of all classes which need to be persisted
	 *
	 * @var array of F3_FLOW3_Persistence_ClassSchema
	 */
	protected $classSchemata = array();

	/**
	 * Constructor
	 *
	 * @param F3_FLOW3_Component_ManagerInterface $componentManager
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
	}

	/**
	 * Initializes the persistence manager
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initialize() {
		$loadedFromCache = FALSE;
		if (!$loadedFromCache) {
			$namesOfAvailableClasses = array();
			foreach ($this->componentManager->getComponentConfigurations() as $componentConfiguration) {
				$namesOfAvailableClasses[] = $componentConfiguration->getClassName();
			}
			$this->classSchemata = $this->buildClassSchemataFromClasses($namesOfAvailableClasses);
		}
	}

	/**
	 * Builds class schemata from the specified classes. Only classes which are the root
	 * or part of an Aggregate (ie. repositories, entities and value objects) are taken
	 * into consideration.
	 *
	 * @param array $classNames Names of the classes to take into account.
	 * @return array of F3_FLOW3_Persistence_ClassSchema
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildClassSchemataFromClasses(array $classNames) {
		$classSchemata = array();
		foreach ($classNames as $className) {
			$class = new F3_FLOW3_Reflection_Class($className);
			if ($class->isTaggedWith('repository') || $class->isTaggedWith('entity') || $class->isTaggedWith('valueobject')) {
				$classSchemata[$className] = F3_FLOW3_Persistence_ClassSchemaBuilder::build($class);
			}
		}
		return $classSchemata;
	}

}
?>