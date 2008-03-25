<?php
declare(ENCODING = 'utf-8');

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
 * An abstract cache
 *
 * @package FLOW3
 * @subpackage Cache
 * @version $Id:F3_FLOW3_AOP_Framework.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
abstract class F3_FLOW3_Cache_AbstractCache {

	/**
	 * @var string Identifies this cache
	 */
	protected $identifier;

	/**
	 * @var F3_FLOW3_Cache_AbstractBackend
	 */
	protected $backend;

	/**
	 * Constructs the cache
	 *
	 * @param string $identifier A identifier which describes this cache
	 * @param F3_FLOW3_Cache_AbstractBackend $backend Backend to be used for this cache
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws InvalidArgumentException
	 */
	public function __construct($identifier, F3_FLOW3_Cache_AbstractBackend $backend) {
		if (!is_string($identifier) || strlen($identifier) == 0) throw new InvalidArgumentException('No valid identifier specified.', 1203584729);
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
	 * @return F3_FLOW3_Cache_AbstractBackend The backend used by this cache
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getBackend() {
		return $this->backend;
	}

	/**
	 * Saves data in the cache.
	 *
	 * @param string $entryIdentifier: Something which identifies the data - depends on concrete cache
	 * @param mixed $data: The data to cache - also depends on the concrete cache implementation
	 * @param array $tags: Tags to associate with this cache entry
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	abstract public function save($entryIdentifier, $data, $tags = array());

	/**
	 * Loads data from the cache.
	 *
	 * @param string $entryIdentifier: Something which identifies the cache entry - depends on concrete cache
	 * @return mixed
	 * @author Robert Lemke <robert@typo3.org>
	 */
	abstract public function load($entryIdentifier);

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier: An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @author Robert Lemke <robert@typo3.org>
	 */
	abstract public function has($entryIdentifier);
}
?>