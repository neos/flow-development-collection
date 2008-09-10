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
 * @version $Id$
 */

/**
 * A mock RESTController
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_Fixture_Controller_MockRESTController extends F3_FLOW3_MVC_Controller_RESTController {

	/**
	 * A mock list action
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function listAction() {
		return 'list action called';
	}

	/**
	 * A mock show action
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function showAction() {
		return 'show action called';
	}

	/**
	 * A mock edit action
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function editAction() {
		return 'edit action called';
	}

	/**
	 * A mock new action
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function newAction() {
		return 'new action called';
	}

	/**
	 * A mock create action
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createAction() {
		return 'create action called';
	}


	/**
	 * A mock update action
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function updateAction() {
		return 'update action called';
	}


	/**
	 * A mock delete action
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function deleteAction() {
		return 'delete action called';
	}
}

?>