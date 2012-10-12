<?php
namespace TYPO3\Flow\Core\Migrations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Utility\Files;

/**
 * Move all code to PSR-0 compatible directory structure, remove Package.xml,
 * add composer.json.
 */
class Version201209201112 extends AbstractMigration {

	/**
	 * Returns the identifier of this migration.
	 *
	 * Hardcoded to be stable after the rename to TYPO3 Flow.
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return 'TYPO3.FLOW3-201209201112';
	}

	/**
	 * @return void
	 */
	public function up() {
		$packageKeyAsDirectory = str_replace('.', '/', $this->targetPackageData['packageKey']);
		if (!is_dir(Files::concatenatePaths(array($this->targetPackageData['path'], 'Classes', $packageKeyAsDirectory)))) {
			$this->moveFile('Classes/*', 'Classes/' . $packageKeyAsDirectory . '/');
		} else {
			$this->showNote('Skipping moving of classes to PSR-0 layout since the directory "Classes/' . $packageKeyAsDirectory . '" already exists. Make sure to update any other class to the new layout.');
		}

		$this->writeComposerManifest();

		$this->deleteFile('Meta/Package.xml');

		$packageKeyAsNamespace = str_replace('.', '\\', $this->targetPackageData['packageKey']);
		$this->showNote('You may now remove your "' . $packageKeyAsNamespace . '\Package" class if it does not contain any code.');
	}

	/**
	 * @return void
	 */
	protected function writeComposerManifest() {
		$composerJsonFilename = Files::concatenatePaths(array($this->targetPackageData['path'], 'composer.json'));
		if (file_exists($composerJsonFilename)) {
			return;
		}

		$manifest = array();

		$nameParts = explode('.', $this->targetPackageData['packageKey']);
		$vendor = array_shift($nameParts);
		$manifest['name'] = strtolower($vendor . '/' . implode('-', $nameParts));

		switch ($this->targetPackageData['category']) {
			case 'Application':
				$manifest['type'] = 'typo3-flow-package';
			break;
			default:
				$manifest['type'] = strtolower('typo3-flow-' . $this->targetPackageData['category']);
		}

		$manifest['description'] = $this->targetPackageData['meta']['description'];
		$manifest['version'] = $this->targetPackageData['meta']['version'];
		$manifest['require'] = array('typo3/flow' => '*');
		$manifest['autoload'] = array('psr-0' => array(str_replace('.', '\\', $this->targetPackageData['packageKey']) => 'Classes'));

		if (defined('JSON_PRETTY_PRINT')) {
			file_put_contents($composerJsonFilename, json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
		} else {
			file_put_contents($composerJsonFilename, json_encode($manifest));
		}
	}
}

?>