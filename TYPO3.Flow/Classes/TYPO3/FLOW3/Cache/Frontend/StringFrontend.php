<?php
namespace TYPO3\FLOW3\Cache\Frontend;

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
 * A cache frontend for strings. Nothing else.
 *
 * @api
 */
class StringFrontend extends \TYPO3\FLOW3\Cache\Frontend\AbstractFrontend {

	/**
	 * Saves the value of a PHP variable in the cache.
	 *
	 * @param string $entryIdentifier An identifier used for this cache entry
	 * @param string $string The variable to cache
	 * @param array $tags Tags to associate with this cache entry
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
	 * @return void
	 * @throws \TYPO3\FLOW3\Cache\Exception\InvalidDataException
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function set($entryIdentifier, $string, array $tags = array(), $lifetime = NULL) {
		if (!$this->isValidEntryIdentifier($entryIdentifier)) throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233057566);
		if (!is_string($string)) throw new \TYPO3\FLOW3\Cache\Exception\InvalidDataException('Given data is of type "' . gettype($string) . '", but a string is expected for string cache.', 1222808333);
		foreach ($tags as $tag) {
			if (!$this->isValidTag($tag)) throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1233057512);
		}

		$this->backend->set($entryIdentifier, $string, $tags, $lifetime);
	}

	/**
	 * Finds and returns a variable value from the cache.
	 *
	 * @param string $entryIdentifier Identifier of the cache entry to fetch
	 * @return string The value
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function get($entryIdentifier) {
		if (!$this->isValidEntryIdentifier($entryIdentifier)) {
			throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233057752);
		}

		return $this->backend->get($entryIdentifier);
	}

	/**
	 * Finds and returns all cache entries which are tagged by the specified tag.
	 *
	 * @param string $tag The tag to search for
	 * @return array An array with the content of all matching entries. An empty array if no entries matched
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function getByTag($tag) {
		if (!$this->isValidTag($tag)) throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1233057772);

		$entries = array();
		$identifiers = $this->backend->findIdentifiersByTag($tag);
		foreach ($identifiers as $identifier) {
			$entries[] = $this->backend->get($identifier);
		}
		return $entries;
	}

}
?>