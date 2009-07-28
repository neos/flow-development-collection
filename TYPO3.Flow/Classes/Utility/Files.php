<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Utility;

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
 * File and directory functions
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Files {

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
	 * @param array $paths the file paths to be combined. Last array element may include the filename.
	 * @return string concatenated path without trailing slash.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @see getUnixStylePath()
	 */
	public static function concatenatePaths(array $paths) {
		$resultingPath = '';
		foreach ($paths as $index => $path) {
			$path = self::getUnixStylePath($path);
			if ($index === 0) {
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
	 * @param string $path Path to the directory which shall be read
	 * @param string $suffix If specified, only filenames with this extension are returned (eg. ".php" or "foo.bar")
	 * @param array $filenames Internally used for the recursion - don't specify!
	 * @return array Filenames including full path
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public static function readDirectoryRecursively($path, $suffix = NULL, &$filenames = array()) {
		if (!is_dir($path)) throw new \F3\FLOW3\Utility\Exception('"' . $path . '" is no directory.', 1207253462);

		$directoryIterator = new \DirectoryIterator($path);
		$suffixLength = strlen($suffix);

		foreach ($directoryIterator as $file) {
			$filename = $file->getFilename();
			if ($file->isFile() && $filename[0] !== '.' && ($suffix === NULL || substr($filename, -$suffixLength) === $suffix)) {
				$filenames[] = self::getUnixStylePath($file->getPathname());
			}
			if ($file->isDir() && $filename[0] !== '.') {
				self::readDirectoryRecursively($file->getPathname(), $suffix, $filenames);
			}
		}
		return $filenames;
	}

	/**
	 * Deletes all files, directories and subdirectories from the specified
	 * directory. The passed directory itself won't be deleted though.
	 *
	 * @param string $path Path to the directory which shall be emptied.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see removeDirectoryRecursively()
	 */
	public static function emptyDirectoryRecursively($path) {
		if (!is_dir($path)) throw new \F3\FLOW3\Utility\Exception('"' . $path . '" is no directory.', 1169047616);

		$directoryIterator = new \RecursiveDirectoryIterator($path);
		foreach ($fileIterator = new \RecursiveIteratorIterator($directoryIterator) as $filename) {
			if (!$fileIterator->isDot() && @unlink($filename) === FALSE) {
				throw new \F3\FLOW3\Utility\Exception('Cannot unlink file "' . $filename . '".', 1169047619);
			}
		}
		foreach ($directoryIterator as $subDirectoryName) {
			if (!$directoryIterator->isDot()) self::removeDirectoryRecursively($subDirectoryName);
		}
	}

	/**
	 * Deletes all files, directories and subdirectories from the specified
	 * directory. Contrary to emptyDirectoryRecursively() this function will
	 * also finally remove the emptied directory.
	 *
	 * @param  string $path Path to the directory which shall be removed completely.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see emptyDirectoryRecursively()
	 */
	public static function removeDirectoryRecursively($path) {
		self::emptyDirectoryRecursively($path);
		rmdir($path);
	}

	/**
	 * Creates a directory specified by $path. If the parent directories
	 * don't exist yet, they will be created as well.
	 *
	 * @param string $path Path to the directory which shall be created
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Make mode configurable / make umask configurable
	 */
	public static function createDirectoryRecursively($path) {
		if (substr($path, -2) === '/.') {
			$path = substr($path, 0, -1);
		}
		if (!is_dir($path) && strlen($path) > 0) {
			$oldMask = umask(000);
			mkdir($path, 0777, TRUE);
			umask($oldMask);
			if (!is_dir($path)) throw new \F3\FLOW3\Utility\Exception('Could not create directory "' . $path . '"!', 1170251400);
		}
	}

	/**
	 * Copies the contents of the source directory to the target directory.
	 * $targetDirectory will be created if it does not exist.
	 *
	 * @param string $sourceDirectory
	 * @param string $targetDirectory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public static function copyDirectoryRecursively($sourceDirectory, $targetDirectory) {
		if (!is_dir($sourceDirectory)) throw new \F3\FLOW3\Utility\Exception('"' . $sourceDirectory . '" is no directory.', 1235428779);

		self::createDirectoryRecursively($targetDirectory);
		if (!is_dir($targetDirectory)) throw new \F3\FLOW3\Utility\Exception('"' . $targetDirectory . '" is no directory.', 1235428779);

		$resourceFilenames = self::readDirectoryRecursively($sourceDirectory);
		foreach ($resourceFilenames as $filename) {
			$relativeFilename = str_replace($sourceDirectory, '', $filename);
			self::createDirectoryRecursively($targetDirectory . dirname($relativeFilename));
			copy($filename, self::concatenatePaths(array($targetDirectory, $relativeFilename)));
		}
	}

	/**
	 * An enhanced version of file_get_contents which intercepts the warning
	 * issued by the original function if a file could not be loaded.
	 *
	 * @param string $pathAndFilename Path and name of the file to load
	 * @param integer $flags (optional) ORed flags using PHP's FILE_* constants (see manual of file_get_contents).
	 * @param resource $context (optional) A context resource created by stream_context_create()
	 * @param integer $offset (optional) Offset where reading of the file starts.
	 * @param integer $maximumLength (optional) Maximum length to read. Default is -1 (no limit)
	 * @return mixed The file content as a string or FALSE if the file could not be opened.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public static function getFileContents($pathAndFilename, $flags = 0, $context = NULL, $offset = -1, $maximumLength = -1) {
		if ($flags === TRUE) $flags = FILE_USE_INCLUDE_PATH;
		try {
			if ($maximumLength > -1) {
				$content = file_get_contents($pathAndFilename, $flags, $context, $offset, $maximumLength);
			} else {
				$content = file_get_contents($pathAndFilename, $flags, $context, $offset);
			}
		} catch (\F3\FLOW3\Error\Exception $ignoredException) {
			$content = FALSE;
		}
		return $content;
	}
}
?>
