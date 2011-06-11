<?php
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
 * A cache frontend tailored to PHP code.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class PhpFrontend extends \F3\FLOW3\Cache\Frontend\StringFrontend {

	/**
	 * Constructs the cache
	 *
	 * @param string $identifier A identifier which describes this cache
	 * @param \F3\FLOW3\Cache\Backend\PhpCapableBackendInterface $backend Backend to be used for this cache
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($identifier, \F3\FLOW3\Cache\Backend\PhpCapableBackendInterface $backend) {
		parent::__construct($identifier, $backend);
	}

	/**
	 * Saves the PHP source code in the cache.
	 *
	 * @param string $entryIdentifier An identifier used for this cache entry, for example the class name
	 * @param string $sourceCode PHP source code
	 * @param array $tags Tags to associate with this cache entry
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function set($entryIdentifier, $sourceCode, array $tags = array(), $lifetime = NULL) {
		if (!$this->isValidEntryIdentifier($entryIdentifier)) throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1264023823);
		if (!is_string($sourceCode)) throw new \F3\FLOW3\Cache\Exception\InvalidDataException('The given source code is not a valid string.', 1264023824);
		foreach ($tags as $tag) {
			if (!$this->isValidTag($tag)) throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1264023825);
		}
		$sourceCode = '<?php' . chr(10) . $sourceCode . chr(10) . '#';
		$this->backend->set($entryIdentifier, $sourceCode, $tags, $lifetime);
	}

	/**
	 * Loads PHP code from the cache and require_onces it right away.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed Potential return value from the include operation
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function requireOnce($entryIdentifier) {
		return $this->backend->requireOnce($entryIdentifier);
	}

}
?>