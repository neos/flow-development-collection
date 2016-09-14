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

use TYPO3\Flow\Cache\Backend\TransientMemoryBackend;
use TYPO3\Flow\Cache\Frontend\StringFrontend;
use TYPO3\Flow\Core\ApplicationContext;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Security\Cryptography\HashService;
use TYPO3\Flow\Security\Cryptography\PasswordHashingStrategyInterface;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test case for the Hash Service
 */
class HashServiceTest extends UnitTestCase
{
    /**
     * @var HashService
     */
    protected $hashService;

    /**
     * @var StringFrontend
     */
    protected $cache;

    /**
     * Set up test dependencies
     *
     * @return void
     */
    public function setUp()
    {
        $this->cache = new StringFrontend('TestCache', new TransientMemoryBackend(new ApplicationContext('Testing')));
        $this->cache->initializeObject();

        $this->hashService = new HashService();
        $this->inject($this->hashService, 'cache', $this->cache);
    }

    /**
     * @test
     */
    public function generateHmacReturnsHashStringIfStringIsGiven()
    {
        $hash = $this->hashService->generateHmac('asdf');
        $this->assertTrue(is_string($hash));
    }

    /**
     * @test
     */
    public function generateHmacReturnsHashStringWhichContainsSomeSalt()
    {
        $hash = $this->hashService->generateHmac('asdf');
        $this->assertNotEquals(sha1('asdf'), $hash);
    }

    /**
     * @test
     */
    public function generateHmacReturnsDifferentHashStringsForDifferentInputStrings()
    {
        $hash1 = $this->hashService->generateHmac('asdf');
        $hash2 = $this->hashService->generateHmac('blubb');
        $this->assertNotEquals($hash1, $hash2);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException
     */
    public function generateHmacThrowsExceptionIfNoStringGiven()
    {
        $this->hashService->generateHmac(null);
    }

    /**
     * @test
     */
    public function generatedHashCanBeValidatedAgain()
    {
        $string = 'asdf';
        $hash = $this->hashService->generateHmac($string);
        $this->assertTrue($this->hashService->validateHmac($string, $hash));
    }

    /**
     * @test
     */
    public function generatedHashWillNotBeValidatedIfHashHasBeenChanged()
    {
        $string = 'asdf';
        $hash = 'myhash';
        $this->assertFalse($this->hashService->validateHmac($string, $hash));
    }

    /**
     * @test
     */
    public function hashPasswordWithoutStrategyIdentifierUsesConfiguredDefaultStrategy()
    {
        $settings = [
            'security' => [
                'cryptography' => [
                    'hashingStrategies' => [
                        'default' => 'TestStrategy',
                        'fallback' => 'LegacyStrategy',
                        'TestStrategy' => 'TYPO3\Flow\Test\TestStrategy',
                        'LegacyStrategy' => 'TYPO3\Flow\Test\LegacyStrategy'
                    ]
                ]
            ]
        ];
        $this->hashService->injectSettings($settings);

        $mockStrategy = $this->createMock(PasswordHashingStrategyInterface::class);
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        ObjectAccess::setProperty($this->hashService, 'objectManager', $mockObjectManager, true);

        $mockObjectManager->expects($this->atLeastOnce())->method('get')->with('TYPO3\Flow\Test\TestStrategy')->will($this->returnValue($mockStrategy));
        $mockStrategy->expects($this->atLeastOnce())->method('hashPassword')->will($this->returnValue('---hashed-password---'));

        $this->hashService->hashPassword('myTestPassword');
    }

    /**
     * @test
     */
    public function validatePasswordWithoutStrategyIdentifierAndConfiguredFallbackUsesFallbackStrategy()
    {
        $settings = [
            'security' => [
                'cryptography' => [
                    'hashingStrategies' => [
                        'default' => 'TestStrategy',
                        'fallback' => 'LegacyStrategy',
                        'TestStrategy' => 'TYPO3\Flow\Test\TestStrategy',
                        'LegacyStrategy' => 'TYPO3\Flow\Test\LegacyStrategy'
                    ]
                ]
            ]
        ];
        $this->hashService->injectSettings($settings);

        $mockStrategy = $this->createMock(PasswordHashingStrategyInterface::class);
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        ObjectAccess::setProperty($this->hashService, 'objectManager', $mockObjectManager, true);

        $mockObjectManager->expects($this->atLeastOnce())->method('get')->with('TYPO3\Flow\Test\LegacyStrategy')->will($this->returnValue($mockStrategy));
        $mockStrategy->expects($this->atLeastOnce())->method('validatePassword')->will($this->returnValue(true));

        $this->hashService->validatePassword('myTestPassword', '---hashed-password---');
    }

    /**
     * @test
     */
    public function hashPasswordWillIncludeStrategyIdentifierInHashedPassword()
    {
        $settings = [
            'security' => [
                'cryptography' => [
                    'hashingStrategies' => [
                        'TestStrategy' => 'TYPO3\Flow\Test\TestStrategy'
                    ]
                ]
            ]
        ];
        $this->hashService->injectSettings($settings);

        $mockStrategy = $this->createMock(PasswordHashingStrategyInterface::class);
        $mockStrategy->expects($this->any())->method('hashPassword')->will($this->returnValue('---hashed-password---'));
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockStrategy));
        ObjectAccess::setProperty($this->hashService, 'objectManager', $mockObjectManager, true);

