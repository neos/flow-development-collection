<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Fixture\Controller;

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
 * A mock RESTController
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class MockRESTController extends \F3\FLOW3\MVC\Controller\RESTController {

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