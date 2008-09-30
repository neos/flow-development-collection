<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Cache;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Cache
 * @version $Id$
 */

/**
 * A cache for strings. Nothing else.
 *
 * @package FLOW3
 * @subpackage Cache
 * @version $Id:F3::FLOW3::AOP::Framework.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class StringCache extends F3::FLOW3::Cache::AbstractCache {

	/**
	 * Saves the value of a PHP variable in the cache. Note that the variable
	 * will be serialized if necessary.
	 *
	 * @param string $entryIdentifier An identifier used for this cache entry
	 * @param string $string The variable to cache
	 * @param array $tags Tags to associate with this cache entry
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function save($entryIdentifier, $string, $tags = array()) {
		if (!is_string($string)) throw new F3::FLOW3::Cache::Exception::InvalidData('Only strings can be digested by the StringCache. Thanks.', 1222808333);

		$this->backend->save($entryIdentifier, $string, $tags);
	}

	/**
	 * Loads a variable value from the cache.
	 *
	 * @param string $entryIdentifier Identifier of the cache entry to fetch
	 * @return string The value
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function load($entryIdentifier) {
		return $this->backend->load($entryIdentifier);
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function has($entryIdentifier) {
		return $this->backend->has($entryIdentifier);
	}

	/**
	 * Removes the given cache entry from the cache.
	 *
	 * @param string $entryIdentifier An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function remove($entryIdentifier) {
		return $this->backend->remove($entryIdentifier);
	}
}
?>