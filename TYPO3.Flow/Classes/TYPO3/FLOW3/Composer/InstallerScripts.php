<?php
namespace TYPO3\FLOW3\Composer;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Composer\Script\Event;
use TYPO3\FLOW3\Utility\Files;

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
	static public function postUpdateAndInstall(Event $event) {
		Files::createDirectoryRecursively('Configuration');
		Files::createDirectoryRecursively('Data');

		Files::copyDirectoryRecursively('Packages/Framework/TYPO3.FLOW3/Resources/Private/Installer/Distribution/Essentials', '.');
		Files::copyDirectoryRecursively('Packages/Framework/TYPO3.FLOW3/Resources/Private/Installer/Distribution/Defaults', '.', TRUE);

		chmod('flow3', 0755);
	}
}
?>