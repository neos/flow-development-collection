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
	 * @var F3::FLOW3::Component::FactoryInterface A reference to the Component Factory
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
	 * Constructs the view.
	 *
	 * @param F3::FLOW3::Component::FactoryInterface $componentFactory A reference to the Component Factory
	 * @param F3::FLOW3::Package::ManagerInterface $packageManager A reference to the Package Manager
	 * @param F3::FLOW3::Resource::Manager $resourceManager A reference to the Resource Manager
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(F3::FLOW3::Component::FactoryInterface $componentFactory, F3::FLOW3::Package::ManagerInterface $packageManager, F3::FLOW3::Resource::Manager $resourceManager) {
		$this->componentFactory = $componentFactory;
		$this->packageManager = $packageManager;
		$this->resourceManager = $resourceManager;
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