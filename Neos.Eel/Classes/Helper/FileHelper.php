<?php
namespace Neos\Eel\Helper;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Utility\Unicode\Functions;

/**
 * Helper to read files.
 */
class FileHelper implements ProtectedContextAwareInterface
{
    /**
     * Read and return the files contents for further use.
     *
     * @param string $filepath
     * @return string
     */
    public function readFile(string $filepath): string
    {
        return file_get_contents($filepath);
    }

    /**
     * @param string $filepath
     * @return string
     */
    public function getSha1(string $filepath): string
    {
        return sha1_file($filepath);
    }

    /**
     * Get file name and path information
     *
     * @param string $filepath
     * @return array with keys dirname, basename, extension (if any), and filename
     */
    public function fileInfo(string $filepath)
    {
        return Functions::pathinfo($filepath);
    }

    /**
     * Get file information like creation and modification times as well as size.
     *
     * @param string $filepath
     * @return array with keys mode, uid, gid, size, atime, mtime, ctime, (blksize, blocks, dev, ino, nlink, rdev)
     */
    public function stat(string $filepath)
    {
        return stat($filepath);
    }

    /**
     * Check if the given file path exists
     *
     * @param string $filepath
     * @return bool
     */
    public function exists(string $filepath): bool
    {
        return file_exists($filepath);
    }
    
    /**
     * @param string $methodName
     * @return bool
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
