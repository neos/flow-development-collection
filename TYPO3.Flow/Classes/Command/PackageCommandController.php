<?php
namespace TYPO3\FLOW3\Command;

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
use TYPO3\FLOW3\Core\Booting\Scripts;

/**
 * Package command controller to handle packages from CLI (create/activate/deactivate packages)
 *
 * @FLOW3\Scope("singleton")
 */
class PackageCommandController extends \TYPO3\FLOW3\MVC\Controller\CommandController {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @param array $settings The FLOW3 settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Create a new package
	 *
	 * This command creates a new package which contains only the mandatory
	 * directories and files.
	 *
	 * @FLOW3\FlushesCaches
	 * @param string $packageKey The package key of the package to create
	 * @return string
	 * @see typo3.kickstart:kickstart:package
	 */
	public function createCommand($packageKey) {
		if (!$this->packageManager->isPackageKeyValid($packageKey)) {
			$this->outputLine('The package key "%s" is not valid.', array($packageKey));
			$this->quit(1);
		}
		if ($this->packageManager->isPackageAvailable($packageKey)) {
			$this->outputLine('The package "%s" already exists.', array($packageKey));
			$this->quit(1);
		}
		$package = $this->packageManager->createPackage($packageKey);
		$this->outputLine('Created new package "' . $packageKey . '" at "' . $package->getPackagePath() . '".');
	}

	/**
	 * Delete an existing package
	 *
	 * This command deletes an existing package identified by the package key.
	 *
	 * @FLOW3\FlushesCaches
	 * @param string $packageKey The package key of the package to create
	 * @return string
	 */
	public function deleteCommand($packageKey) {
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			$this->outputLine('The package "%s" does not exist.', array($packageKey));
			$this->quit(1);
		}
		$this->packageManager->deletePackage($packageKey);
		$this->outputLine('Deleted package "%s".', array($packageKey));
		Scripts::executeCommand('typo3.flow3:cache:flush', $this->settings);
		$this->sendAndExit(0);
	}

	/**
	 * Activate an available package
	 *
	 * This command activates an existing, but currently inactive package.
	 *
	 * @FLOW3\FlushesCaches
	 * @param string $packageKey The package key of the package to create
	 * @return string
	 * @see typo3.flow3:package:deactivate
	 */
	public function activateCommand($packageKey) {
		if ($this->packageManager->isPackageActive($packageKey)) {
			$this->outputLine('Package "%s" is already active.', array($packageKey));
			$this->quit(1);
		}

		$this->packageManager->activatePackage($packageKey);
		$this->outputLine('Activated package "%s".', array($packageKey));
		Scripts::executeCommand('typo3.flow3:cache:flush', $this->settings);
		$this->sendAndExit(0);
	}

	/**
	 * Deactivate a package
	 *
	 * This command deactivates a currently active package.
	 *
	 * @FLOW3\FlushesCaches
	 * @param string $packageKey The package key of the package to create
	 * @return string
	 * @see typo3.flow3:package:activate
	 */
	public function deactivateCommand($packageKey) {
		if (!$this->packageManager->isPackageActive($packageKey)) {
			$this->outputLine('Package "%s" was not active.', array($packageKey));
			$this->quit(1);
		}

		$this->packageManager->deactivatePackage($packageKey);
		$this->outputLine('Deactivated package "%s".', array($packageKey));
		Scripts::executeCommand('typo3.flow3:cache:flush', $this->settings);
		$this->sendAndExit(0);
	}

	/**
	 * List available packages
	 *
	 * Lists all locally available packages. Displays the package key, version and
	 * package title and its state – active or inactive.
	 *
	 * @return string The list of packages
	 * @see typo3.flow3:package:activate
	 * @see typo3.flow3:package:deactivate
	 */
	public function listCommand() {
		$activePackages = array();
		$inactivePackages = array();
		$longestPackageKey = 0;
		foreach ($this->packageManager->getAvailablePackages() as $packageKey => $package) {
			if (strlen($packageKey) > $longestPackageKey) {
				$longestPackageKey = strlen($packageKey);
			}
			if ($this->packageManager->isPackageActive($packageKey)) {
				$activePackages[$packageKey] = $package;
			} else {
				$inactivePackages[$packageKey] = $package;
			}
		}

		ksort($activePackages);
		ksort($inactivePackages);

		$this->outputLine('ACTIVE PACKAGES:');
		foreach ($activePackages as $package) {
			$packageMetaData = $package->getPackageMetaData();
			$this->outputLine(' ' . str_pad($package->getPackageKey(), $longestPackageKey + 3) . str_pad($packageMetaData->getVersion(), 15) . $packageMetaData->getTitle());
		}

		if (count($inactivePackages) > 0) {
			$this->outputLine();
			$this->outputLine('INACTIVE PACKAGES:');
			foreach ($inactivePackages as $package) {
				$packageMetaData = $package->getPackageMetaData();
				$this->outputLine(' ' . str_pad($package->getPackageKey(), $longestPackageKey + 3) . str_pad($packageMetaData->getVersion(), 15) . $packageMetaData->getTitle());
			}
		}
	}

	/**
	 * Import a package from a remote location
	 *
	 * Imports the specified package from a remote git repository.
	 * The imported package will not be activated automatically.
	 *
	 * Currently only packages located at forge.typo3.org are supported.
	 * Note that the git binary must be available
	 *
	 * @param string $packageKey The package key of the package to import
	 * @return void
	 * @see typo3.flow3:package:activate
	 * @see typo3.flow3:package:create
	 */
	public function importCommand($packageKey) {
		try {
			$this->packageManager->importPackage($packageKey);
			$this->outputLine('Imported package %s.', array($packageKey));
			Scripts::executeCommand('typo3.flow3:cache:flush', $this->settings);
			$this->sendAndExit(0);
		} catch (\TYPO3\FLOW3\Package\Exception $exception) {
			$this->outputLine($exception->getMessage());
			$this->quit(1);
		}
	}

}

?>
