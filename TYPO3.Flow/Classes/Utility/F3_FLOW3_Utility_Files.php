<?php
declare(ENCODING = 'utf-8');

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
 * @package FLOW3
 * @subpackage Utility
 * @version $Id:F3_FLOW3_Utility_Files.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * File and directory functions
 *
 * @package FLOW3
 * @subpackage Utility
 * @version $Id:F3_FLOW3_Utility_Files.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Utility_Files {

	/**
	 * Replacing backslashes and double slashes to slashes.
	 * It's needed to compare paths (especially on windows).
	 *
	 * @param string $path Path which should transformed to the Unix Style.
	 * @return string
	 * @author Malte Jansen <typo3@maltejansen.de>
	 */
	public static function getUnixStylePath($path) {
		return str_replace('//', '/', str_replace('\\', '/', $path));
	}

	/**
	 * Properly glues together filepaths / filenames by replacing
	 * backslashes and double slashes of the specified paths.
	 * Note: trailing slashes will be removed, leading slashes won't.
	 * Usage: concatenatePaths(array('dir1/dir2', 'dir3', 'file'))
	 *
	 * @param array $paths the file paths to be combined. Last array element may
	 * include the filename.
	 * @return string concatenated path without trailing slash.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @see getUnixStylePath()
	 */
	public static function concatenatePaths(array $paths) {
		$resultingPath = '';
		foreach($paths as $index => $path) {
			$path = self::getUnixStylePath($path);
			if ($index == 0) {
				$path = rtrim($path, '/');
			} else {
				$path = trim($path, '/');
			}
			if (strlen($path) > 0) {
				$resultingPath.= $path . '/';
			}
		}
		return rtrim($resultingPath, '/');
	} 

	/**
	 * Returns all filenames from the specified directory. Filters hidden files and
	 * directories.
	 *
	 * @param string $path Path to the directory which shall be read.
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public static function readDirectoryRecursively($path, &$files = array()) {
		if (!is_dir($path)) throw new F3_FLOW3_Utility_Exception('"' . $path . '" is no directory.', 1207253462);

		$directoryIterator = new DirectoryIterator($path);
		foreach ($directoryIterator as $file) {
			if ($file->isFile() && F3_PHP6_Functions::substr($file->getFilename(),0,1) != '.') {
				$files[] = F3_FLOW3_Utility_Files::getUnixStylePath($file->getPathname());
			}
			if ($file->isDir() && F3_PHP6_Functions::substr($file->getFilename(),0,1) != '.') {
				self::readDirectoryRecursively($file->getPathname(), $files);
			}
		}
		return $files;
	}

	/**
	 * Deletes all files, directories and subdirectories from the specified
	 * directory. The passed directory itself won't be deleted though.
	 *
	 * @param string $path: Path to the directory which shall be emptied.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see removeDirectoryRecursively()
	 */
	public static function emptyDirectoryRecursively($path) {
		if (!is_dir($path)) throw new F3_FLOW3_Utility_Exception('"' . $path . '" is no directory.', 1169047616);

		$directoryIterator = new RecursiveDirectoryIterator($path);
		foreach (new RecursiveIteratorIterator($directoryIterator) as $filename) {
			if (@unlink($filename) === FALSE) {
				throw new F3_FLOW3_Utility_Exception('Cannot unlink file "' . $filename . '".', 1169047619);
			}
		}
		foreach ($directoryIterator as $subDirectoryName) {
			self::removeDirectoryRecursively($subDirectoryName);
		}
	}

	/**
	 * Deletes all files, directories and subdirectories from the specified
	 * directory. Contrary to emptyDirectoryRecursively() this function will
	 * also finally remove the emptied directory.
	 *
	 * @param  string $path: Path to the directory which shall be removed completely.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see emptyDirectoryRecursively()
	 */
	public static function removeDirectoryRecursively($path) {
		self::emptyDirectoryRecursively($path);
		rmdir ($path);
	}

	/**
	 * Creates a directory specified by $path. If the parent directories
	 * don't exist yet, they will be created as well.
	 *
	 * @param string $path: Path to the directory which shall be created
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Make mode configurable / make umask configurable
	 */
	public static function createDirectoryRecursively($path) {
		if (substr($path, -2) === '/.') {
			$path = substr($path, 0, -1);
		}
		if (!is_dir($path) && F3_PHP6_Functions::strlen($path) > 0) {
			$oldMask = umask(000);
			mkdir($path, 0777, TRUE);
			umask($oldMask);
			if (!is_dir($path)) throw new F3_FLOW3_Utility_Exception('Could not create directory "' . $path . '"!', 1170251400);
		}
	}
}
?>