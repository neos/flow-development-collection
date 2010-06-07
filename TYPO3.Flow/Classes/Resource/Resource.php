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
 * @version $Id$
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
	 * @var string
	 */
	protected $fileExtension;

	/**
	 * Constructs this resource
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($hash, $fileExtension) {
		if (!is_string($hash) || strlen($hash) !== 40) {
			throw new \InvalidArgumentException('A valid sha1 hash must be passed to this constructor.', 1259748358);
		}
		if (!is_string($fileExtension) || substr(strtolower($fileExtension), -3, 3) === 'php') {
			throw new \InvalidArgumentException('A valid file extension must be passed to this constructor.', 1259748359);
		}
		$this->hash = $hash;
		$this->fileExtension = strtolower($fileExtension);
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
	 * Returns the file extension used for this resource
	 *
	 * @return string The file extension used for this resource
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getFileExtension() {
		return $this->fileExtension;
	}

	/**
	 * Returns the mime type for this resource
	 * 
	 * @return string The mime type
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMimeType() {
		return \F3\FLOW3\Utility\FileTypes::getMimeTypeFromFilename('x.' . $this->fileExtension);
	}

	/**
	 * Returns a string representation of this resource object.
	 *
	 * @return string The hash of this resource
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __toString() {
		return $this->hash;
	}
}

?>