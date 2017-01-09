<?php
namespace Neos\Cache;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Global Configuration about the environment to be used by caches.
 */
class EnvironmentConfiguration
{
    /**
     * A identifier for this installation,
     * all applications with the same identifier would share entries
     * of caches that are used by multiple installations.
     * The installations path would be a good identifier.
     * If your application supports different contexts that shouldn't
     * share caches an identifier for that should also be included.
     *
     * @var string
     */
    protected $applicationIdentifier;

    /**
     * Base path for any file based caches.
     *
     * @var string
     */
    protected $fileCacheBasePath;

    /**
     * The maximum allowed length of a path.
     *
     * @var integer
     */
    protected $maximumPathLength;

    /**
     * EnvironmentConfiguration constructor.
     *
     * @param string $applicationIdentifier
     * @param string $fileCacheBasePath
     * @param integer $maximumPathLength
     */
    public function __construct($applicationIdentifier, $fileCacheBasePath, $maximumPathLength = PHP_MAXPATHLEN)
    {
        $this->applicationIdentifier = $applicationIdentifier;
        $this->fileCacheBasePath = $fileCacheBasePath;
        $this->maximumPathLength = $maximumPathLength;
    }

    /**
     * @return int
     */
    public function getMaximumPathLength()
    {
        return $this->maximumPathLength;
    }

    /**
     * @return string
     */
    public function getFileCacheBasePath()
    {
        return $this->fileCacheBasePath;
    }

    /**
     * @return string
     */
    public function getApplicationIdentifier()
    {
        return $this->applicationIdentifier;
    }
}
