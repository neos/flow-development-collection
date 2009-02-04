<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Package\Controller;

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
 * Package controller to handle packages from CLI (create/activate/deactivate packages)
 *
 * @package FLOW3
 * @subpackage Package
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ManagerController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @var F3\FLOW3\Package\ManagerInterface
	 * @inject
	 */
	protected $packageManager;

	/**
	 * @var array
	 */
	protected $supportedRequestTypes = array('F3\FLOW3\MVC\CLI\Request');

	/**
	 * Default action (no arguments given)
	 * Forwards to the helpAction.
	 *
	 * @return void
	 * @author Tobias Liebig <mail_typo3@etobi.de>
	 */
	public function indexAction() {
		return $this->helpAction();
	}

	/**
	 * Action for creating a new package
	 *
	 * @param string $packageKey The package key of the package to create
	 * @return void
	 * @author Tobias Liebig <mail_typo3@etobi.de>
	 */
	public function createAction($packageKey) {
		if ($packageKey == '') {
			return $this->helpAction();
		}
		if ($this->packageManager->isPackageAvailable($packageKey)) {
			return 'The package "' . $packageKey . '" already exists.' . chr(10);
		}
		$this->packageManager->createPackage($packageKey);
		return 'New package "' . $packageKey . '" created.' . chr(10);
	}

	/**
	 * Action for deleting an existing package
	 *
	 * @param string $packageKey The package key of the package to create
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function deleteAction($packageKey) {
		if ($packageKey == '') {
			return $this->helpAction();
		}
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			return 'The package "' . $packageKey . '" does not exist.' . chr(10);
		}
		$this->packageManager->deletePackage($packageKey);
		return 'Package "' . $packageKey . '" has been deleted.' . chr(10);
	}

	/**
	 * Action for activating a package
	 *
	 * @param string $packageKey The package key of the package to create
	 * @return void
	 * @author Tobias Liebig <mail_typo3@etobi.de>
	 */
	public function activateAction($packageKey) {
		if ($packageKey == '') {
			return $this->helpAction();
		} else {
			$this->packageManager->activatePackage($packageKey);
			return 'package "' . $packageKey . '" activated.' . chr(10);
		}
	}

	/**
	 * Action for deactivating a package
	 *
	 * @param string $packageKey The package key of the package to create
	 * @return void
	 * @author Tobias Liebig <mail_typo3@etobi.de>
	 */
	public function deactivateAction($packageKey) {
		if ($packageKey == '') {
			return $this->helpAction();
		} else {
			$this->packageManager->deactivatePackage($packageKey);
			return 'package "' . $packageKey . '" deactivated.' . chr(10);
		}
	}

	/**
	 * Action for displaying a help screen
	 *
	 * @return void
	 * @author Tobias Liebig <mail_typo3@etobi.de>
	 */
	public function helpAction() {
		return chr(10) .
			'FLOW3 Package CLI Controller' . chr(10) .
			'Usage: php index_dev.php FLOW3 Package <command> package-key=<PACKAGE>' . chr(10).
			chr(10) .
			'<command>:' . chr(10) .
			'  create     - create a new package' . chr(10).
			'  activate   - activate a package' . chr(10).
			'  deactivate - activate a package' . chr(10).
			'  delete     - delete a package' . chr(10)
		;
	}
}

?>