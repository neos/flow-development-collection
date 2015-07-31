<?php
namespace TYPO3\Flow\Resource;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Model describing a resource pointer
 *
 * This class is deprecated. Please simply use the Resource->getHash() method instead.
 *
 * @deprecated
 * @see \TYPO3\Flow\Resource\Resource
 */
class ResourcePointer {

	/**
	 * @var string
	 */
	protected $Persistence_Object_Identifier;

	/**
	 * @var string
	 */
	protected $hash;

	/**
	 * Constructs this resource pointer
	 *
	 * @param string $hash
	 * @throws \InvalidArgumentException
	 * @deprecated
	 */
	public function __construct($hash) {
		$this->hash = $hash;
	}

	/**
	 * Returns the hash of this resource
	 *
	 * @return string A 40 character hexadecimal sha1 hash over the content of this resource
	 * @deprecated
	 */
	public function getHash() {
		return $this->hash;
	}

	/**
	 * Returns a string representation of this resource object.
	 *
	 * @return string The hash of this resource
	 * @deprecated
	 */
	public function __toString() {
		return $this->hash;
	}

}
