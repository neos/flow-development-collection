<?php
namespace TYPO3\Flow\Core\Migrations;

/*                                                                        *
 * This script belongs to the Flow package "Flow".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
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
	 * @return array in the format [<returnCode>, '<output>']
	 */
	static public function commitAll($path, $message) {
		chdir($path);
		exec('git add .');
		$output = array();
		$returnCode = NULL;

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
	static public function logContains($path, $searchTerm) {
		$output = array();
		chdir($path);
		exec('git log -F --oneline --grep=' . escapeshellarg($searchTerm), $output);
		return $output !== array();
	}
}
