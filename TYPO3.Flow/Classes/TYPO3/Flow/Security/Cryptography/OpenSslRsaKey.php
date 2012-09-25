<?php
namespace TYPO3\Flow\Security\Cryptography;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * An RSA key
 *
 */
class OpenSslRsaKey {

	/**
	 * @var string
	 */
	protected $modulus;

	/**
	 * @var string
	 */
	protected $keyString;

	/**
	 * Constructor
	 *
	 * @param string $modulus The HEX modulus
	 * @param string $keyString The private key string
	 */
	public function __construct($modulus, $keyString) {
		$this->modulus = $modulus;
		$this->keyString = $keyString;
	}

	/**
	 * Returns the modulus in HEX representation
	 *
	 * @return string The modulus
	 */
	public function getModulus() {
		return $this->modulus;
	}

	/**
	 * Returns the key string
	 *
	 * @return string The key string
	 */
	public function getKeyString() {
		return $this->keyString;
	}
}
?>