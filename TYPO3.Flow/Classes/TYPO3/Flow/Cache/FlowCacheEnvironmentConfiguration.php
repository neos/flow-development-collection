<?php
namespace TYPO3\Flow\Cache;

/**
 * Flow specific cache configuration object
 */
class FlowCacheEnvironmentConfiguration extends EnvironmentConfiguration
{
    /**
     * The maximum allowed length of a path.
     *
     * @var integer
     */
    protected $maximumPathLength = PHP_MAXPATHLEN;

    /**
     * Base path for any file based caches.
     *
     * @var string
     */
    protected $fileCacheBasePath;

    /**
     * A identifier for this installation,
     * all applications with the same identifier would share entries
     * of caches that are used by multiple installations.
     * The installations path would be a good identifier.
     *
     * @var string
     */
    protected $applicationIdentifier;

    /**
     * The application context is also used to separate cache
     * entries, this allows you to use the same cache for "Testing" purposes
     * without having those entries bleed in your "Production" environment.
     *
     * @var string
     */
    protected $applicationContext;

    /**
     * @var string
     */
    protected $persistentCacheBasePath;

    /**
     * EnvironmentConfiguration constructor.
     *
     * @param string $applicationIdentifier
     * @param string $applicationContext
     * @param string $fileCacheBasePath
     * @param integer $maximumPathLength
     * @param string $persistentCacheBasePath
     */
    public function __construct($applicationIdentifier, $applicationContext, $fileCacheBasePath, $maximumPathLength, $persistentCacheBasePath)
    {
        $this->applicationIdentifier = $applicationIdentifier;
        $this->applicationContext = $applicationContext;
        $this->fileCacheBasePath = $fileCacheBasePath;
        $this->maximumPathLength = $maximumPathLength;
        $this->persistentCacheBasePath = $persistentCacheBasePath;
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

    /**
     * @return string
     */
    public function getApplicationContext()
    {
        return $this->applicationContext;
    }

    /**
     * @return string
     */
    public function getPersistentCacheBasePath()
    {
        return $this->persistentCacheBasePath;
    }
}
