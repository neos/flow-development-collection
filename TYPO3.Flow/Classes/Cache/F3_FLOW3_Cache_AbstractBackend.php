<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Cache;

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
 * @version $Id:\F3\FLOW3\AOP\Framework.php 201 2007-03-30 11:18:30Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
abstract class AbstractBackend implements \F3\FLOW3\Cache\BackendInterface {

	/**
	 * @var \F3\FLOW3\Cache\AbstractCache Reference to the cache which uses this backend
	 */
	protected $cache;

	/**
	 * @var \F3\FLOW3\SignalSlot\Dispatcher
	 */
	protected $signalDispatcher;

	/**
	 * @var string The current application context
	 */
	protected $context;

	/**
	 * @var integer Default lifetime of a cache entry in seconds
	 */
	protected $defaultLifetime = 3600;


	/**
	 * Constructs this backend
	 *
	 * @param string $context FLOW3's application context
	 * @param mixed $options Configuration options - depends on the actual backend
	 */
	public function __construct($context, $options = array()) {
		$this->context = $context;
		if (is_array($options) || $options instanceof ArrayAccess) {
			foreach ($options as $optionKey => $optionValue) {
				$methodName = 'set' . ucfirst($optionKey);
				if (method_exists($this, $methodName)) {
					$this->$methodName($optionValue);
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
	 * Sets a reference to the cache which uses this backend
	 *
	 * @param \F3\FLOW3\Cache\CacheInterface $cache The frontend for this backend
	 * @return void
	 */
	public function setCache(\F3\FLOW3\Cache\CacheInterface $cache) {
		$this->cache = $cache;
	}

	/**
	 * Checks the validity of an entry identifier. Returns true if it's valid.
	 *
	 * @param string An identifier to be checked for validity
	 * @return boolean
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function isValidEntryIdentifier($identifier) {
		return preg_match(self::PATTERN_ENTRYIDENTIFIER, $identifier) === 1;
	}

	/**
	 * Checks the validity of a tag. Returns true if it's valid.
	 *
	 * @param string An identifier to be checked for validity
	 * @return boolean
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isValidTag($tag) {
		return preg_match(self::PATTERN_TAG, $tag) === 1;
	}

}
?>