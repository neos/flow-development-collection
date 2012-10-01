<?php
namespace TYPO3\FLOW3\Cache\Backend;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

// @codeCoverageIgnoreStart


/**
 * A caching backend which forgets everything immediately
 *
 * @api
 */
class NullBackend extends AbstractBackend implements PhpCapableBackendInterface, TaggableBackendInterface {

	/**
	 * Acts as if it would save data
	 *
	 * @param string $entryIdentifier ignored
	 * @param string $data ignored
	 * @param array $tags ignored
	 * @param integer $lifetime ignored
	 * @return void
	 * @api
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
	}

	/**
	 * Returns False
	 *
	 * @param string $entryIdentifier ignored
	 * @return boolean FALSE
	 * @api
	 */
	public function get($entryIdentifier) {
		return FALSE;
	}

	/**
	 * Returns False
	 *
	 * @param string $entryIdentifier ignored
	 * @return boolean FALSE
	 * @api
	 */
	public function has($entryIdentifier) {
		return FALSE;
	}

	/**
	 * Does nothing
	 *
	 * @param string $entryIdentifier ignored
	 * @return boolean FALSE
	 * @api
	 */
	public function remove($entryIdentifier) {
		return FALSE;
	}

	/**
	 * Returns an empty array
	 *
	 * @param string $tag ignored
	 * @return array An empty array
	 * @api
	 */
	public function findIdentifiersByTag($tag) {
		return array();
	}

	/**
	 * Does nothing
	 *
	 * @return void
	 * @api
	 */
	public function flush() {
	}

	/**
	 * Does nothing
	 *
	 * @param string $tag ignored
	 * @return void
	 * @api
	 */
	public function flushByTag($tag) {
	}

	/**
	 * Does nothing
	 *
	 * @return void
	 * @api
	 */
	public function collectGarbage() {
	}

	/**
	 * Does nothing
	 *
	 * @param string $identifier An identifier which describes the cache entry to load
	 * @return void
	 * @api
	 */
	public function requireOnce($identifier) {
	}
}
// @codeCoverageIgnoreEnd
?>