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
 * A contract for a Cache Backend
 *
 * @package FLOW3
 * @subpackage Cache
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 * @author Robert Lemke <robert@typo3.org>
 */
interface BackendInterface {

	const TAG_CLASS = '%CLASS%';

	/**
	 * Pattern an entry identifer must match.
	 */
	const PATTERN_ENTRYIDENTIFIER = '/^[a-zA-Z0-9_%]{1,250}$/';

	/**
	 * Pattern an entry identifer must match.
	 */
	const PATTERN_TAG = '/^[a-zA-Z0-9_%]{1,250}$/';

	/**
	 * Sets a reference to the cache which uses this backend
	 *
	 * @param \F3\FLOW3\Cache\CacheInterface $cache The frontend for this backend
	 * @return void
	 */
	public function setCache(\F3\FLOW3\Cache\CacheInterface $cache);

	/**
	 * Saves data in the cache.
	 *
	 * @param string $entryIdentifier: An identifier for this specific cache entry
	 * @param string $data: The data to be stored
	 * @param array $tags: Tags to associate with this cache entry
	 * @param integer $lifetime: Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @throws \F3\FLOW3\Cache\Exception if no cache frontend has been set.
	 * @throws \InvalidArgumentException if the identifier is not valid
	 * @throws \F3\FLOW3\Cache\Exception\InvalidData if $data is not a string
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL);

	/**
	 * Loads data from the cache.
	 *
	 * @param string $entryIdentifier: An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 */
	public function get($entryIdentifier);

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier: An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 */
	public function has($entryIdentifier);

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry but if - for what reason ever -
	 * old entries for the identifier still exist, they are removed as well.
	 *
	 * @param string $entryIdentifier: Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 */
	public function remove($entryIdentifier);

	/**
	 * Removes all cache entries of this cache.
	 *
	 * @return void
	 */
	public function flush();

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string $tag The tag the entries must have
	 * @return void
	 */
	public function flushByTag($tag);

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the
	 * specified tag.
	 * The asterisk ("*") is allowed as a wildcard at the beginning and the end of
	 * the tag.
	 *
	 * @param string $tag The tag to search for, the "*" wildcard is supported
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 */
	public function findIdentifiersByTag($tag);

	/**
	 * Does garbage collection
	 *
	 * @return void
	 */
	public function collectGarbage();

	/**
	 * Checks the validity of an entry identifier. Returns true if it's valid.
	 *
	 * @param string An identifier to be checked for validity
	 * @return boolean
	 */
	public function isValidEntryIdentifier($identifier);

	/**
	 * Checks the validity of a tag. Returns true if it's valid.
	 *
	 * @param string An identifier to be checked for validity
	 * @return boolean
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isValidTag($tag);

}
?>