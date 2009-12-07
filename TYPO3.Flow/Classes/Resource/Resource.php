<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Resource;

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
 * Model describing a resource
 *
 * @version $Id: Publisher.php 3523 2009-11-30 14:53:36Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 * @valueobject
 */
class Resource {

	/**
	 * @var string
	 */
	protected $hash;

	/**
	 * Constructs this resource
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($hash) {
		if (!is_string($hash) || strlen($hash) !== 40) {
			throw new \InvalidArgumentException('A valid sha1 hash must be passed to this constructor.', 1259748358);
		}
		$this->hash = $hash;
	}

	/**
	 * Returns the hash of this resource
	 *
	 * @return string A 40 character hexadecimal sha1 hash over the content of this resource
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getHash() {
		return $this->hash;
	}

	/**
	 * Returns the URI of the – probably private – storing location for the payload of this resource.
	 *
	 * @return \F3\FLOW3\Property\DataType\Uri
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getStoreUri() {
		return new \F3\FLOW3\Property\DataType\Uri(FLOW3_PATH_DATA . 'Persistent/Resources/' . $this->hash);
	}
}

?>