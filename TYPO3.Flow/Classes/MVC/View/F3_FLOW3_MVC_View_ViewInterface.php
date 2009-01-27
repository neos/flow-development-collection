<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\View;

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
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 */

/**
 * Interface of a view
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface ViewInterface {

	/**
	 * Sets the current request
	 *
	 * @param \F3\FLOW3\MVC\Request $request
	 * @return void
	 */
	public function setRequest(\F3\FLOW3\MVC\Request $request);

	/**
	 * Returns an View Helper instance.
	 * View Helpers must implement the interface \F3\FLOW3\MVC\View\Helper\HelperInterface
	 *
	 * @param string $viewHelperObjectName the full name of the View Helper object including namespace
	 * @return \F3\FLOW3\MVC\View\Helper\HelperInterface The View Helper instance
	 */
	public function getViewHelper($viewHelperObjectName);

	/**
	 * Renders the view
	 *
	 * @return string The rendered view
	 */
	public function render();
}

?>