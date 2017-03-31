<?php
namespace Neos\Utility;

/*
 * This file is part of the Neos.Utility.Files package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Error\Exception as ErrorException;
use Neos\Utility\Exception\FilesException;

/**
 * File and directory functions
 */
abstract class Files
{
    /**
     * Replacing backslashes and double slashes to slashes.
     * It's needed to compare paths (especially on windows).
     *
     * @param string $path Path which should transformed to the Unix Style.
     * @return string
     * @api
     */
    public static function getUnixStylePath($path)
    {
        if (strpos($path, ':') === false) {
            return str_replace(['//', '\\'], '/', $path);
        } else {
            return preg_replace('/^([a-z]{2,}):\//', '$1://', str_replace(['//', '\\'], '/', $path));
        }
    }

    /**
     * Makes sure path has a trailing slash
     *
     * @param string $path
     * @return string
     * @api
     */
    public static function getNormalizedPath($path)
    {
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
    public static function concatenatePaths(array $paths)
    {
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
     * @return array Filenames including full path
     * @api
     */
    public static function readDirectoryRecursively($path, $suffix = null, $returnRealPath = false, $returnDotFiles = false)
    {
        return iterator_to_array(self::getRecursiveDirectoryGenerator($path, $suffix, $returnRealPath, $returnDotFiles));
    }

    /**
     * @param string $path
     * @param string $suffix
     * @param boolean $returnRealPath
     * @param boolean $returnDotFiles
     * @return \Generator
     * @throws FilesException
     */
    public static function getRecursiveDirectoryGenerator($path, $suffix = null, $returnRealPath = false, $returnDotFiles = false)
    {
        if (!is_dir($path)) {
            throw new FilesException('"' . $path . '" is no directory.', 1207253462);
        }

        $directories = array(self::getNormalizedPath($path));
        while ($directories !== array()) {
            $currentDirectory = array_pop($directories);
            if ($handle = opendir($currentDirectory)) {
                while (false !== ($filename = readdir($handle))) {
                    if ($filename === '.' || $filename === '..') {
                        continue;
                    }

                    if ($filename[0] === '.' && $returnDotFiles === false) {
                        continue;
                    }

                    $pathAndFilename = self::concatenatePaths(array($currentDirectory, $filename));
                    if (is_dir($pathAndFilename)) {
                        array_push($directories, self::getNormalizedPath($pathAndFilename));
                    } elseif ($suffix === null || strpos(strrev($filename), strrev($suffix)) === 0) {
                        yield static::getUnixStylePath(($returnRealPath === true) ? realpath($pathAndFilename) : $pathAndFilename);
                    }
                }
                closedir($handle);
            }
        }
    }

    /**
     * Deletes all files, directories and subdirectories from the specified
     * directory. The passed directory itself won't be deleted though.
     *
     * @param string $path Path to the directory which shall be emptied.
     * @return void
     * @throws FilesException
     * @see removeDirectoryRecursively()
     * @api
     */
    public static function emptyDirectoryRecursively($path)
    {
        if (!is_dir($path)) {
            throw new FilesException('"' . $path . '" is no directory.', 1169047616);
        }

        if (self::is_link($path)) {
            if (self::unlink($path) !== true) {
                throw new FilesException('Could not unlink symbolic link "' . $path . '".', 1323697654);
            }
        } else {
            $directoryIterator = new \RecursiveDirectoryIterator($path);
            foreach ($directoryIterator as $fileInfo) {
                if (!$fileInfo->isDir()) {
                    if (self::unlink($fileInfo->getPathname()) !== true) {
                        throw new FilesException('Could not unlink file "' . $fileInfo->getPathname() . '".', 1169047619);
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
     * @throws FilesException
     */
    public static function removeEmptyDirectoriesOnPath($path, $basePath = null)
    {
        if ($basePath !== null) {
            $basePath = trim($basePath, '/');
            if (strpos($path, $basePath) !== 0) {
                throw new FilesException(sprintf('Could not remove empty directories on path because the given base path "%s" is not a parent path of "%s".', $basePath, $path), 1323962907);
            }
        }
        foreach (array_reverse(explode('/', $path)) as $currentSegment) {
            if ($path === $basePath) {
                break;
            }
            if (file_exists($path . '/.DS_Store')) {
                @unlink($path . '/.DS_Store');
            }
            if (@rmdir($path) === false) {
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
     * @throws FilesException
     * @see emptyDirectoryRecursively()
     * @api
     */
    public static function removeDirectoryRecursively($path)
    {
        if (self::is_link($path)) {
            if (self::unlink($path) !== true) {
                throw new FilesException('Could not unlink symbolic link "' . $path . '".', 1316000297);
            }
        } else {
            self::emptyDirectoryRecursively($path);
            try {
                if (rmdir($path) !== true) {
                    throw new FilesException('Could not remove directory "' . $path . '".', 1316000298);
                }
            } catch (\Exception $exception) {
                throw new FilesException('Could not remove directory "' . $path . '".', 1323961907);
            }
        }
    }

    /**
     * Creates a directory specified by $path. If the parent directories
     * don't exist yet, they will be created as well.
     *
     * @param string $path Path to the directory which shall be created
     * @return void
     * @throws FilesException
     * @todo Make mode configurable / make umask configurable
     * @api
     */
    public static function createDirectoryRecursively($path)
    {
        if (substr($path, -2) === '/.') {
            $path = substr($path, 0, -1);
        }
        if (is_file($path)) {
            throw new FilesException('Could not create directory "' . $path . '", because a file with that name exists!', 1349340620);
        }
        if (!is_link($path) && !is_dir($path) && $path !== '') {
            $oldMask = umask(000);
            mkdir($path, 0777, true);
            umask($oldMask);
            if (!is_dir($path)) {
                throw new FilesException('Could not create directory "' . $path . '"!', 1170251400);
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
     * @throws FilesException
     * @api
     */
    public static function copyDirectoryRecursively($sourceDirectory, $targetDirectory, $keepExistingFiles = false, $copyDotFiles = false)
    {
        if (!is_dir($sourceDirectory)) {
            throw new FilesException('"' . $sourceDirectory . '" is no directory.', 1235428779);
        }

        self::createDirectoryRecursively($targetDirectory);
        if (!is_dir($targetDirectory)) {
            throw new FilesException('"' . $targetDirectory . '" is no directory.', 1235428780);
        }

        foreach (self::getRecursiveDirectoryGenerator($sourceDirectory, null, false, $copyDotFiles) as $filename) {
            $relativeFilename = str_replace($sourceDirectory, '', $filename);
            self::createDirectoryRecursively($targetDirectory . dirname($relativeFilename));
            $targetPathAndFilename = self::concatenatePaths([$targetDirectory, $relativeFilename]);
            if ($keepExistingFiles === false || !file_exists($targetPathAndFilename)) {
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
     * @param integer $offset (optional) Offset where reading of the file starts, as of PHP 7.1 supports negative offsets.
     * @param integer $maximumLength (optional) Maximum length to read. Default is -1 (no limit)
     * @return mixed The file content as a string or FALSE if the file could not be opened.
     * @api
     */
    public static function getFileContents($pathAndFilename, $flags = 0, $context = null, $offset = null, $maximumLength = -1)
    {
        if ($flags === true) {
            $flags = FILE_USE_INCLUDE_PATH;
        }
        try {
            if ($maximumLength > -1) {
                $content = file_get_contents($pathAndFilename, $flags, $context, $offset, $maximumLength);
            } else {
                $content = file_get_contents($pathAndFilename, $flags, $context, $offset);
            }
        } catch (ErrorException $ignoredException) {
            $content = false;
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
    public static function getUploadErrorMessage($errorCode)
    {
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
    public static function is_link($pathAndFilename)
    {
        // if not on Windows, call PHPs own is_link() function
        if (DIRECTORY_SEPARATOR === '/') {
            return \is_link($pathAndFilename);
        }
        if (!file_exists($pathAndFilename)) {
            return false;
        }
        $normalizedPathAndFilename = strtolower(rtrim(self::getUnixStylePath($pathAndFilename), '/'));
        $normalizedTargetPathAndFilename = strtolower(self::getUnixStylePath(realpath($pathAndFilename)));
        if ($normalizedTargetPathAndFilename === '') {
            return false;
        }
        return $normalizedPathAndFilename !== $normalizedTargetPathAndFilename;
    }

    /**
     * A version of unlink() that works on Windows regardless on the symlink type (file/directory).
     *
     * If this method could not unlink the specified file or it doesn't exist anymore (e.g. because of a concurrent
     * deletion), it will clear the stat cache for its filename and check if the file still exist. If it does not exist,
     * this method assumes that the file has been deleted by another process and will return TRUE. If the file still
     * exists though, this method will return FALSE.
     *
     * @param string $pathAndFilename Path and name of the file or directory
     * @return boolean TRUE if file/directory was removed successfully
     * @api
     */
    public static function unlink($pathAndFilename)
    {
        try {
            // if not on Windows, call PHPs own unlink() function
            if (DIRECTORY_SEPARATOR === '/' || is_file($pathAndFilename)) {
                if (!@\unlink($pathAndFilename)) {
                    clearstatcache();
                    return !file_exists($pathAndFilename);
                }
                return true;
            }
        } catch (\Exception $exception) {
            clearstatcache();
            return !file_exists($pathAndFilename);
        }

        try {
            return rmdir($pathAndFilename);
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Supported file size units for the byte conversion functions below
     *
     * @var array
     */
    protected static $sizeUnits = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

    /**
     * Converts an integer with a byte count into human-readable form
     *
     * @param float|integer $bytes
     * @param integer $decimals number of decimal places in the resulting string
     * @param string $decimalSeparator decimal separator of the resulting string
     * @param string $thousandsSeparator thousands separator of the resulting string
     * @return string the size string, e.g. "1,024 MB"
     */
    public static function bytesToSizeString($bytes, $decimals = 0, $decimalSeparator = '.', $thousandsSeparator = ',')
    {
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
     * @throws FilesException if the specified unit could not be resolved
     */
    public static function sizeStringToBytes($sizeString)
    {
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
        $pow = array_search($unit, self::$sizeUnits, true);
        if ($pow === false) {
            throw new FilesException(sprintf('Unknown file size unit "%s"', $matches['unit']), 1417695299);
        }
        return $size * pow(2, (10 * $pow));
    }

    /**
     * Will create relative symlinks by given absolute paths, falling back to Windows' mklink command because PHP's symlink() does not support relative paths there.
     * If the file exists already, it will be deleted regardless of its attributes.
     *
     * @param string $target The absolute target where the the symlink should point to relativiely
     * @param string $link The absolute path to the link where the symlink will be created
     * @return boolean
     * @throws FilesException
     */
    public static function createRelativeSymlink($target, $link)
    {
        if (file_exists($link)) {
            self::unlink($link);
        }
        $relativeTargetPath = self::getRelativePath($link, $target);
        if (DIRECTORY_SEPARATOR !== '/') {
            $relativeTargetPath = str_replace('/', '\\', $relativeTargetPath);
            $flag = (is_dir($target) ? '/d' : '');
            $output = array();
            $return = 0;
            // See https://bugs.php.net/bug.php?id=69473 and http://www.howtogeek.com/howto/16226/complete-guide-to-symbolic-links-symlinks-on-windows-or-linux/
            exec(sprintf('mklink %s %s %s', $flag, escapeshellarg($link), escapeshellarg($relativeTargetPath)), $output, $return);
            if ($return !== 0) {
                throw new FilesException(sprintf('Error while attempting to create a relative symlink at "%s" pointing to "%s". Make sure you have sufficient privileges and your operating system supports symlinks.', $link, $relativeTargetPath), 1378986321);
            }
            return file_exists($link);
        } else {
            return \symlink($relativeTargetPath, $link);
        }
    }

    /**
     * Finds the relative path between two given absolute paths.
     * Credits go to stackoverflow member "Gordon".
     *
     * @see http://stackoverflow.com/questions/2637945/
     *
     * @param string $from An absolute path to base on
     * @param string $to An absolute path to find the relative representation onto $from
     * @return string
     */
    public static function getRelativePath($from, $to)
    {
        $from = self::getUnixStylePath($from);
        $to = self::getUnixStylePath($to);
        if (is_dir($from)) {
            $from = self::getNormalizedPath($from);
        }
        if (is_dir($to)) {
            $to = self::getNormalizedPath($to);
        }

        $from = explode('/', $from);
        $to = explode('/', $to);
        $relativePath = $to;

        foreach ($from as $depth => $directory) {
            // find first non-matching dir
            if ($directory === $to[$depth]) {
                // ignore this directory
                array_shift($relativePath);
            } else {
                // get number of remaining dirs to $from
                $remaining = count($from) - $depth;
                if ($remaining > 1) {
                    // add traversals up to first matching dir
                    $padLength = (count($relativePath) + $remaining - 1) * -1;
                    $relativePath = array_pad($relativePath, $padLength, '..');
                    break;
                } else {
                    $relativePath[0] = './' . $relativePath[0];
                }
            }
        }
        return implode('/', $relativePath);
    }
}
