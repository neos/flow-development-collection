<?php
namespace Neos\Flow\Tests\Unit\Security\Cryptography;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Security\Cryptography\BCryptHashingStrategy;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the BCryptHashingStrategy
 */
class BCryptHashingStrategyTest extends UnitTestCase
{
    /**
     * Test the implementation using the sample hashes shown on http://php.net/crypt
     * @test
     */
    public function systemSupportsBlowfishCryptMethod()
    {
        self::assertTrue(\CRYPT_BLOWFISH === 1);

        $cryptResult = crypt('rasmuslerdorf', '$2a$07$usesomesillystringforsalt$');
        self::assertEquals('$2a$07$usesomesillystringfore2uDLvp1Ii2e./U9C8sBjqp8I90dH6hi', $cryptResult);
    }

    /**
     * @test
     */
    public function hashPasswordWithMatchingPasswordAndParametersSucceeds()
    {
        $strategy = new BCryptHashingStrategy(10);
        $derivedKeyWithSalt = $strategy->hashPassword('password');

        self::assertTrue($strategy->validatePassword('password', $derivedKeyWithSalt));
        self::assertFalse($strategy->validatePassword('pass', $derivedKeyWithSalt));
    }

    /**
     * @test
     */
    public function hashAndValidatePasswordWithNotMatchingPasswordFails()
    {
        $strategy = new BCryptHashingStrategy(10);
        $derivedKeyWithSalt = $strategy->hashPassword('password');

        self::assertFalse($strategy->validatePassword('pass', $derivedKeyWithSalt), 'Different password should not match');
    }

    /**
     * @test
     */
    public function hashAndValidatePasswordWithDifferentCostsMatch()
    {
        $strategy = new BCryptHashingStrategy(10);

        $otherStrategy = new BCryptHashingStrategy(6);
        $derivedKeyWithSalt = $otherStrategy->hashPassword('password');

        self::assertTrue($strategy->validatePassword('password', $derivedKeyWithSalt), 'Hashing strategy should validate password with different cost');
    }

    /**
     * @test
     */
    public function validatePasswordWithInvalidHashFails()
    {
        $strategy = new BCryptHashingStrategy(10);

        self::assertFalse($strategy->validatePassword('password', ''));
        self::assertFalse($strategy->validatePassword('password', '$1$abc'));
        self::assertFalse($strategy->validatePassword('password', '$2x$01$012345678901234567890123456789'));
    }
}
