<?php
namespace TYPO3\FLOW3\Core\Migrations;

/*                                                                        *
 * This script belongs to the FLOW3 package "FLOW3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Utility\Files;

/**
 * A set of helper methods for the code migration tool.
 */
class Tools {

	/**
	 * Will return an array with all available packages.
	 *
	 * The data for each entry will be an array with the key, full path to
	 * the package (index 'path') and a category (the packages subfolder,
	 * index 'category'). The array is indexed by package key.
	 *
	 * @param string $packagesPath
	 * @return array
	 */
	static public function getPackagesData($packagesPath) {
		$packagesData = array();
		$packagesDirectoryIterator = new \DirectoryIterator($packagesPath);
		foreach ($packagesDirectoryIterator as $categoryFileInfo) {
			$category = $categoryFileInfo->getFilename();
			if (!$categoryFileInfo->isDir() || $category[0] === '.') {
				continue;
			}

			$categoryDirectoryIterator = new \DirectoryIterator($categoryFileInfo->getPathname());
			foreach ($categoryDirectoryIterator as $packageFileInfo) {
				$packageKey = $packageFileInfo->getFilename();
				if (!$packageFileInfo->isDir() || $packageKey[0] === '.') {
					continue;
				}

				if (!file_exists(Files::concatenatePaths(array($packageFileInfo->getPathname(), 'Classes/Package.php')))) {
					continue;
				};

				$packagesData[$packageKey] = array(
					'packageKey' => $packageKey,
					'category' => $category,
					'path' => $packageFileInfo->getPathname(),
				);
			}
		}
		return $packagesData;
	}

	/**
	 * Does a simple str_replace on the given file.
	 *
	 * @param string $search
	 * @param string $replace
	 * @param string $pathAndFilename
	 * @return boolean|NULL FALSE on errors, NULL on skip, TRUE on success
	 */
	static public function searchAndReplace($search, $replace, $pathAndFilename) {
		$pathInfo = pathinfo($pathAndFilename);
		if (!isset($pathInfo['filename']) || $pathAndFilename === __FILE__) return FALSE;
		$file = file_get_contents($pathAndFilename);
		$fileBackup = $file;
		$file = str_replace($search, $replace, $file);
		if ($file !== $fileBackup) {
			file_put_contents($pathAndFilename, $file);
		}
		return TRUE;
	}

	/**
	 * Does a simple preg_replace on the given file. The given patterns
	 * are used as given, no quoting is applied!
	 *
	 * @param string $search
	 * @param string $replace
	 * @param string $pathAndFilename
	 * @return boolean|NULL FALSE on errors, NULL on skip, TRUE on success
	 */
	static public function searchAndReplaceRegex($search, $replace, $pathAndFilename) {
		$pathInfo = pathinfo($pathAndFilename);
		if (!isset($pathInfo['filename']) || $pathAndFilename === __FILE__) return FALSE;
		$file = file_get_contents($pathAndFilename);
		$fileBackup = $file;
		$file = preg_replace($search, $replace, $file);
		if ($file !== $fileBackup) {
			file_put_contents($pathAndFilename, $file);
		}
		return TRUE;
	}
}

?>