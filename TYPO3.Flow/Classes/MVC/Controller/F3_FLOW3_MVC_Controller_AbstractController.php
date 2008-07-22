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
	 * @var F3_FLOW3_Component_FactoryInterface A reference to the Component Factory
	 */
	protected $componentFactory;

	/**
	 * @var string Key of the package this controller belongs to
	 */
	protected $packageKey;

	/**
	 * @var F3_FLOW3_Package_Package The package this controller belongs to
	 */
	protected $package;

	/**
	 * Contains the settings of the current package
	 *
	 * @var F3_FLOW3_Configuration_Container
	 */
	protected $settings;

	/**
	 * Constructs the controller.
	 *
	 * @param F3_FLOW3_Component_FactoryInterface $componentFactory A reference to the Component Factory
	 * @param F3_FLOW3_Package_ManagerInterface $packageManager A reference to the Package Manager
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_Component_FactoryInterface $componentFactory, F3_FLOW3_Package_ManagerInterface $packageManager) {
		$this->componentFactory = $componentFactory;
		list(, $this->packageKey) = explode('_', get_class($this));
		$this->package = $packageManager->getPackage($this->packageKey);
	}

	/**
	 * Sets / injects the settings of the package this controller belongs to.
	 *
	 * @param F3_FLOW3_Configuration_Container $settings Settings container of the current package
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setSettings(F3_FLOW3_Configuration_Container $settings) {
		$this->settings = $settings;
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