        $result = $this->hashService->hashPassword('myTestPassword', 'TestStrategy');
        $this->assertEquals('TestStrategy=>---hashed-password---', $result);
    }

    /**
     * @test
     */
    public function validatePasswordWillUseStrategyIdentifierFromHashedPassword()
    {
        $settings = [
            'security' => [
                'cryptography' => [
                    'hashingStrategies' => [
                        'TestStrategy' => 'TYPO3\Flow\Test\TestStrategy'
                    ]
                ]
            ]
        ];
        $this->hashService->injectSettings($settings);

        $mockStrategy = $this->createMock(PasswordHashingStrategyInterface::class);
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockStrategy));
        ObjectAccess::setProperty($this->hashService, 'objectManager', $mockObjectManager, true);

        $mockStrategy->expects($this->atLeastOnce())->method('validatePassword')->with('myTestPassword', '---hashed-password---')->will($this->returnValue(true));

        $result = $this->hashService->validatePassword('myTestPassword', 'TestStrategy=>---hashed-password---');
        $this->assertEquals(true, $result);
    }

    /**
     * @test
     */
    public function generatedHashReturnsAHashOf40Characters()
    {
        $hash = $this->hashService->generateHmac('asdf');
        $this->assertSame(40, strlen($hash));
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException
     */
    public function appendHmacThrowsExceptionIfNoStringGiven()
    {
        $this->hashService->appendHmac(null);
    }

    /**
     * @test
     */
    public function appendHmacAppendsHmacToGivenString()
    {
        $string = 'This is some arbitrary string ';
        $hashedString = $this->hashService->appendHmac($string);
        $this->assertSame($string, substr($hashedString, 0, -40));
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException
     */
    public function validateAndStripHmacThrowsExceptionIfNoStringGiven()
    {
        $this->hashService->validateAndStripHmac(null);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException
     */
    public function validateAndStripHmacThrowsExceptionIfGivenStringIsTooShort()
    {
        $this->hashService->validateAndStripHmac('string with less than 40 characters');
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Security\Exception\InvalidHashException
     */
    public function validateAndStripHmacThrowsExceptionIfGivenStringHasNoHashAppended()
    {
        $this->hashService->validateAndStripHmac('string with exactly a length 40 of chars');
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Security\Exception\InvalidHashException
     */
    public function validateAndStripHmacThrowsExceptionIfTheAppendedHashIsInvalid()
    {
        $this->hashService->validateAndStripHmac('some Stringac43682075d36592d4cb320e69ff0aa515886eab');
    }

    /**
     * @test
     */
    public function validateAndStripHmacReturnsTheStringWithoutHmac()
    {
        $string = ' Some arbitrary string with special characters: öäüß!"§$ ';
        $hashedString = $this->hashService->appendHmac($string);
        $actualResult = $this->hashService->validateAndStripHmac($hashedString);
        $this->assertSame($string, $actualResult);
    }
}
