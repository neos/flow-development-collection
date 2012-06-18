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

/**
 * Encapsulates some git commands the code migration needs.
 */
class Git {

	/**
	 * Check whether the git command is available.
	 *
	 * @return boolean
	 */
	static public function isGitAvailable() {
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
	static public function isWorkingCopyClean($path) {
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
	static public function move($source, $target) {
		$result = 255;
		system('git mv ' . escapeshellarg($source) . ' ' . escapeshellarg($target), $result);
		return $result;
	}

	/**
	 * @param $fileOrDirectory
	 * @return integer
	 */
	static public function remove($fileOrDirectory) {
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
	static public function show() {
		$output = array();
		exec('git show', $output);
		return implode(PHP_EOL, $output);
	}

	/**
	 * @param string $path
	 * @param string $message
	 * @return array
	 */
	static public function commitAll($path, $message) {
		chdir($path);
		exec('git add .');
		$output = array();
		$returnCode = NULL;
		exec('git commit -m ' . escapeshellarg($message), $output, $returnCode);
		return array($returnCode, $output);
	}

	/**
	 * Checks if the current git repository has the given migration applied.
	 *
	 * @param string $packagePath
	 * @param string $migrationIdentifier
	 * @return bool
	 */
	static public function hasMigrationApplied($packagePath, $migrationIdentifier) {
		$output = array();
		chdir($packagePath);
		exec('git log -F --oneline --grep ' . escapeshellarg('Migration: ' . $migrationIdentifier), $output);
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
	static public function commitMigration($packagePath, $migrationIdentifier) {
		$message = '[TASK] Apply migration ' . $migrationIdentifier . '

This commit contains the result of applying migration
 ' . $migrationIdentifier . '.
to this package.

Migration: ' . $migrationIdentifier;

		list ($returnCode, $output) = self::commitAll($packagePath, $message);
		if ($returnCode === 0) {
			return '    ' . implode(PHP_EOL . '    ', $output) . PHP_EOL;
		} else {
			return '    No changes were committed.' . PHP_EOL;
		}
	}
}

?>