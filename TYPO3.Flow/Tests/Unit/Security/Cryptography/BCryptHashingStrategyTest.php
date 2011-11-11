<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\Cryptography;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
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
class BCryptHashingStrategyTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

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
		$strategy = new \TYPO3\FLOW3\Security\Cryptography\BCryptHashingStrategy(10);
		$derivedKeyWithSalt = $strategy->hashPassword('password');

		$this->assertTrue($strategy->validatePassword('password', $derivedKeyWithSalt));
		$this->assertFalse($strategy->validatePassword('pass', $derivedKeyWithSalt));
	}

	/**
	 * @test
	 */
	public function hashAndValidatePasswordWithNotMatchingPasswordOrParametersFails() {
		$strategy = new \TYPO3\FLOW3\Security\Cryptography\BCryptHashingStrategy(10);
		$derivedKeyWithSalt = $strategy->hashPassword('password');

		$this->assertFalse($strategy->validatePassword('pass', $derivedKeyWithSalt), 'Different password should not match');

		$strategy = new \TYPO3\FLOW3\Security\Cryptography\BCryptHashingStrategy(8);
		$this->assertFalse($strategy->validatePassword('password', $derivedKeyWithSalt), 'Different cost should not match');
	}

}
?>