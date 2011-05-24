<?php
declare(ENCODING = 'utf-8');
namespace TYPO3\FLOW3\Security\Cryptography;

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
 * A password hashing strategy interface
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
interface PasswordHashingStrategyInterface {

	/**
	 * Hash a password for storage
	 *
	 * @param string $password Cleartext password that will be hashed
	 * @param string $staticSalt Optional static salt that will not be stored in the hashed password
	 * @return string The hashed password with dynamic salt (if used)
	 */
	public function hashPassword($password, $staticSalt = NULL);

	/**
	 * Validate a hashed password against a cleartext password
	 *
	 * @param string $password
	 * @param string $hashedPasswordAndSalt Hashed password with dynamic salt (if used)
	 * @param string $staticSalt Optional static salt that will not be stored in the hashed password
	 * @return boolean TRUE if the given cleartext password matched the hashed password
	 */
	public function validatePassword($password, $hashedPasswordAndSalt, $staticSalt = NULL);

}
?>