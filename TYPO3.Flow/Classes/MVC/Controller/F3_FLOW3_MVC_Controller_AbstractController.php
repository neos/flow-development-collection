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
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_Controller_AbstractController.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * An abstract base class for Controllers
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_Controller_AbstractController.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
abstract class F3_FLOW3_MVC_Controller_AbstractController {

	/**
	 * @var F3_FLOW3_Component_ManagerInterface A reference to the Component Manager
	 */
	protected $componentManager;

	/**
	 * @var F3_FLOW3_Package_ManagerInterface A reference to the Package Manager
	 */
	protected $packageManager;

	/**
	 * @var string Key of the package this controller belongs to
	 */
	protected $packageKey;

	/**
	 * @var F3_FLOW3_Package_Package The package this controller belongs to
	 */
	protected $package;

	/**
	 * Constructs the controller.
	 *
	 * @param F3_FLOW3_Component_ManagerInterface $componentManager: A reference to the Component Manager
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_Component_ManagerInterface $componentManager, F3_FLOW3_Package_ManagerInterface $packageManager) {
		$this->componentManager = $componentManager;
		$this->packageManager = $packageManager;
		list($dummy, $this->packageKey) = explode('_', get_class($this));
		$this->package = $this->packageManager->getPackage($this->packageKey);
	}

	/**
	 * Initializes this component after all dependencies have been resolved.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeComponent() {
		$this->initializeController();
	}

	/**
	 * Initializes this controller.
	 *
	 * Override this method for initializing your concrete controller implementation.
	 * Recommended actions for your controller initialization method are setting up the expected
	 * arguments and narrowing down the supported request types if neccessary.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function initializeController() {
	}
}

?>