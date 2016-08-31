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

/**
 * An abstract caching backend
 *
 * @api
 */
abstract class AbstractBackend implements \TYPO3\Flow\Cache\Backend\BackendInterface
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
     * Constructs this backend
     *
     * @param \TYPO3\Flow\Core\ApplicationContext $context Flow's application context
     * @param array $options Configuration options - depends on the actual backend
     * @throws \InvalidArgumentException
     * @api
     */
    public function __construct(\TYPO3\Flow\Core\ApplicationContext $context, array $options = array())
    {
        $this->context = $context;
        if (is_array($options) || $options instanceof \ArrayAccess) {
            foreach ($options as $optionKey => $optionValue) {
                $methodName = 'set' . ucfirst($optionKey);
                if (method_exists($this, $methodName)) {
                    $this->$methodName($optionValue);
                } else {
                    throw new \InvalidArgumentException('Invalid cache backend option "' . $optionKey . '" for backend of type "' . get_class($this) . '"', 1231267498);
                }
            }
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

    /**
     * Sets a reference to the cache frontend which uses this backend
     *
     * @param \TYPO3\Flow\Cache\Frontend\FrontendInterface $cache The frontend for this backend
     * @return void
     * @api
     */
    public function setCache(\TYPO3\Flow\Cache\Frontend\FrontendInterface $cache)
    {
        $this->cache = $cache;
        $this->cacheIdentifier = $this->cache->getIdentifier();
    }

    /**
     * Returns the internally used, prefixed entry identifier for the given public
     * entry identifier.
     *
     * While Flow applications will mostly refer to the simple entry identifier, it
     * may be necessary to know the actual identifier used by the cache backend
     * in order to share cache entries with other applications. This method allows
     * for retrieving it.
     *
     * Note that, in case of the AbstractBackend, this method is returns just the
     * given entry identifier.
     *
     * @param string $entryIdentifier The short entry identifier, for example "NumberOfPostedArticles"
     * @return string The prefixed identifier, for example "Flow694a5c7a43a4_NumberOfPostedArticles"
     * @api
     */
    public function getPrefixedIdentifier($entryIdentifier)
    {
        return $entryIdentifier;
    }

    /**
     * Sets the default lifetime for this cache backend
     *
     * @param integer $defaultLifetime Default lifetime of this cache backend in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
     * @return void
     * @throws \InvalidArgumentException
     * @api
     */
    public function setDefaultLifetime($defaultLifetime)
    {
        if (!is_int($defaultLifetime) || $defaultLifetime < 0) {
            throw new \InvalidArgumentException('The default lifetime must be given as a positive integer.', 1233072774);
        }
        $this->defaultLifetime = $defaultLifetime;
    }

    /**
     * Calculates the expiry time by the given lifetime. If no lifetime is
     * specified, the default lifetime is used.
     *
     * @param integer $lifetime The lifetime in seconds
     * @return \DateTime The expiry time
     */
    protected function calculateExpiryTime($lifetime = null)
    {
        if ($lifetime === self::UNLIMITED_LIFETIME || ($lifetime === null && $this->defaultLifetime === self::UNLIMITED_LIFETIME)) {
            return new \DateTime(self::DATETIME_EXPIRYTIME_UNLIMITED, new \DateTimeZone('UTC'));
        }

        if ($lifetime === null) {
            $lifetime = $this->defaultLifetime;
        }
        return new \DateTime('now +' . $lifetime . ' seconds', new \DateTimeZone('UTC'));
    }
}
