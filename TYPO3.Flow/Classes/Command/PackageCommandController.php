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
	 * @flushesCaches
	 * @param string $packageKey The package key of the package to create
	 * @return string
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
		$this->outputLine('New package "' . $packageKey . '" created at "' . $package->getPackagePath() . '".');
		$this->bootstrap->executeCommand('typo3.flow3:cache:flush');
		$this->sendAndExit(0);
	}

	/**
	 * Delete an existing package
	 *
	 * @flushesCaches
	 * @param string $packageKey The package key of the package to create
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function deleteCommand($packageKey) {
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			$this->outputLine('The package "%s" does not exist.', array($packageKey));
			$this->quit(1);
		}
		$this->packageManager->deletePackage($packageKey);
		$this->outputLine('Package "%s" has been deleted.', array($packageKey));
		$this->bootstrap->executeCommand('typo3.flow3:cache:flush');
		$this->sendAndExit(0);
	}

	/**
	 * Activate an available package
	 *
	 * @flushesCaches
	 * @param string $packageKey The package key of the package to create
	 * @return string
	 * @author Tobias Liebig <mail_typo3@etobi.de>
	 */
	public function activateCommand($packageKey) {
		if ($this->packageManager->isPackageActive($packageKey)) {
			$this->outputLine('Package "%s" is already active.', array($packageKey));
			$this->quit(1);
		}

		$this->packageManager->activatePackage($packageKey);
		$this->outputLine('Package "%s" activated.', array($packageKey));
		$this->bootstrap->executeCommand('typo3.flow3:cache:flush');
		$this->sendAndExit(0);
	}

	/**
	 * Deactivate a package
	 *
	 * @flushesCaches
	 * @param string $packageKey The package key of the package to create
	 * @return string
	 * @author Tobias Liebig <mail_typo3@etobi.de>
	 */
	public function deactivateCommand($packageKey) {
		if (!$this->packageManager->isPackageActive($packageKey)) {
			$this->outputLine('Package "%s" was not active.', array($packageKey));
			$this->quit(1);
		}

		$this->packageManager->deactivatePackage($packageKey);
		$this->outputLine('Package "%s" deactivated.', array($packageKey));
		$this->bootstrap->executeCommand('typo3.flow3:cache:flush');
		$this->sendAndExit(0);
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
}

?>
