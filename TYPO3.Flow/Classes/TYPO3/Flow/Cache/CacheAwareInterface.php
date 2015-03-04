<?php
namespace TYPO3\Flow\Cache;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Interface for objects which are cache aware and are collaborative when it comes to storing them in a cache.
 *
 * @api
 */
interface CacheAwareInterface {

	/**
	 * Returns a string which distinctly identifies this object and thus can be used as an identifier for cache entries
	 * related to this object.
	 *
	 * @return string
	 */
	public function getCacheEntryIdentifier();

}
