<?php
declare(encoding = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * File and directory functions
 * 
 * @package		FLOW3
 * @subpackage	Utility
 * @version     $Id:T3_FLOW3_Utility_Files.php 467 2008-02-06 19:34:56Z robert $
 * @copyright   Copyright belongs to the respective authors
 * @license     http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Utility_Files {

	/**
	 * Deletes all files, directories and subdirectories from the specified
	 * directory. The passed directory itself won't be deleted though.
	 *
	 * @param  string		$path: Path to the directory which shall be emptied.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see    removeDirectoryRecursively()
	 */
	public static function emptyDirectoryRecursively($path) {
		if (!is_dir($path)) throw new RuntimeException('"' . $path . '" is no directory.', 1169047616);
		
		$directoryIterator = new RecursiveDirectoryIterator($path);
		foreach (new RecursiveIteratorIterator($directoryIterator) as $filename) {
			if (@unlink ($filename) === FALSE) {
				throw new RuntimeException('Cannot unlink file "' . $filename . '".', 1169047619);
			}
		}		
		foreach ($directoryIterator as $subDirectoryName) {
			if (!@rmdir($subDirectoryName)) {
				self::removeDirectoryRecursively($subDirectoryName);
			}
		}		
	}

	/**
	 * Deletes all files, directories and subdirectories from the specified
	 * directory. Contrary to emptyDirectoryRecursively() this function will
	 * also finally remove the emptied directory.
	 *
	 * @param  string		$path: Path to the directory which shall be removed completely.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see    emptyDirectoryRecursively()
	 */
	public static function removeDirectoryRecursively($path) {
		self::emptyDirectoryRecursively($path);
		rmdir ($path);
	}
	
	/**
	 * Creates a directory specified by $path. If the parent directories
	 * don't exist yet, they will be created as well.
	 *
	 * @param  string		$path: Path to the directory which shall be created
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo   Make mode configurable
	 */
	public static function createDirectoryRecursively($path) {
		$directoryNames = explode('/', $path);
		if (!is_array($directoryNames) || count($directoryNames) == 0) throw new RuntimeException('Invalid path "' . $path . '" specified for creating directory recursively.', 1170251395);
		$currentPath = '';
		foreach ($directoryNames as $directoryName) {
			$currentPath .= $directoryName . '/';
			if (!is_dir($currentPath) && T3_PHP6_Functions::strlen($directoryName) > 0) {
				mkdir($currentPath, 0777);
				if (!is_dir($currentPath)) throw new RuntimeException('Could not create directory "' . $path . '"!', 1170251400);
			}
		}
	}
}
?>