<?php
namespace TYPO3\FLOW3\Utility;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use TYPO3\FLOW3\Mvc\ActionRequest;

/**
 * Abstraction methods which return system environment variables.
 *
 * @FLOW3\Scope("singleton")
 * @api
 */
class Environment {

	/**
	 * @var string
	 */
	protected $context = '';

	/**
	 * @var \TYPO3\FLOW3\Mvc\ActionRequest
	 */
	protected $request;

	/**
	 * The base path of $temporaryDirectory. This property can (and should) be set from outside.
	 * @var string
	 */
	protected $temporaryDirectoryBase;

	/**
	 * @var string
	 */
	protected $temporaryDirectory = NULL;

	/**
	 * Initializes the environment instance.
	 *
	 * @param string $context The FLOW3 context
	 */
	public function __construct($context) {
		$this->context = $context;
	}

	/**
	 * Sets the base path of the temporary directory
	 *
	 * @param string $temporaryDirectoryBase Base path of the temporary directory, with trailing slash
	 * @return void
	 */
	public function setTemporaryDirectoryBase($temporaryDirectoryBase) {
		$this->temporaryDirectoryBase = $temporaryDirectoryBase;
		$this->temporaryDirectory = NULL;
	}

	/**
	 * Returns the full path to FLOW3's temporary directory.
	 *
	 * @return string Path to PHP's temporary directory
	 * @api
	 */
	public function getPathToTemporaryDirectory() {
		if ($this->temporaryDirectory !== NULL) {
			return $this->temporaryDirectory;
		}

		$this->temporaryDirectory = $this->createTemporaryDirectory($this->temporaryDirectoryBase);

		return $this->temporaryDirectory;
	}

	/**
	 * Retrieves the maximum path lenght that is valid in the current environment.
	 *
	 * @return integer The maximum available path length
	 */
	public function getMaximumPathLength() {
		return PHP_MAXPATHLEN;
	}

	/**
	 * Whether or not URL rewriting is enabled.
	 *
	 * @return boolean
	 */
	public function isRewriteEnabled() {
		if (getenv('REDIRECT_FLOW3_REWRITEURLS')) {
			return TRUE;
		}
		return (boolean)getenv('FLOW3_REWRITEURLS');
	}

	/**
	 * Creates FLOW3's temporary directory - or at least asserts that it exists and is
	 * writable.
	 *
	 * @param string $temporaryDirectoryBase Full path to the base for the temporary directory
	 * @return string The full path to the temporary directory
	 * @throws \TYPO3\FLOW3\Utility\Exception if the temporary directory could not be created or is not writable
	 */
	protected function createTemporaryDirectory($temporaryDirectoryBase) {
		$temporaryDirectoryBase = \TYPO3\FLOW3\Utility\Files::getUnixStylePath($temporaryDirectoryBase);
		if (substr($temporaryDirectoryBase, -1, 1) !== '/') {
			$temporaryDirectoryBase .= '/';
		}
		$temporaryDirectory = $temporaryDirectoryBase . $this->context . '/';

		if (!is_dir($temporaryDirectory) && !is_link($temporaryDirectory)) {
			try {
				\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($temporaryDirectory);
			} catch (\TYPO3\FLOW3\Error\Exception $exception) {
				throw new \TYPO3\FLOW3\Utility\Exception('The temporary directory "' . $temporaryDirectory . '" could not be created. Please make sure permissions are correct for this path or define another temporary directory in your Settings.yaml with the path "TYPO3.FLOW3.utility.environment.temporaryDirectoryBase".', 1335382361);
			}
		}

		if (!is_writable($temporaryDirectory)) {
			throw new \TYPO3\FLOW3\Utility\Exception('The temporary directory "' . $temporaryDirectory . '" is not writable. Please make this directory writable or define another temporary directory in your Settings.yaml with the path "TYPO3.FLOW3.utility.environment.temporaryDirectoryBase".', 1216287176);
		}

		return $temporaryDirectory;
	}
}
?>