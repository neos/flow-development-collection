<?php
namespace Neos\Flow\Utility;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Error\Exception as ErrorException;
use Neos\Flow\Utility\Exception as UtilityException;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Utility\Files;

/**
 * Abstraction methods which return system environment variables.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class Environment
{
    /**
     * @var ApplicationContext
     */
    protected $context;

    /**
     * @var ActionRequest
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
    protected $temporaryDirectory = null;

    /**
     * Initializes the environment instance.
     *
     * @param ApplicationContext $context The Flow context
     */
    public function __construct(ApplicationContext $context)
    {
        $this->context = $context;
    }

    /**
     * Sets the base path of the temporary directory
     *
     * @param string $temporaryDirectoryBase Base path of the temporary directory, with trailing slash
     * @return void
     */
    public function setTemporaryDirectoryBase($temporaryDirectoryBase)
    {
        $this->temporaryDirectoryBase = $temporaryDirectoryBase;
        $this->temporaryDirectory = null;
    }

    /**
     * Returns the full path to Flow's temporary directory.
     *
     * @return string Path to PHP's temporary directory
     * @api
     */
    public function getPathToTemporaryDirectory()
    {
        if ($this->temporaryDirectory !== null) {
            return $this->temporaryDirectory;
        }

        $this->temporaryDirectory = $this->createTemporaryDirectory($this->temporaryDirectoryBase);

        return $this->temporaryDirectory;
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
     * Creates Flow's temporary directory - or at least asserts that it exists and is
     * writable.
     *
     * For each Flow Application Context, we create an extra temporary folder,
     * and for nested contexts, the folders are prefixed with "SubContext" to
     * avoid ambiguity, and look like: Data/Temporary/Production/SubContextLive
     *
     * @param string $temporaryDirectoryBase Full path to the base for the temporary directory
     * @return string The full path to the temporary directory
     * @throws UtilityException if the temporary directory could not be created or is not writable
     */
    protected function createTemporaryDirectory($temporaryDirectoryBase)
    {
        $temporaryDirectoryBase = Files::getUnixStylePath($temporaryDirectoryBase);
        if (substr($temporaryDirectoryBase, -1, 1) !== '/') {
            $temporaryDirectoryBase .= '/';
        }
        $temporaryDirectory = $temporaryDirectoryBase . str_replace('/', '/SubContext', (string)$this->context) . '/';

        if (!is_dir($temporaryDirectory) && !is_link($temporaryDirectory)) {
            try {
                Files::createDirectoryRecursively($temporaryDirectory);
            } catch (ErrorException $exception) {
                throw new UtilityException('The temporary directory "' . $temporaryDirectory . '" could not be created. Please make sure permissions are correct for this path or define another temporary directory in your Settings.yaml with the path "Neos.Flow.utility.environment.temporaryDirectoryBase".', 1335382361);
            }
        }

        if (!is_writable($temporaryDirectory)) {
            throw new UtilityException('The temporary directory "' . $temporaryDirectory . '" is not writable. Please make this directory writable or define another temporary directory in your Settings.yaml with the path "Neos.Flow.utility.environment.temporaryDirectoryBase".', 1216287176);
        }

        return $temporaryDirectory;
    }

    /**
     * @return ApplicationContext
     */
    public function getContext()
    {
        return $this->context;
    }
}
