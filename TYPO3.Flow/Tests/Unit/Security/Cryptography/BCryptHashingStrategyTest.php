<?php
namespace TYPO3\Flow\Tests\Unit\Security\Cryptography;

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
 * Testcase for the BCryptHashingStrategy
 *
 */
class BCryptHashingStrategyTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * Test the implementation using the sample hashes shown on http://php.net/crypt
	 * @test
	 */
	public function systemSupportsBlowfishCryptMethod() {
		$this->assertTrue(\CRYPT_BLOWFISH === 1);

		$cryptResult = crypt('rasmuslerdorf', '$2a$07$usesomesillystringforsalt$');
		$this->assertEquals('$2a$07$usesomesillystringfore2uDLvp1Ii2e./U9C8sBjqp8I90dH6hi', $cryptResult);
	}

	/**
	 * @test
	 */
	public function hashPasswordWithMatchingPasswordAndParametersSucceeds() {
		$strategy = new \TYPO3\Flow\Security\Cryptography\BCryptHashingStrategy(10);
		$derivedKeyWithSalt = $strategy->hashPassword('password');

		$this->assertTrue($strategy->validatePassword('password', $derivedKeyWithSalt));
		$this->assertFalse($strategy->validatePassword('pass', $derivedKeyWithSalt));
	}

	/**
	 * @test
	 */
	public function hashAndValidatePasswordWithNotMatchingPasswordFails() {
		$strategy = new \TYPO3\Flow\Security\Cryptography\BCryptHashingStrategy(10);
		$derivedKeyWithSalt = $strategy->hashPassword('password');

		$this->assertFalse($strategy->validatePassword('pass', $derivedKeyWithSalt), 'Different password should not match');
	}

	/**
	 * @test
	 */
	public function hashAndValidatePasswordWithDifferentCostsMatch() {
		$strategy = new \TYPO3\Flow\Security\Cryptography\BCryptHashingStrategy(10);

		$otherStrategy = new \TYPO3\Flow\Security\Cryptography\BCryptHashingStrategy(6);
		$derivedKeyWithSalt = $otherStrategy->hashPassword('password');

		$this->assertTrue($strategy->validatePassword('password', $derivedKeyWithSalt), 'Hashing strategy should validate password with different cost');
	}

	/**
	 * @test
	 */
	public function validatePasswordWithInvalidHashFails() {
		$strategy = new \TYPO3\Flow\Security\Cryptography\BCryptHashingStrategy(10);

		$this->assertFalse($strategy->validatePassword('password', ''));
		$this->assertFalse($strategy->validatePassword('password', '$1$abc'));
		$this->assertFalse($strategy->validatePassword('password', '$2x$01$012345678901234567890123456789'));
	}

}
?>