<?php
namespace TYPO3\Flow\Utility;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Core\Bootstrap;

/**
 * Abstraction methods which return system environment variables.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class Environment
{
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
    public function __construct(\TYPO3\Flow\Core\ApplicationContext $context)
    {
        $this->context = $context;
    }

    /**
     * Sets the base path of the temporary directory
     *
     * @param string $temporaryDirectoryBase Base path of the temporary directory, with trailing slash
     * @return void
     * @throws Exception
     * @deprecated since 2.3 - Set the environment variable FLOW_PATH_TEMPORARY_BASE to change the temporary directory base, see Bootstrap::defineConstants()
     */
    public function setTemporaryDirectoryBase($temporaryDirectoryBase)
    {
        throw new Exception('Changing the temporary directory path during runtime is no longer supported. Set the environment variable FLOW_PATH_TEMPORARY_BASE to change the temporary directory base', 1441355116);
    }

    /**
     * Returns the full path to Flow's temporary directory.
     *
     * @return string
     */
    public function getPathToTemporaryDirectory()
    {
        return FLOW_PATH_TEMPORARY;
    }

    /**
     * Retrieves the maximum path length that is valid in the current environment.
     *
     * @return integer The maximum available path length
     */
    public function getMaximumPathLength()
    {
        return PHP_MAXPATHLEN;
    }

    /**
     * Whether or not URL rewriting is enabled.
     *
     * @return boolean
     */
    public function isRewriteEnabled()
    {
        return (boolean)Bootstrap::getEnvironmentConfigurationSetting('FLOW_REWRITEURLS');
    }

    /**
     * @return \TYPO3\Flow\Core\ApplicationContext
     */
    public function getContext()
    {
        return $this->context;
    }
}
