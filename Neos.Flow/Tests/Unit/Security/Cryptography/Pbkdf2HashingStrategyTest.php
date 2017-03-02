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

use Neos\Flow\Security\Cryptography\Pbkdf2HashingStrategy;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the Pbkdf2HashingStrategy
 */
class Pbkdf2HashingStrategyTest extends UnitTestCase
{
    /**
     * @test
     */
    public function hashPasswordWithMatchingPasswordAndParametersSucceeds()
    {
        $strategy = new Pbkdf2HashingStrategy(8, 1000, 64, 'sha256');
        $derivedKeyWithSalt = $strategy->hashPassword('password', 'MyStaticSalt');

        $this->assertTrue($strategy->validatePassword('password', $derivedKeyWithSalt, 'MyStaticSalt'));
        $this->assertFalse($strategy->validatePassword('pass', $derivedKeyWithSalt, 'MyStaticSalt'));
        $this->assertFalse($strategy->validatePassword('password', $derivedKeyWithSalt, 'SomeSalt'));
    }

    /**
     * @test
     */
    public function hashAndValidatePasswordWithNotMatchingPasswordOrParametersFails()
    {
        $strategy = new Pbkdf2HashingStrategy(8, 1000, 64, 'sha256');
        $derivedKeyWithSalt = $strategy->hashPassword('password', 'MyStaticSalt');

        $this->assertFalse($strategy->validatePassword('pass', $derivedKeyWithSalt, 'MyStaticSalt'), 'Different password should not match');
        $this->assertFalse($strategy->validatePassword('password', $derivedKeyWithSalt, 'SomeSalt'), 'Different static salt should not match');

        $strategy = new Pbkdf2HashingStrategy(8, 99, 64, 'sha256');
        $this->assertFalse($strategy->validatePassword('password', $derivedKeyWithSalt, 'MyStaticSalt'), 'Different iteration should not match');
    }
}
