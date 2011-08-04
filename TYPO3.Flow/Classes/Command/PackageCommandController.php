<?php
namespace TYPO3\FLOW3\Command;

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
 * Package command controller to handle packages from CLI (create/activate/deactivate packages)
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 */
class PackageCommandController extends \TYPO3\FLOW3\MVC\Controller\CommandController {

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * Create a new package
	 *
	 * @param string $packageKey The package key of the package to create
	 * @return string
	 */
	public function createCommand($packageKey) {
		if (!$this->packageManager->isPackageKeyValid($packageKey)) {
			$this->response->setExitCode(1);
			return 'The package key "' . $packageKey . '" is not valid.';
		}
		if ($this->packageManager->isPackageAvailable($packageKey)) {
			$this->response->setExitCode(2);
			return 'The package "' . $packageKey . '" already exists.';
		}
		$package = $this->packageManager->createPackage($packageKey);
		echo 'New package "' . $packageKey . '" created at "' . $package->getPackagePath() . '".' . PHP_EOL;
		$this->bootstrap->executeCommand('typo3.flow3:cache:flush');
		exit(0);
	}

	/**
	 * Delete an existing package
	 *
	 * @param string $packageKey The package key of the package to create
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function deleteCommand($packageKey) {
		if ($packageKey === '') {
			return $this->helpCommand();
		}
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			$this->response->setExitCode(1);
			return 'The package "' . $packageKey . '" does not exist.';
		}
		$this->packageManager->deletePackage($packageKey);
		echo 'Package "' . $packageKey . '" has been deleted.' . PHP_EOL;
		$this->bootstrap->executeCommand('typo3.flow3:cache:flush');
		exit(0);
	}

	/**
	 * Activate an available package
	 *
	 * @param string $packageKey The package key of the package to create
	 * @return string
	 * @author Tobias Liebig <mail_typo3@etobi.de>
	 */
	public function activateCommand($packageKey) {
		if ($packageKey === '') {
			return $this->helpCommand();
		}

		if ($this->packageManager->isPackageActive($packageKey)) {
			$this->response->setExitCode(1);
			return 'Package "' . $packageKey . '" is already active.';
		}

		$this->packageManager->activatePackage($packageKey);
		echo 'Package "' . $packageKey . '" activated.' . PHP_EOL;
		$this->bootstrap->executeCommand('typo3.flow3:cache:flush');
		exit(0);
	}

	/**
	 * Deactivate a package
	 *
	 * @param string $packageKey The package key of the package to create
	 * @return string
	 * @author Tobias Liebig <mail_typo3@etobi.de>
	 */
	public function deactivateCommand($packageKey) {
		if (!$this->packageManager->isPackageActive($packageKey)) {
			$this->response->setExitCode(1);
			return 'Package "' . $packageKey . '" was not active.';
		}

		$this->packageManager->deactivatePackage($packageKey);
		echo 'Package "' . $packageKey . '" deactivated.' . PHP_EOL;
		$this->bootstrap->executeCommand('typo3.flow3:cache:flush');
		exit(0);
	}

	/**
	 * List available packages
	 *
	 * Lists all locally available packages. Displays the package key, version and package title and its state –
	 * active or inactive.
	 *
	 * @return string The list of packages
	 * @author Robert Lemke <robert@typo3.org>
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

		$output = 'ACTIVE PACKAGES:' . PHP_EOL;
		foreach ($activePackages as $package) {
			$packageMetaData = $package->getPackageMetaData();
			$output .= ' ' . str_pad($package->getPackageKey(), $longestPackageKey + 3) . str_pad($packageMetaData->getVersion(), 15) . $packageMetaData->getTitle() . PHP_EOL;
		}

		if (count($inactivePackages) > 0) {
			$output .= PHP_EOL . 'INACTIVE PACKAGES:' . PHP_EOL;
			foreach ($inactivePackages as $package) {
				$packageMetaData = $package->getPackageMetaData();
				$output .= ' ' . str_pad($package->getPackageKey(), $longestPackageKey + 3) . str_pad($packageMetaData->getVersion(), 15) . $packageMetaData->getTitle() . PHP_EOL;
			}
		}
		return $output;
	}
}

?>