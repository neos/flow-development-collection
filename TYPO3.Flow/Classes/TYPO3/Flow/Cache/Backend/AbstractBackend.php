<?php
namespace TYPO3\Flow\Cache\Backend;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Cache\EnvironmentConfiguration;

/**
 * An abstract caching backend
 *
 * @api
 * @deprecated This is replaced by \Neos\Cache\Backend\AbstractBackend which has a different constructor, you need to adapt any custom cache backend to that.
 * @see \Neos\Cache\Backend\AbstractBackend
 */
abstract class AbstractBackend extends \Neos\Cache\Backend\AbstractBackend implements BackendInterface
{
    const DATETIME_EXPIRYTIME_UNLIMITED = '9999-12-31T23:59:59+0000';
    const UNLIMITED_LIFETIME = 0;

    /**
     * Reference to the cache frontend which uses this backend
     * @var \TYPO3\Flow\Cache\Frontend\FrontendInterface
     */
    protected $cache;

    /**
     * @var string
     */
    protected $cacheIdentifier;

    /**
     * The current application context
     * @var \TYPO3\Flow\Core\ApplicationContext
     */
    protected $context;

    /**
     * Default lifetime of a cache entry in seconds
     * @var integer
     */
    protected $defaultLifetime = 3600;

    /**
     * @var \TYPO3\Flow\Utility\Environment
     */
    protected $environment;

    /**
     * @var EnvironmentConfiguration
     */
    protected $environmentConfiguration;

    /**
     * Constructs this backend
     *
     * @param \TYPO3\Flow\Core\ApplicationContext $context Flow's application context
     * @param array $options Configuration options - depends on the actual backend
     * @param EnvironmentConfiguration $environmentConfiguration
     * @deprecated Use AbstractBackend instead
     * @api
     */
    public function __construct(\TYPO3\Flow\Core\ApplicationContext $context, array $options = array(), EnvironmentConfiguration $environmentConfiguration = null)
    {
        $this->context = $context;
        $this->environmentConfiguration = $environmentConfiguration;

        if (is_array($options) || $options instanceof \Iterator) {
            $this->setProperties($options);
        }
    }

    /**
     * Injects the Environment object
     *
     * @param \TYPO3\Flow\Utility\Environment $environment
     * @return void
     */
    public function injectEnvironment(\TYPO3\Flow\Utility\Environment $environment)
    {
        $this->environment = $environment;
    }
}
