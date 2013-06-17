<?php
namespace TYPO3\Flow\Cache\Frontend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Cache\Backend\IterableBackendInterface;

/**
 * An iterator for cache entries
 *
 * @api
 */
class CacheEntryIterator implements \Iterator {

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\FrontendInterface
	 */
	protected $frontend;

	/**
	 * @var \TYPO3\Flow\Cache\Backend\IterableBackendInterface
	 */
	protected $backend;

	/**
	 * Constructs this Iterator
	 *
	 * @param \TYPO3\Flow\Cache\Frontend\FrontendInterface $frontend Frontend of the cache to iterate over
	 * @param \TYPO3\Flow\Cache\Backend\IterableBackendInterface $backend Backend of the cache
	 * @param integer $chunkSize Determines the number of entries fetched by the backend at once (for future use)
	 */
	public function __construct(FrontendInterface $frontend, IterableBackendInterface $backend, $chunkSize = NULL) {
		$this->frontend = $frontend;
		$this->backend = $backend;
		$this->backend->rewind();
	}

	/**
	 * Returns the data of the current cache entry pointed to by the cache entry
	 * iterator.
	 *
	 * @return mixed
	 * @api
	 */
	public function current() {
		return $this->frontend->get($this->backend->key());
	}

	/**
	 * Move forward to the next cache entry
	 *
	 * @return void
	 * @api
	 */
	public function next() {
		$this->backend->next();
	}

	/**
	 * Returns the identifier of the current cache entry pointed to by the cache
	 * entry iterator.
	 *
	 * @return string
	 * @api
	 */
	public function key() {
		return $this->backend->key();
	}

	/**
	 * Checks if current position of the cache entry iterator is valid
	 *
	 * @return boolean TRUE if the current element of the iterator is valid, otherwise FALSE
	 * @api
	 */
	public function valid() {
		return $this->backend->valid();
	}

	/**
	 * Rewind the cache entry iterator to the first element
	 *
	 * @return void
	 * @api
	 */
	public function rewind() {
		$this->backend->rewind();
	}

}
?>