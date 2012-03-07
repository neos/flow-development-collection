<?php
namespace TYPO3\FLOW3\Package\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Package controller to handle packages from CLI (create/activate/deactivate packages)
 *
 * @FLOW3\Scope("singleton")
 */
class PackageManagerController extends \TYPO3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @var \TYPO3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @var array
	 */
	protected $supportedRequestTypes = array('TYPO3\FLOW3\MVC\CLI\Request');

	/**
	 * Injects the package manager
	 *
	 * @param \TYPO3\FLOW3\Package\PackageManagerInterface $packageManager
	 * @return void
	 */
	public function injectPackageManager(\TYPO3\FLOW3\Package\PackageManagerInterface $packageManager) {
		$this->packageManager = $packageManager;
	}

	/**
	 * Default action (no arguments given)
	 * Forwards to the helpAction.
	 *
	 * @return string
	 */
	public function indexAction() {
		return $this->helpAction();
	}

	/**
	 * Action for creating a new package
	 *
	 * @param string $packageKey The package key of the package to create
	 * @return string
	 */
	public function createAction($packageKey) {
		if ($packageKey === '') {
			return $this->helpAction();
		}
		if (!$this->packageManager->isPackageKeyValid($packageKey)) {
			return 'The package key "' . $packageKey . '" is not valid.' . PHP_EOL;
		}
		if ($this->packageManager->isPackageAvailable($packageKey)) {
			return 'The package "' . $packageKey . '" already exists.' . PHP_EOL;
		}
		$package = $this->packageManager->createPackage($packageKey);
		return 'New package "' . $packageKey . '" created at "' . $package->getPackagePath() . '".' . PHP_EOL;
	}

	/**
	 * Action for deleting an existing package
	 *
	 * @param string $packageKey The package key of the package to create
	 * @return string
	 */
	public function deleteAction($packageKey) {
		if ($packageKey === '') {
			return $this->helpAction();
		}
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			return 'The package "' . $packageKey . '" does not exist.' . PHP_EOL;
		}
		$this->packageManager->deletePackage($packageKey);
		return 'Package "' . $packageKey . '" has been deleted.' . PHP_EOL;
	}

	/**
	 * Action for activating a package
	 *
	 * @param string $packageKey The package key of the package to create
	 * @return string
	 */
	public function activateAction($packageKey) {
		if ($packageKey === '') {
			return $this->helpAction();
		}

		if ($this->packageManager->isPackageActive($packageKey)) {
			return 'Package "' . $packageKey . '" is already active.' . PHP_EOL;
		}

		$this->packageManager->activatePackage($packageKey);
		return 'Package "' . $packageKey . '" activated.' . PHP_EOL;
	}

	/**
	 * Action for deactivating a package
	 *
	 * @param string $packageKey The package key of the package to create
	 * @return string
	 */
	public function deactivateAction($packageKey) {
		if ($packageKey === '') {
			return $this->helpAction();
		}

		if (!$this->packageManager->isPackageActive($packageKey)) {
			return 'Package "' . $packageKey . '" was not active.' . PHP_EOL;
		}

		$this->packageManager->deactivatePackage($packageKey);
		return 'Package "' . $packageKey . '" deactivated.' . PHP_EOL;
	}

	/**
	 * Action for listing active packages
	 *
	 * @return string
	 */
	public function listAvailableAction() {
		$packages = $this->packageManager->getAvailablePackages();
		$output = 'Available packages:' . PHP_EOL;
		foreach ($packages as $package) {
			$output .= ' ' . str_pad($package->getPackageKey(), 30) . $package->getPackageMetaData()->getVersion() . PHP_EOL;
		}
		return $output . PHP_EOL;
	}

	/**
	 * Action for listing active packages
	 *
	 * @return string
	 */
	public function listActiveAction() {
		$packages = $this->packageManager->getActivePackages();
		$output = 'Active packages:' . PHP_EOL;
		foreach ($packages as $package) {
			$output .= ' ' . str_pad($package->getPackageKey(), 30) . $package->getPackageMetaData()->getVersion() . PHP_EOL;
		}
		return $output . PHP_EOL;
	}

	/**
	 * Action for displaying a help screen
	 *
	 * @return string
	 */
	public function helpAction() {
		return PHP_EOL .
			'FLOW3 Package CLI Controller' . PHP_EOL .
			'Usage: php Public/index.php FLOW3 Package Manager <command> --package-key=<PACKAGE>' . PHP_EOL.
			PHP_EOL .
			'<command>:' . PHP_EOL .
			'  create     - create a new package' . PHP_EOL.
			'  activate   - activate a package' . PHP_EOL.
			'  deactivate - activate a package' . PHP_EOL.
			'  delete     - delete a package' . PHP_EOL
		;
	}
}

?>