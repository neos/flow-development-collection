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
	 */
	public function setCache(\F3\FLOW3\Cache\Frontend\FrontendInterface $cache) {
		$this->cache = $cache;
	}


}
?>