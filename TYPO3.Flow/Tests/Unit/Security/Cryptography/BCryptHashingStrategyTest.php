<?php
namespace TYPO3\Flow\Tests\Unit\Security\Cryptography;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use RandomLib\Generator;

/**
 * Testcase for the BCryptHashingStrategy
 *
 */
class BCryptHashingStrategyTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * Test the implementation using the sample hashes shown on http://php.net/crypt
     * @test
     */
    public function systemSupportsBlowfishCryptMethod()
    {
        $this->assertTrue(\CRYPT_BLOWFISH === 1);

        $cryptResult = crypt('rasmuslerdorf', '$2a$07$usesomesillystringforsalt$');
        $this->assertEquals('$2a$07$usesomesillystringfore2uDLvp1Ii2e./U9C8sBjqp8I90dH6hi', $cryptResult);
    }

    /**
     * @test
     */
    public function hashPasswordWithMatchingPasswordAndParametersSucceeds()
    {
        $strategy = new \TYPO3\Flow\Security\Cryptography\BCryptHashingStrategy(10);

        $mockRandomGenerator = $this->getMock(Generator::class, array(), array(), '', false);
        $mockRandomGenerator->expects($this->any())->method('generateString')->willReturn('00ff00ff00ff00ff00ff00ff00ff00ff');
        $this->inject($strategy, 'randomGenerator', $mockRandomGenerator);

        $derivedKeyWithSalt = $strategy->hashPassword('password');

        $this->assertTrue($strategy->validatePassword('password', $derivedKeyWithSalt));
        $this->assertFalse($strategy->validatePassword('pass', $derivedKeyWithSalt));
    }

    /**
     * @test
     */
    public function hashAndValidatePasswordWithNotMatchingPasswordFails()
    {
        $strategy = new \TYPO3\Flow\Security\Cryptography\BCryptHashingStrategy(10);

        $mockRandomGenerator = $this->getMock(Generator::class, array(), array(), '', false);
        $mockRandomGenerator->expects($this->any())->method('generateString')->willReturn('00ff00ff00ff00ff00ff00ff00ff00ff');
        $this->inject($strategy, 'randomGenerator', $mockRandomGenerator);

        $derivedKeyWithSalt = $strategy->hashPassword('password');

        $this->assertFalse($strategy->validatePassword('pass', $derivedKeyWithSalt), 'Different password should not match');
    }

    /**
     * @test
     */
    public function hashAndValidatePasswordWithDifferentCostsMatch()
    {
        $strategy = new \TYPO3\Flow\Security\Cryptography\BCryptHashingStrategy(10);

        $otherStrategy = new \TYPO3\Flow\Security\Cryptography\BCryptHashingStrategy(6);

        $mockRandomGenerator = $this->getMock(Generator::class, array(), array(), '', false);
        $mockRandomGenerator->expects($this->any())->method('generateString')->willReturn('00ff00ff00ff00ff00ff00ff00ff00ff');
        $this->inject($otherStrategy, 'randomGenerator', $mockRandomGenerator);

        $derivedKeyWithSalt = $otherStrategy->hashPassword('password');

        $this->assertTrue($strategy->validatePassword('password', $derivedKeyWithSalt), 'Hashing strategy should validate password with different cost');
    }

    /**
     * @test
     */
    public function validatePasswordWithInvalidHashFails()
    {
        $strategy = new \TYPO3\Flow\Security\Cryptography\BCryptHashingStrategy(10);

        $this->assertFalse($strategy->validatePassword('password', ''));
        $this->assertFalse($strategy->validatePassword('password', '$1$abc'));
        $this->assertFalse($strategy->validatePassword('password', '$2x$01$012345678901234567890123456789'));
    }
}
