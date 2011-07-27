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
			return 'The package key "' . $packageKey . '" is not valid.' . PHP_EOL;
		}
		if ($this->packageManager->isPackageAvailable($packageKey)) {
			return 'The package "' . $packageKey . '" already exists.' . PHP_EOL;
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
			return 'The package "' . $packageKey . '" does not exist.' . PHP_EOL;
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
			return 'Package "' . $packageKey . '" is already active.' . PHP_EOL;
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
			return 'Package "' . $packageKey . '" was not active.' . PHP_EOL;
		}

		$this->packageManager->deactivatePackage($packageKey);
		echo 'Package "' . $packageKey . '" deactivated.' . PHP_EOL;
		$this->bootstrap->executeCommand('typo3.flow3:cache:flush');
		exit(0);
	}

	/**
	 * List available (active and inactive) packages
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function listAvailableCommand() {
		$packages = $this->packageManager->getAvailablePackages();
		$output = 'Available packages:' . PHP_EOL;
		foreach ($packages as $package) {
			$output .= ' ' . str_pad($package->getPackageKey(), 30) . $package->getPackageMetaData()->getVersion() . PHP_EOL;
		}
		return $output . PHP_EOL;
	}

	/**
	 * List active packages
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function listActiveCommand() {
		$packages = $this->packageManager->getActivePackages();
		$output = 'Active packages:' . PHP_EOL;
		foreach ($packages as $package) {
			$output .= ' ' . str_pad($package->getPackageKey(), 30) . $package->getPackageMetaData()->getVersion() . PHP_EOL;
		}
		return $output . PHP_EOL;
	}

}

?>