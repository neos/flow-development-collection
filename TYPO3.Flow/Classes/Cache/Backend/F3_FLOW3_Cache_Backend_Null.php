<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Cache::Backend;

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

// @codeCoverageIgnoreStart

/**
 * A caching backend which forgets everything immediately
 *
 * @package FLOW3
 * @subpackage Cache
 * @version $Id:F3::FLOW3::AOP::Framework.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Null extends F3::FLOW3::Cache::AbstractBackend {

	/**
	 * Acts as if it would save data
	 *
	 * @param string $entryIdentifier ignored
	 * @param string $data ignored
	 * @param array $tags ignored
	 * @param integer $lifetime ignored
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function save($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
	}

	/**
	 * Returns False
	 *
	 * @param string $entryIdentifier ignored
	 * @return boolean FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function load($entryIdentifier) {
		return FALSE;
	}

	/**
	 * Returns False
	 *
	 * @param string $entryIdentifier ignored
	 * @return boolean FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function has($entryIdentifier) {
		return FALSE;
	}

	/**
	 * Does nothing
	 *
	 * @param string $entryIdentifier ignored
	 * @return boolean FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function remove($entryIdentifier) {
		return FALSE;
	}

	/**
	 * Returns an empty array
	 *
	 * @param string $tag ignored
	 * @return array An empty array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findIdentifiersByTag($tag) {
		return array();
	}

	/**
	 * Does nothing
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flush() {
	}

	/**
	 * Does nothing
	 *
	 * @param string $tag ignored
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushByTag($tag) {
	}

	/**
	 * Does nothing
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function collectGarbage() {
	}
}
// @codeCoverageIgnoreEnd
?>