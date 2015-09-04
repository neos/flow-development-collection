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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Core\Bootstrap;

/**
 * Abstraction methods which return system environment variables.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class Environment {

	/**
	 * @var \TYPO3\Flow\Core\ApplicationContext
	 */
	protected $context;

	/**
	 * @var \TYPO3\Flow\Mvc\ActionRequest
	 */
	protected $request;

	/**
	 * Initializes the environment instance.
	 *
	 * @param \TYPO3\Flow\Core\ApplicationContext $context The Flow context
	 */
	public function __construct(\TYPO3\Flow\Core\ApplicationContext $context) {
		$this->context = $context;
	}

	/**
	 * Sets the base path of the temporary directory
	 *
	 * @param string $temporaryDirectoryBase Base path of the temporary directory, with trailing slash
	 * @return void
	 * @throws Exception
	 * @deprecated since 3.1 - Set the environment variable FLOW_PATH_TEMPORARY_BASE to change the temporary directory base, see Bootstrap::defineConstants()
	 */
	public function setTemporaryDirectoryBase($temporaryDirectoryBase) {
		throw new Exception('Changing the temporary directory path during runtime is no longer supported. Set the environment variable FLOW_PATH_TEMPORARY_BASE to change the temporary directory base', 1441355116);
	}

	/**
	 * Returns the full path to Flow's temporary directory.
	 *
	 * @return string Path to PHP's temporary directory
	 * @api
	 */
	public function getPathToTemporaryDirectory() {
		return FLOW_PATH_TEMPORARY;
	}

	/**
	 * Retrieves the maximum path length that is valid in the current environment.
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
		return (boolean)Bootstrap::getEnvironmentConfigurationSetting('FLOW_REWRITEURLS');

	}

	/**
	 * @return \TYPO3\Flow\Core\ApplicationContext
	 */
	public function getContext() {
		return $this->context;
	}

}
