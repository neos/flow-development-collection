<?php
namespace TYPO3\FLOW3\Cache\Backend;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * An abstract caching backend
 *
 * @api
 */
abstract class AbstractBackend implements \TYPO3\FLOW3\Cache\Backend\BackendInterface {

	const DATETIME_EXPIRYTIME_UNLIMITED = '9999-12-31T23:59:59+0000';
	const UNLIMITED_LIFETIME = 0;

	/**
	 * Reference to the cache frontend which uses this backend
	 * @var \TYPO3\FLOW3\Cache\Frontend\FrontendInterface
	 */
	protected $cache;

	/**
	 * @var string
	 */
	protected $cacheIdentifier;

	/**
	 * The current application context
	 * @var \TYPO3\FLOW3\Core\ApplicationContext
	 */
	protected $context;

	/**
	 * Default lifetime of a cache entry in seconds
	 * @var integer
	 */
	protected $defaultLifetime = 3600;

	/**
	 * @var \TYPO3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * Constructs this backend
	 *
	 * @param \TYPO3\FLOW3\Core\ApplicationContext $context FLOW3's application context
	 * @param array $options Configuration options - depends on the actual backend
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function __construct(\TYPO3\FLOW3\Core\ApplicationContext $context, array $options = array()) {
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
	 * @param \TYPO3\FLOW3\Utility\Environment $environment
	 * @return void
	 */
	public function injectEnvironment(\TYPO3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Sets a reference to the cache frontend which uses this backend
	 *
	 * @param \TYPO3\FLOW3\Cache\Frontend\FrontendInterface $cache The frontend for this backend
	 * @return void
	 * @api
	 */
	public function setCache(\TYPO3\FLOW3\Cache\Frontend\FrontendInterface $cache) {
		$this->cache = $cache;
		$this->cacheIdentifier = $this->cache->getIdentifier();
	}

	/**
	 * Sets the default lifetime for this cache backend
	 *
	 * @param integer $defaultLifetime Default lifetime of this cache backend in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function setDefaultLifetime($defaultLifetime) {
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
	protected function calculateExpiryTime($lifetime = NULL) {
		if ($lifetime === self::UNLIMITED_LIFETIME || ($lifetime === NULL && $this->defaultLifetime === self::UNLIMITED_LIFETIME)) {
			$expiryTime = new \DateTime(self::DATETIME_EXPIRYTIME_UNLIMITED, new \DateTimeZone('UTC'));
		} else {
			if ($lifetime === NULL) {
				$lifetime = $this->defaultLifetime;
			}
			$expiryTime = new \DateTime('now +' . $lifetime . ' seconds', new \DateTimeZone('UTC'));
		}
		return $expiryTime;
	}
}
?>