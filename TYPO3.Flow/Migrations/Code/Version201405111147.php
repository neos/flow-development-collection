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
 *
 */
class Version201405111147 extends AbstractMigration {

	/**
	 * @return void
	 */
	public function up() {
		$affectedFiles = array();
		$allPathsAndFilenames = Files::readDirectoryRecursively($this->targetPackageData['path'], NULL, TRUE);
		foreach ($allPathsAndFilenames as $pathAndFilename) {
			if (substr($pathAndFilename, -13) !== 'Converter.php') {
				continue;
			}
			$fileContents = file_get_contents($pathAndFilename);
			if (preg_match('/public\s+function\s+canConvertFrom\s*\(/', $fileContents) === 1) {
				$affectedFiles[] = substr($pathAndFilename, strlen($this->targetPackageData['path']) + 1);
			}
		}

		if ($affectedFiles !== array()) {
			$this->showWarning('Following TypeConverters implement the canConvertFrom() method. The element type of the $targetType argument is no longer cut off, so it might be "array<Some/Element/Type>" instead of just "array" for example. Make sure that this is not an issue or add' . PHP_EOL . '  $targetType = TypeHandling::truncateElementType($targetType);' . PHP_EOL . 'to the beginning of this method body if you\'re not sure:' . PHP_EOL . PHP_EOL . '* ' . implode(PHP_EOL . '* ', $affectedFiles));
		}
	}

}
