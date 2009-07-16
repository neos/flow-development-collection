<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Cryptography;

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
 * A RSA key
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 * @scope prototype
 */
class OpenSSLRSAKey {

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
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct($modulus, $keyString) {
		$this->modulus = $modulus;
		$this->keyString = $keyString;
	}

	/**
	 * Returns the modulus in HEX representation
	 *
	 * @return string The modulus
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getModulus() {
		return $this->modulus;
	}

	/**
	 * Returns the key string
	 *
	 * @return string The key string
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getKeyString() {
		return $this->keyString;
	}
}
?>