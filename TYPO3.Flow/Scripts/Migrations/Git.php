<?php
namespace TYPO3\Flow\Core\Migrations;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
     * Check whether the working copy is clean.
     *
     * @param string $path
     * @return boolean
     */
    public static function isWorkingCopyClean($path)
    {
        chdir($path);
        $output = array();
        exec('git status --porcelain', $output);
        return $output === array();
    }

    /**
     * @param string $source
     * @param string $target
     * @return integer
     */
    public static function move($source, $target)
    {
        $result = 255;
        system('git mv ' . escapeshellarg($source) . ' ' . escapeshellarg($target), $result);
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
            system('git rm -qr ' . escapeshellarg($fileOrDirectory), $result);
        } else {
            system('git rm -q ' . escapeshellarg($fileOrDirectory), $result);
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
     * @return array
     */
    public static function commitAll($path, $message)
    {
        chdir($path);
        exec('git add .');
        $output = array();
        $returnCode = null;

        $temporaryPathAndFilename = tempnam(sys_get_temp_dir(), 'flow-commitmsg');
        file_put_contents($temporaryPathAndFilename, $message);
        exec('git commit -F ' . escapeshellarg($temporaryPathAndFilename), $output, $returnCode);
        unlink($temporaryPathAndFilename);

        return array($returnCode, $output);
    }

    /**
     * Checks if the current git repository has the given migration applied.
     *
     * @param string $packagePath
     * @param string $migrationIdentifier
     * @return bool
     */
    public static function hasMigrationApplied($packagePath, $migrationIdentifier)
    {
        $output = array();
        chdir($packagePath);
        exec('git log -F --oneline --grep=' . escapeshellarg('Migration: ' . $migrationIdentifier), $output);
        return $output !== array();
    }

    /**
     * Commit changes done to the package described by $packageData. The migration
     * that was did the changes is given with $versionNumber and $versionPackageKey
     * and will be recorded in the commit message.
     *
     * @param string $packagePath
     * @param string $migrationIdentifier
     * @return string
     */
    public static function commitMigration($packagePath, $migrationIdentifier)
    {
        $message = '[TASK] Apply migration ' . $migrationIdentifier . '

This commit contains the result of applying migration
 ' . $migrationIdentifier . '.
to this package.

Migration: ' . $migrationIdentifier;

        list($returnCode, $output) = self::commitAll($packagePath, $message);
        if ($returnCode === 0) {
            return '    ' . implode(PHP_EOL . '    ', $output) . PHP_EOL;
        } else {
            return '    No changes were committed.' . PHP_EOL;
        }
    }
}
