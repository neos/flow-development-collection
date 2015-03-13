<?php
namespace TYPO3\Flow\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * File and directory functions
 */
class Files {

	/**
	 * Replacing backslashes and double slashes to slashes.
	 * It's needed to compare paths (especially on windows).
	 *
	 * @param string $path Path which should transformed to the Unix Style.
	 * @return string
	 * @api
	 */
	static public function getUnixStylePath($path) {
		if (strpos($path, ':') === FALSE) {
			return str_replace(array('//', '\\'), '/', $path);
		} else {
			return preg_replace('/^([a-z]{2,}):\//', '$1://', str_replace(array('//', '\\'), '/', $path));
		}
	}

	/**
	 * Makes sure path has a trailing slash
	 *
	 * @param string $path
	 * @return string
	 * @api
	 */
	static public function getNormalizedPath($path) {
		return rtrim($path, '/') . '/';
	}

	/**
	 * Properly glues together filepaths / filenames by replacing
	 * backslashes and double slashes of the specified paths.
	 * Note: trailing slashes will be removed, leading slashes won't.
	 * Usage: concatenatePaths(array('dir1/dir2', 'dir3', 'file'))
	 *
	 * @param array $paths the file paths to be combined. Last array element may include the filename.
	 * @return string concatenated path without trailing slash.
	 * @see getUnixStylePath()
	 * @api
	 */
	static public function concatenatePaths(array $paths) {
		$resultingPath = '';
		foreach ($paths as $index => $path) {
			$path = self::getUnixStylePath($path);
			if ($index === 0) {
				$path = rtrim($path, '/');
			} else {
				$path = trim($path, '/');
			}
			if ($path !== '') {
				$resultingPath .= $path . '/';
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
	 * @param boolean $returnRealPath If turned on, all paths are resolved by calling realpath()
	 * @param boolean $returnDotFiles If turned on, also files beginning with a dot will be returned
	 * @param array $filenames Internally used for the recursion - don't specify!
	 * @return array Filenames including full path
	 * @throws Exception
	 * @api
	 */
	static public function readDirectoryRecursively($path, $suffix = NULL, $returnRealPath = FALSE, $returnDotFiles = FALSE, &$filenames = array()) {
		if (!is_dir($path)) {
			throw new Exception('"' . $path . '" is no directory.', 1207253462);
		}

		$directoryIterator = new \DirectoryIterator($path);
		$suffixLength = strlen($suffix);

		foreach ($directoryIterator as $fileInfo) {
			$filename = $fileInfo->getFilename();
			if ($filename === '.' || $filename === '..' || ($returnDotFiles === FALSE && $filename[0] === '.')) {
				continue;
			}
			if ($fileInfo->isFile() && ($suffix === NULL || substr($filename, -$suffixLength) === $suffix)) {
				$filenames[] = self::getUnixStylePath(($returnRealPath === TRUE ? realpath($fileInfo->getPathname()) : $fileInfo->getPathname()));
			}
			if ($fileInfo->isDir()) {
				self::readDirectoryRecursively($fileInfo->getPathname(), $suffix, $returnRealPath, $returnDotFiles, $filenames);
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
	 * @throws Exception
	 * @see removeDirectoryRecursively()
	 * @api
	 */
	static public function emptyDirectoryRecursively($path) {
		if (!is_dir($path)) {
			throw new Exception('"' . $path . '" is no directory.', 1169047616);
		}

		if (self::is_link($path)) {
			if (self::unlink($path) !== TRUE) {
				throw new Exception('Could not unlink symbolic link "' . $path . '".', 1323697654);
			}
		} else {
			$directoryIterator = new \RecursiveDirectoryIterator($path);
			foreach ($directoryIterator as $fileInfo) {
				if (!$fileInfo->isDir()) {
					if (self::unlink($fileInfo->getPathname()) !== TRUE) {
						throw new Exception('Could not unlink file "' . $fileInfo->getPathname() . '".', 1169047619);
					}
				} elseif (!$directoryIterator->isDot()) {
					self::removeDirectoryRecursively($fileInfo->getPathname());
				}
			}
		}
	}

	/**
	 * Removes all empty directories on the specified path. If a base path is given, this function will not remove
	 * directories, even if empty, above and including that base path.
	 *
	 * Any .DS_Store files are silently removed.
	 *
	 * @param string $path The path on which empty directories shall be removed
	 * @param string $basePath A parent path of $path where removal of directories stops
	 * @return void
	 * @see removeDirectoryRecursively()
	 * @api
	 * @throws Exception
	 */
	static public function removeEmptyDirectoriesOnPath($path, $basePath = NULL) {
		if ($basePath !== NULL) {
			$basePath = trim($basePath, '/');
			if (strpos($path, $basePath) !== 0) {
				throw new Exception(sprintf('Could not remove empty directories on path because the given base path "%s" is not a parent path of "%s".', $basePath, $path), 1323962907);
			}
		}
		foreach (array_reverse(explode('/', $path)) as $currentSegment) {
			if ($path === $basePath) {
				break;
			}
			if (file_exists($path . '/.DS_Store')) {
				@unlink($path . '/.DS_Store');
			}
			if (@rmdir($path) === FALSE) {
				break;
			}
			$path = substr($path, 0, -(strlen($currentSegment) + 1));
		}
	}

	/**
	 * Deletes all files, directories and subdirectories from the specified
	 * directory. Contrary to emptyDirectoryRecursively() this function will
	 * also finally remove the emptied directory.
	 *
	 * @param  string $path Path to the directory which shall be removed completely.
	 * @return void
	 * @throws Exception
	 * @see emptyDirectoryRecursively()
	 * @api
	 */
	static public function removeDirectoryRecursively($path) {
		if (self::is_link($path)) {
			if (self::unlink($path) !== TRUE) {
				throw new Exception('Could not unlink symbolic link "' . $path . '".', 1316000297);
			}
		} else {
			self::emptyDirectoryRecursively($path);
			try {
				if (rmdir($path) !== TRUE) {
					throw new Exception('Could not remove directory "' . $path . '".', 1316000298);
				}
			} catch (\Exception $exception) {
				throw new Exception('Could not remove directory "' . $path . '".', 1323961907);
			}
		}
	}

	/**
	 * Creates a directory specified by $path. If the parent directories
	 * don't exist yet, they will be created as well.
	 *
	 * @param string $path Path to the directory which shall be created
	 * @return void
	 * @throws Exception
	 * @todo Make mode configurable / make umask configurable
	 * @api
	 */
	static public function createDirectoryRecursively($path) {
		if (substr($path, -2) === '/.') {
			$path = substr($path, 0, -1);
		}
		if (is_file($path)) {
			throw new Exception('Could not create directory "' . $path . '", because a file with that name exists!', 1349340620);
		}
		if (!is_dir($path) && $path !== '') {
			$oldMask = umask(000);
			mkdir($path, 0777, TRUE);
			umask($oldMask);
			if (!is_dir($path)) {
				throw new Exception('Could not create directory "' . $path . '"!', 1170251400);
			}
		}
	}

	/**
	 * Copies the contents of the source directory to the target directory.
	 * $targetDirectory will be created if it does not exist.
	 *
	 * If $keepExistingFiles is TRUE, this will keep files already present
	 * in the target location. It defaults to FALSE.
	 *
	 * If $copyDotFiles is TRUE, this will copy files whose name begin with
	 * a dot. It defaults to FALSE.
	 *
	 * @param string $sourceDirectory
	 * @param string $targetDirectory
	 * @param boolean $keepExistingFiles
	 * @param boolean $copyDotFiles
	 * @return void
	 * @throws Exception
	 * @api
	 */
	static public function copyDirectoryRecursively($sourceDirectory, $targetDirectory, $keepExistingFiles = FALSE, $copyDotFiles = FALSE) {
		if (!is_dir($sourceDirectory)) {
			throw new Exception('"' . $sourceDirectory . '" is no directory.', 1235428779);
		}

		self::createDirectoryRecursively($targetDirectory);
		if (!is_dir($targetDirectory)) {
			throw new Exception('"' . $targetDirectory . '" is no directory.', 1235428780);
		}

		$sourceFilenames = self::readDirectoryRecursively($sourceDirectory, NULL, FALSE, $copyDotFiles);
		foreach ($sourceFilenames as $filename) {
			$relativeFilename = str_replace($sourceDirectory, '', $filename);
			self::createDirectoryRecursively($targetDirectory . dirname($relativeFilename));
			$targetPathAndFilename = self::concatenatePaths(array($targetDirectory, $relativeFilename));
			if ($keepExistingFiles === FALSE || !file_exists($targetPathAndFilename)) {
				copy($filename, $targetPathAndFilename);
			}
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
	 * @api
	 */
	static public function getFileContents($pathAndFilename, $flags = 0, $context = NULL, $offset = -1, $maximumLength = -1) {
		if ($flags === TRUE) {
			$flags = FILE_USE_INCLUDE_PATH;
		}
		try {
			if ($maximumLength > -1) {
				$content = file_get_contents($pathAndFilename, $flags, $context, $offset, $maximumLength);
			} else {
				$content = file_get_contents($pathAndFilename, $flags, $context, $offset);
			}
		} catch (\TYPO3\Flow\Error\Exception $ignoredException) {
			$content = FALSE;
		}
		return $content;
	}

	/**
	 * Returns a human-readable message for the given PHP file upload error
	 * constant.
	 *
	 * @param integer $errorCode One of the UPLOAD_ERR_ constants
	 * @return string
	 */
	static public function getUploadErrorMessage($errorCode) {
		switch ($errorCode) {
			case \UPLOAD_ERR_INI_SIZE:
				return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
			case \UPLOAD_ERR_FORM_SIZE:
				return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
			case \UPLOAD_ERR_PARTIAL:
				return 'The uploaded file was only partially uploaded';
			case \UPLOAD_ERR_NO_FILE:
				return 'No file was uploaded';
			case \UPLOAD_ERR_NO_TMP_DIR:
				return 'Missing a temporary folder';
			case \UPLOAD_ERR_CANT_WRITE:
				return 'Failed to write file to disk';
			case \UPLOAD_ERR_EXTENSION:
				return 'File upload stopped by extension';
			default:
				return 'Unknown upload error';
		}
	}

	/**
	 * A version of is_link() that works on Windows too
	 * @see http://www.php.net/is_link
	 *
	 * If http://bugs.php.net/bug.php?id=51766 gets fixed we can drop this.
	 *
	 * @param string $pathAndFilename Path and name of the file or directory
	 * @return boolean TRUE if the path exists and is a symbolic link, FALSE otherwise
	 * @api
	 */
	static public function is_link($pathAndFilename) {
		// if not on Windows, call PHPs own is_link() function
		if (DIRECTORY_SEPARATOR === '/') {
			return \is_link($pathAndFilename);
		}
		if (!file_exists($pathAndFilename)) {
			return FALSE;
		}
		$normalizedPathAndFilename = strtolower(rtrim(self::getUnixStylePath($pathAndFilename), '/'));
		$normalizedTargetPathAndFilename = strtolower(self::getUnixStylePath(realpath($pathAndFilename)));
		if ($normalizedTargetPathAndFilename === '') {
			return FALSE;
		}
		return $normalizedPathAndFilename !== $normalizedTargetPathAndFilename;
	}

	/**
	 * A version of unlink() that works on Windows regardless on the symlink type (file/directory)
	 *
	 * @param string $pathAndFilename Path and name of the file or directory
	 * @return boolean TRUE if file/directory was removed successfully
	 * @api
	 */
	static public function unlink($pathAndFilename) {
		try {
			// if not on Windows, call PHPs own unlink() function
			if (DIRECTORY_SEPARATOR === '/' || is_file($pathAndFilename)) {
				return @\unlink($pathAndFilename);
			}
			return rmdir($pathAndFilename);
		} catch (\Exception $exception) {
			return FALSE;
		}
	}

	/**
	 * Supported filesize units for the byte conversion functions below
	 *
	 * @var array
	 */
	static protected $sizeUnits = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

	/**
	 * Converts an integer with a byte count into human-readable form
	 *
	 * @param float|integer $bytes
	 * @param integer $decimals number of decimal places in the resulting string
	 * @param string $decimalSeparator decimal separator of the resulting string
	 * @param string $thousandsSeparator thousands separator of the resulting string
	 * @return string the size string, e.g. "1,024 MB"
	 */
	static public function bytesToSizeString($bytes, $decimals = 0, $decimalSeparator = '.', $thousandsSeparator = ',') {
		if (!is_integer($bytes) && !is_float($bytes)) {
			if (is_numeric($bytes)) {
				$bytes = (float)$bytes;
			} else {
				$bytes = 0;
			}
		}

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count(self::$sizeUnits) - 1);
		$bytes /= pow(2, (10 * $pow));

		return sprintf(
			'%s %s',
			number_format(round($bytes, 4 * $decimals), $decimals, $decimalSeparator, $thousandsSeparator),
			self::$sizeUnits[$pow]
		);
	}

	/**
	 * Converts a size string (e.g. "1024.0 MB") to the number of bytes it represents
	 *
	 * @param string $sizeString the human-readable size string (e.g. ini_get('upload_max_filesize'))
	 * @return float The number of bytes the $sizeString represents or 0 if the number could not be parsed
	 * @throws Exception if the specified unit could not be resolved
	 */
	static public function sizeStringToBytes($sizeString) {
		preg_match('/(?P<size>\d+\.*\d*)(?P<unit>.*)/', $sizeString, $matches);
		if (empty($matches['size'])) {
			return 0.0;
		}
		$size = (float)$matches['size'];
		if (empty($matches['unit'])) {
			return (float)round($size);
		}
		$unit = strtoupper(trim($matches['unit']));
		if ($unit !== 'B' && strlen($unit) === 1) {
			$unit .= 'B';
		}
		$pow = array_search($unit, self::$sizeUnits, TRUE);
		if ($pow === FALSE) {
			throw new Exception(sprintf('Unknown file size unit "%s"', $matches['unit']), 1417695299);
		}
		return $size * pow(2, (10 * $pow));
	}
}
