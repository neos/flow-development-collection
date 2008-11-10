<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::MVC::View;

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
 * @version $Id:F3::FLOW3::MVC::View::AbstractView.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * An abstract View
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3::FLOW3::MVC::View::AbstractView.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
abstract class AbstractView {

	/**
	 * @var F3::FLOW3::Component::ManagerInterface A reference to the Component Manager
	 */
	protected $componentFactory;

	/**
	 * @var F3::FLOW3::Package::FactoryInterface A reference to the Package Factory
	 */
	protected $packageManager;

	/**
	 * @var F3::FLOW3::Resource::ManagerInterface
	 */
	protected $resourceManager;

	/**
	 * @var F3::FLOW3::MVC::Request
	 */
	protected $request;

	/**
	 * @var array of F3::FLOW3::MVC::View::Helper::HelperInterface
	 */
	protected $viewHelpers;

	/**
	 * Constructs the view.
	 *
	 * @param F3::FLOW3::Component::ManagerInterface $componentManager A reference to the Component Manager
	 * @param F3::FLOW3::Package::ManagerInterface $packageManager A reference to the Package Manager
	 * @param F3::FLOW3::Resource::Manager $resourceManager A reference to the Resource Manager
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(F3::FLOW3::Component::ManagerInterface $componentManager, F3::FLOW3::Package::ManagerInterface $packageManager, F3::FLOW3::Resource::Manager $resourceManager) {
		$this->componentManager = $componentManager;
		$this->packageManager = $packageManager;
		$this->resourceManager = $resourceManager;
	}

	/**
	 * Initializes the view after all dependencies have been injected
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeComponent() {
		$this->initializeView();
	}

	/**
	 * Sets the current request
	 *
	 * @param F3::FLOW3::MVC::Request $request
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setRequest(F3::FLOW3::MVC::Request $request) {
		$this->request = $request;
	}

	/**
	 * Returns an View Helper instance.
	 * View Helpers must implement the interface F3::FLOW3::MVC::View::Helper::HelperInterface
	 *
	 * @param string $viewHelperClassName the full name of the View Helper Class including namespace
	 * @return F3::FLOW3::MVC::View::Helper::HelperInterface The View Helper instance
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getViewHelper($viewHelperClassName) {
		if (!isset($this->viewHelpers[$viewHelperClassName])) {
			$viewHelper = $this->componentManager->getComponent($viewHelperClassName);
			if (!$viewHelper instanceof F3::FLOW3::MVC::View::Helper::HelperInterface) {
				throw new F3::FLOW3::MVC::Exception::InvalidViewHelper('View Helpers must implement interface "F3::FLOW3::MVC::View::Helper::HelperInterface"', 1222895456);
			}
			$viewHelper->setRequest($this->request);
			$this->viewHelpers[$viewHelperClassName] = $viewHelper;
		}
		return $this->viewHelpers[$viewHelperClassName];
	}

	/**
	 * Initializes this view.
	 *
	 * Override this method for initializing your concrete view implementation.
	 *
	 * @return void
	 */
	protected function initializeView() {
	}

	/**
	 * Renders the view
	 *
	 * @return string The rendered view
	 */
	abstract public function render();
}

?>
