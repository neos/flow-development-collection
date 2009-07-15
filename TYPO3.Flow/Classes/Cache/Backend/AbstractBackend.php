<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Cache\Backend;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Cache
 * @version $Id$
 */

/**
 * An abstract caching backend
 *
 * @package FLOW3
 * @subpackage Cache
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class AbstractBackend implements \F3\FLOW3\Cache\Backend\BackendInterface {

	const DATETIME_EXPIRYTIME_UNLIMITED = '9999-12-31T23:59:59+0000';
	const UNLIMITED_LIFETIME = 0;

	/**
	 * Reference to the cache frontend which uses this backend
	 * @var \F3\FLOW3\Cache\Frontend\FrontendInterface
	 */
	protected $cache;

	/**
	 * @var \F3\FLOW3\SignalSlot\Dispatcher
	 */
	protected $signalDispatcher;

	/**
	 * The current application context
	 * @var string
	 */
	protected $context;

	/**
	 * Default lifetime of a cache entry in seconds
	 * @var integer
	 */
	protected $defaultLifetime = 3600;

	/**
	 * Constructs this backend
	 *
	 * @param string $context FLOW3's application context
	 * @param mixed $options Configuration options - depends on the actual backend
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function __construct($context, $options = array()) {
		$this->context = $context;
		if (is_array($options) || $options instanceof ArrayAccess) {
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
	 * Injects the Signal Dispatcher.
	 *
	 * This is necessary because the classes of the Cache subpackage cannot be proxied
	 * by the AOP framework because AOP itself requires caching and therefore is not
	 * available at the time caching is initialized.
	 *
	 * @param \F3\FLOW3\SignalSlot\Dispatcher
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSignalDispatcher(\F3\FLOW3\SignalSlot\Dispatcher $signalDispatcher) {
		$this->signalDispatcher = $signalDispatcher;
	}

	/**
	 * Sets a reference to the cache frontend which uses this backend
	 *
	 * @param \F3\FLOW3\Cache\Frontend\FrontendInterface $cache The frontend for this backend
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setCache(\F3\FLOW3\Cache\Frontend\FrontendInterface $cache) {
		$this->cache = $cache;
	}

	/**
	 * Sets the default lifetime for this cache backend
	 *
	 * @param integer $defaultLifeTime Default lifetime of this cache backend in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function setDefaultLifetime($defaultLifetime) {
		if (!is_int($defaultLifetime) || $defaultLifetime < 0) throw new \InvalidArgumentException('The default lifetime must be given as a positive integer.', 1233072774);
		$this->defaultLifetime = $defaultLifetime;
	}

	/**
	 * Calculates the expiry time by the given lifetime. If no lifetime is
	 * specified, the default lifetime is used.
	 *
	 * @param integer $lifetime The lifetime in seconds
	 * @return \DateTime The expiry time
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function calculateExpiryTime($lifetime = NULL) {
		if ($lifetime === self::UNLIMITED_LIFETIME || ($lifetime === NULL && $this->defaultLifetime === self::UNLIMITED_LIFETIME)) {
			$expiryTime = new \DateTime(self::DATETIME_EXPIRYTIME_UNLIMITED, new \DateTimeZone('UTC'));
		} else {
			if ($lifetime === NULL) $lifetime = $this->defaultLifetime;
			$expiryTime = new \DateTime('now +' . $lifetime . ' seconds', new \DateTimeZone('UTC'));
		}
		return $expiryTime;
	}
}
?>