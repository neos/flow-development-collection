<?php
namespace Neos\Flow\Core\Migrations;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Encapsulates some git commands the code migration needs.
 */
class Git
{
    /**
     * Check whether the git command is available.
     *
     * @return boolean
     */
    public static function isGitAvailable()
    {
        $result = 255;
        $output = array();
        exec('git --version', $output, $result);
        return $result === 0;
    }

    /**
     * Check whether the given $path points to the top-level of a git repository
     *
     * @param string $path
     * @return boolean
     */
    public static function isWorkingCopyRoot($path)
    {
        if (!self::isWorkingCopy($path)) {
            return false;
        }
        chdir($path);
        $output = array();
        exec('git rev-parse --show-cdup', $output);
        return implode('', $output) === '';
    }

    /**
     * Check whether the given $path is inside a git repository
     *
     * @param string $path
     * @return boolean
     */
    public static function isWorkingCopy($path)
    {
        chdir($path);
        $output = array();
        exec('git rev-parse --git-dir 2> /dev/null', $output);
        return implode('', $output) !== '';
    }

    /**
     * Check whether the working copy has uncommitted changes.
     *
     * @param string $path
     * @return boolean
     */
    public static function isWorkingCopyDirty($path)
    {
        chdir($path);
        $output = array();
        exec('git status --porcelain', $output);
        return $output !== array();
    }

    /**
     * @param string $source
     * @param string $target
     * @return integer
     */
    public static function move($source, $target)
    {
        $result = 255;
        exec('git mv ' . escapeshellarg($source) . ' ' . escapeshellarg($target), $output, $result);
        return $result;
    }

    /**
     * @param $fileOrDirectory
     * @return integer
     */
    public static function remove($fileOrDirectory)
    {
        $result = 255;
        if (is_dir($fileOrDirectory)) {
            exec('git rm -qr ' . escapeshellarg($fileOrDirectory), $output, $result);
        } else {
            exec('git rm -q ' . escapeshellarg($fileOrDirectory), $output, $result);
        }
        return $result;
    }

    /**
     * Get the result of git show for the current directory.
     *
     * @return string
     */
    public static function show()
    {
        $output = array();
        exec('git show', $output);
        return implode(PHP_EOL, $output);
    }

    /**
     * @param string $path
     * @param string $message
     * @return array in the format [<returnCode>, '<output>']
     */
    public static function commitAll($path, $message)
    {
        chdir($path);
        exec('git add .');
        $output = array();
        $returnCode = null;

        $temporaryPathAndFilename = tempnam(sys_get_temp_dir(), 'flow-commitmsg');
        file_put_contents($temporaryPathAndFilename, $message);
        exec('git commit --allow-empty -F ' . escapeshellarg($temporaryPathAndFilename), $output, $returnCode);
        unlink($temporaryPathAndFilename);

        return array($returnCode, $output);
    }

    /**
     * Checks if the git repository for the given $path has a log entry matching $searchTerm
     *
     * @param string $path
     * @param string $searchTerm
     * @return boolean
     */
    public static function logContains($path, $searchTerm)
    {
        return self::getLog($path, $searchTerm) !== array();
    }

    /**
     * Returns the git log for the specified $path, optionally filtered for $searchTerm
     *
     * @param string $path
     * @param string $searchTerm optional term to filter the log for
     * @return array
     */
    public static function getLog($path, $searchTerm = null)
    {
        $output = array();
        chdir($path);
        if ($searchTerm !== null) {
            exec('git log -F --grep=' . escapeshellarg($searchTerm), $output);
        } else {
            exec('git log', $output);
        }
        return $output;
    }
}
