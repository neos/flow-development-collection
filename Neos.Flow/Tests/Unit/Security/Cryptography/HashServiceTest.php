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

use Neos\Cache\Backend\TransientMemoryBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Flow\Security\Cryptography\PasswordHashingStrategyInterface;
use Neos\Flow\Tests\Unit\Cryptography\Fixture\TestHashingStrategy;
use Neos\Flow\Tests\UnitTestCase;

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
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockObjectManager;

    /**
     * @var array
     */
    protected $mockSettings = [
        'security' => [
            'cryptography' => [
                'hashingStrategies' => [
                    'default' => 'TestStrategy',
                    'TestStrategy' => TestHashingStrategy::class,
                ]
            ]
        ]
    ];

    /**
     * Set up test dependencies
     *
     * @return void
     */
    public function setUp()
    {
        $this->cache = new StringFrontend('TestCache', new TransientMemoryBackend(new EnvironmentConfiguration('Hash Testing', '/some/path', PHP_MAXPATHLEN)));
        $this->cache->initializeObject();

        $this->mockObjectManager = $this->getMockBuilder(ObjectManagerInterface::class)->getMock();

        $this->hashService = new HashService();
        $this->inject($this->hashService, 'cache', $this->cache);
        $this->inject($this->hashService, 'objectManager', $this->mockObjectManager);
        $this->hashService->injectSettings($this->mockSettings);
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
     * @expectedException \Neos\Flow\Security\Exception\InvalidArgumentForHashGenerationException
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
        $mockStrategy = $this->createMock(PasswordHashingStrategyInterface::class);

        $this->mockObjectManager->expects($this->atLeastOnce())->method('get')->with(TestHashingStrategy::class)->will($this->returnValue($mockStrategy));
        $mockStrategy->expects($this->atLeastOnce())->method('hashPassword')->will($this->returnValue('---hashed-password---'));

        $this->hashService->hashPassword('myTestPassword');
    }

    /**
     * test
     */
    public function validatePasswordWithoutStrategyIdentifierUsesDefaultStrategy()
    {
        $mockStrategy = $this->createMock(PasswordHashingStrategyInterface::class);

        $this->mockObjectManager->expects($this->atLeastOnce())->method('get')->with(TestHashingStrategy::class)->will($this->returnValue($mockStrategy));
        $mockStrategy->expects($this->atLeastOnce())->method('validatePassword')->will($this->returnValue(true));

        $this->hashService->validatePassword('myTestPassword', '---hashed-password---');
    }

    /**
     * @test
     */
    public function hashPasswordWillIncludeStrategyIdentifierInHashedPassword()
    {
        $mockStrategy = $this->createMock(PasswordHashingStrategyInterface::class);
        $mockStrategy->expects($this->any())->method('hashPassword')->will($this->returnValue('---hashed-password---'));
        $this->mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockStrategy));

        $result = $this->hashService->hashPassword('myTestPassword', 'TestStrategy');
        $this->assertEquals('TestStrategy=>---hashed-password---', $result);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\MissingConfigurationException
     */
    public function hashPasswordThrowsExceptionIfTheGivenHashingStrategyIsNotConfigured()
    {
        $this->hashService->hashPassword('myTestPassword', 'nonExistingHashingStrategy');
    }


    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\MissingConfigurationException
     */
    public function hashPasswordThrowsExceptionIfNoDefaultHashingStrategyIsConfigured()
    {
        $mockSettings = [
            'security' => [
                'cryptography' => [
                    'hashingStrategies' => [
                        'TestStrategy' => TestHashingStrategy::class,
                    ]
                ]
            ]
        ];
        $this->hashService->injectSettings($mockSettings);
        $this->hashService->hashPassword('myTestPassword');
    }

    /**
     * @test
     */
    public function validatePasswordWillUseStrategyIdentifierFromHashedPassword()
    {
        $mockStrategy = $this->createMock(PasswordHashingStrategyInterface::class);
        $this->mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockStrategy));

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
     * @expectedException \Neos\Flow\Security\Exception\InvalidArgumentForHashGenerationException
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
     * @expectedException \Neos\Flow\Security\Exception\InvalidArgumentForHashGenerationException
     */
    public function validateAndStripHmacThrowsExceptionIfNoStringGiven()
    {
        $this->hashService->validateAndStripHmac(null);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\InvalidArgumentForHashGenerationException
     */
    public function validateAndStripHmacThrowsExceptionIfGivenStringIsTooShort()
    {
        $this->hashService->validateAndStripHmac('string with less than 40 characters');
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\InvalidHashException
     */
    public function validateAndStripHmacThrowsExceptionIfGivenStringHasNoHashAppended()
    {
        $this->hashService->validateAndStripHmac('string with exactly a length 40 of chars');
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\InvalidHashException
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
