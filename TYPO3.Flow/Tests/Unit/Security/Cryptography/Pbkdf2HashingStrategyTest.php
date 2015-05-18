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
 * Testcase for the Pbkdf2HashingStrategy
 *
 */
class Pbkdf2HashingStrategyTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function hashPasswordWithMatchingPasswordAndParametersSucceeds()
    {
        $strategy = new \TYPO3\Flow\Security\Cryptography\Pbkdf2HashingStrategy(8, 1000, 64, 'sha256');

        $mockRandomGenerator = $this->getMock(Generator::class, array(), array(), '', false);
        $mockRandomGenerator->expects($this->any())->method('generateString')->willReturn('00ff00ff00ff00ff00ff00ff00ff00ff');
        $this->inject($strategy, 'randomGenerator', $mockRandomGenerator);

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
        $strategy = new \TYPO3\Flow\Security\Cryptography\Pbkdf2HashingStrategy(8, 1000, 64, 'sha256');

        $mockRandomGenerator = $this->getMock(Generator::class, array(), array(), '', false);
        $mockRandomGenerator->expects($this->any())->method('generateString')->willReturn('00ff00ff00ff00ff00ff00ff00ff00ff');
        $this->inject($strategy, 'randomGenerator', $mockRandomGenerator);

        $derivedKeyWithSalt = $strategy->hashPassword('password', 'MyStaticSalt');

        $this->assertFalse($strategy->validatePassword('pass', $derivedKeyWithSalt, 'MyStaticSalt'), 'Different password should not match');
        $this->assertFalse($strategy->validatePassword('password', $derivedKeyWithSalt, 'SomeSalt'), 'Different static salt should not match');

        $strategy = new \TYPO3\Flow\Security\Cryptography\Pbkdf2HashingStrategy(8, 99, 64, 'sha256');
        $this->assertFalse($strategy->validatePassword('password', $derivedKeyWithSalt, 'MyStaticSalt'), 'Different iteration should not match');
    }
}
