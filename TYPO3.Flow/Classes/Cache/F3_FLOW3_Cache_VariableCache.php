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
 * A cache for any kinds of PHP variables
 *
 * @package FLOW3
 * @subpackage Cache
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class VariableCache extends \F3\FLOW3\Cache\AbstractCache {

	/**
	 * Saves the value of a PHP variable in the cache. Note that the variable
	 * will be serialized if necessary.
	 *
	 * @param string $entryIdentifier An identifier used for this cache entry
	 * @param mixed $variable The variable to cache
	 * @param array $tags Tags to associate with this cache entry
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function set($entryIdentifier, $variable, $tags = array()) {
		$this->backend->set($entryIdentifier, serialize($variable), $tags);
	}

	/**
	 * Finds and returns a variable value from the cache.
	 *
	 * @param string $entryIdentifier Identifier of the cache entry to fetch
	 * @return mixed The value
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function get($entryIdentifier) {
		return unserialize($this->backend->get($entryIdentifier));
	}

	/**
	 * Finds and returns all cache entries which are tagged by the specified tag.
	 * The asterisk ("*") is allowed as a wildcard at the beginning and the end of
	 * the tag.
	 *
	 * @param string $tag The tag to search for, the "*" wildcard is supported
	 * @return array An array with the content of all matching entries. An empty array if no entries matched
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getByTag($tag) {
		$entries = array();
		$identifiers = $this->backend->findIdentifiersByTag($tag);
		foreach ($identifiers as $identifier) {
			$entries[] = unserialize($this->backend->get($identifier));
		}
		return $entries;
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