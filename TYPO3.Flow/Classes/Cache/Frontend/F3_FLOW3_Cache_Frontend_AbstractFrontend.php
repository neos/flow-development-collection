<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Cache\Frontend;

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
 * An abstract cache
 *
 * @package FLOW3
 * @subpackage Cache
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
abstract class AbstractFrontend implements \F3\FLOW3\Cache\Frontend\FrontendInterface {

	/**
	 * @var string Identifies this cache
	 */
	protected $identifier;

	/**
	 * @var \F3\FLOW3\Cache\Backend\AbstractBackend
	 */
	protected $backend;

	/**
	 * Constructs the cache
	 *
	 * @param string $identifier A identifier which describes this cache
	 * @param \F3\FLOW3\Cache\Backend\BackendInterface $backend Backend to be used for this cache
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \InvalidArgumentException if the identifier doesn't match PATTERN_ENTRYIDENTIFIER
	 */
	public function __construct($identifier, \F3\FLOW3\Cache\Backend\BackendInterface $backend) {
		if (!preg_match(self::PATTERN_ENTRYIDENTIFIER, $identifier)) throw new \InvalidArgumentException('"' . $identifier . '" is not a valid cache identifier.', 1203584729);
		$this->identifier = $identifier;
		$this->backend = $backend;
		$this->backend->setCache($this);
	}

	/**
	 * Returns this cache's identifier
	 *
	 * @return string The identifier for this cache
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * Returns the backend used by this cache
	 *
	 * @return \F3\FLOW3\Cache\Backend\BackendInterface The backend used by this cache
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getBackend() {
		return $this->backend;
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function has($entryIdentifier) {
		if (!$this->isValidEntryIdentifier($entryIdentifier)) throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233058486);

		return $this->backend->has($entryIdentifier);
	}

	/**
	 * Removes the given cache entry from the cache.
	 *
	 * @param string $entryIdentifier An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function remove($entryIdentifier) {
		if (!$this->isValidEntryIdentifier($entryIdentifier)) throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233058495);

		return $this->backend->remove($entryIdentifier);
	}

	/**
	 * Removes all cache entries of this cache.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flush() {
		$this->backend->flush();
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string $tag The tag the entries must have
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function flushByTag($tag) {
		if (!$this->isValidTag($tag)) throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1233057359);

		$this->backend->flushByTag($tag);
	}

	/**
	 * Does garbage collection
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function collectGarbage() {
		$this->backend->collectGarbage();
	}

	/**
	 * Renders a tag which can be used to mark a cache entry as "depends on this class".
	 * Whenever the specified class is modified, all cache entries tagged with the
	 * class are flushed.
	 *
	 * If an empty string is specified as class name, the returned tag means "depends on any class".
	 *
	 * @param string $className The class name
	 * @return string Class Tag
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassTag($className = '') {
		return ($className === '') ? self::TAG_CLASS : self::TAG_CLASS . str_replace('\\', '_', $className);
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