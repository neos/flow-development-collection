<?php
namespace TYPO3\Flow\Object\Proxy;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A marker interface for Proxy Classes
 *
 */
interface ProxyInterface {

	/**
	 * Wake up method.
	 *
	 * Proxies need to have one as at least session handling relies on it.
	 *
	 * @return void
	 */
	public function __wakeup();

}
