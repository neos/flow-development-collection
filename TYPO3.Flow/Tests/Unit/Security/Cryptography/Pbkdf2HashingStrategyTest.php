<?php
declare(ENCODING = 'utf-8');
namespace TYPO3\FLOW3\Tests\Unit\Security\Cryptography;

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
 * Testcase for the Pbkdf2HashingStrategy
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class Pbkdf2HashingStrategyTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function hashPasswordWithMatchingPasswordAndParametersSucceeds() {
		$strategy = new \TYPO3\FLOW3\Security\Cryptography\Pbkdf2HashingStrategy(8, 1000, 64, 'sha256');
		$derivedKeyWithSalt = $strategy->hashPassword('password', 'MyStaticSalt');

		$this->assertTrue($strategy->validatePassword('password', $derivedKeyWithSalt, 'MyStaticSalt'));
		$this->assertFalse($strategy->validatePassword('pass', $derivedKeyWithSalt, 'MyStaticSalt'));
		$this->assertFalse($strategy->validatePassword('password', $derivedKeyWithSalt, 'SomeSalt'));
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function hashAndValidatePasswordWithNotMatchingPasswordOrParametersFails() {
		$strategy = new \TYPO3\FLOW3\Security\Cryptography\Pbkdf2HashingStrategy(8, 1000, 64, 'sha256');
		$derivedKeyWithSalt = $strategy->hashPassword('password', 'MyStaticSalt');

		$this->assertFalse($strategy->validatePassword('pass', $derivedKeyWithSalt, 'MyStaticSalt'), 'Different password should not match');
		$this->assertFalse($strategy->validatePassword('password', $derivedKeyWithSalt, 'SomeSalt'), 'Different static salt should not match');

		$strategy = new \TYPO3\FLOW3\Security\Cryptography\Pbkdf2HashingStrategy(8, 99, 64, 'sha256');
		$this->assertFalse($strategy->validatePassword('password', $derivedKeyWithSalt, 'MyStaticSalt'), 'Different iteration should not match');
	}

}
?>