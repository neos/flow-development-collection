<?php
namespace TYPO3\Flow\Composer;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Composer\Script\CommandEvent;
use Composer\Script\PackageEvent;
use TYPO3\Flow\Utility\Files;

/**
 * Class for Composer install scripts
 */
class InstallerScripts {

	/**
	 * Make sure required paths and files are available outside of Package
	 * Run on every Composer install or update - most be configured in root manifest
	 *
	 * @param \Composer\Script\Event $event
	 * @return void
	 */
	static public function postUpdateAndInstall(CommandEvent $event) {
		Files::createDirectoryRecursively('Configuration');
		Files::createDirectoryRecursively('Data');

		Files::copyDirectoryRecursively('Packages/Framework/TYPO3.Flow/Resources/Private/Installer/Distribution/Essentials', '.', FALSE, TRUE);
		Files::copyDirectoryRecursively('Packages/Framework/TYPO3.Flow/Resources/Private/Installer/Distribution/Defaults', '.', TRUE, TRUE);

		chmod('flow', 0755);
	}

	/**
	 * Calls actions and install scripts provided by installed packages.
	 *
	 * @param \Composer\Script\PackageEvent $event
	 * @return void
	 * @throws Exception\UnexpectedOperationException
	 */
	static public function postPackageUpdateAndInstall(PackageEvent $event) {
		$operation = $event->getOperation();
		if (!$operation instanceof \Composer\DependencyResolver\Operation\InstallOperation &&
			!$operation instanceof \Composer\DependencyResolver\Operation\UpdateOperation) {
			throw new Exception\UnexpectedOperationException('Handling of operation with type "' . $operation->getJobType() . '" not supported', 1348750840);
		}
		$package = ($operation->getJobType() === 'install') ? $operation->getPackage() : $operation->getTargetPackage();
		$packageExtraConfig = $package->getExtra();

		if (isset($packageExtraConfig['typo3/flow'])) {
			if (isset($packageExtraConfig['typo3/flow']['post-install']) && $operation->getJobType() === 'install') {
				self::runPackageScripts($packageExtraConfig['typo3/flow']['post-install']);
			} elseif (isset($packageExtraConfig['typo3/flow']['post-update']) && $operation->getJobType() === 'update') {
				self::runPackageScripts($packageExtraConfig['typo3/flow']['post-update']);
			}

			$installPath = $event->getComposer()->getInstallationManager()->getInstallPath($package);
			$relativeInstallPath = str_replace(getcwd() . '/', '', $installPath);

			if (isset($packageExtraConfig['typo3/flow']['manage-resources']) && $packageExtraConfig['typo3/flow']['manage-resources'] === TRUE) {
				if (is_dir($relativeInstallPath . '/Resources/Private/Installer/Distribution/Essentials')) {
					Files::copyDirectoryRecursively($relativeInstallPath . '/Resources/Private/Installer/Distribution/Essentials', '.', FALSE, TRUE);
				}
				if (is_dir($relativeInstallPath . '/Resources/Private/Installer/Distribution/Defaults')) {
					Files::copyDirectoryRecursively($relativeInstallPath . '/Resources/Private/Installer/Distribution/Defaults', '.', TRUE, TRUE);
				}
			}
		}
	}

	/**
	 * Calls a static method from it's string representation
	 *
	 * @param string $staticMethodReference
	 * @return void
	 * @throws Exception\InvalidConfigurationException
	 */
	static protected function runPackageScripts($staticMethodReference) {
		$className = substr($staticMethodReference, 0, strpos($staticMethodReference, '::'));
		$methodName = substr($staticMethodReference, strpos($staticMethodReference, '::') + 2);

		if (!class_exists($className)) {
			throw new Exception\InvalidConfigurationException('Class "' . $className . '" is not autoloadable, can not call "' . $staticMethodReference . '"', 1348751076);
		}
		if (!is_callable($staticMethodReference)) {
			throw new Exception\InvalidConfigurationException('Method "' . $staticMethodReference. '" is not callable', 1348751082);
		}
		$className::$methodName();
	}
}
