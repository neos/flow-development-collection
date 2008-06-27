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
	 * The reflection service
	 *
	 * @var F3_FLOW3_Reflection_Service
	 */
	protected $reflectionService;

	/**
	 * The class schema builder
	 *
	 * @var F3_FLOW3_Persistence_ClassSchemataBuilder
	 */
	protected $ClassSchemataBuilder;

	/**
	 * @var F3_FLOW3_Persistence_BackendInterface
	 */
	protected $backend;

	/**
	 * Schemata of all classes which need to be persisted
	 *
	 * @var array of F3_FLOW3_Persistence_ClassSchema
	 */
	protected $classSchemata = array();

	/**
	 * Constructor
	 *
	 * @param F3_FLOW3_Reflection_Service $reflectionService
	 * @param F3_FLOW3_Persistence_ClassSchemataBuilder $ClassSchemataBuilder
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_Reflection_Service $reflectionService, F3_FLOW3_Persistence_ClassSchemataBuilder $ClassSchemataBuilder) {
		$this->reflectionService = $reflectionService;
		$this->ClassSchemataBuilder = $ClassSchemataBuilder;
	}

	/**
	 * Set the backend to use for persistence
	 *
	 * @param F3_FLOW3_Persistence_BackendInterface $backend
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setBackend(F3_FLOW3_Persistence_BackendInterface $backend) {
		$this->backend = $backend;
	}

	/**
	 * Initializes the persistence manager
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initialize() {
		$classNames = $this->reflectionService->getClassNamesByTag('repository') +
			$this->reflectionService->getClassNamesByTag('entity') +
			$this->reflectionService->getClassNamesByTag('valueobject');

		$this->classSchemata = $this->ClassSchemataBuilder->build($classNames);
		if ($this->backend instanceof F3_FLOW3_Persistence_BackendInterface) {
			$this->backend->initialize($this->classSchemata);
		}
	}
}
?>