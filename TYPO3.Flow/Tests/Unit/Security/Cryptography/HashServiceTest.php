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
 * Testcase for the Hash Service
 *
 */
class HashServiceTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Security\Cryptography\HashService
	 */
	protected $hashService;

	/**
	 * Set up test dependencies
	 *
	 * @return void
	 */
	public function setUp() {
		$this->hashService = new \TYPO3\Flow\Security\Cryptography\HashService();
	}

	/**
	 * @test
	 */
	public function generateHmacReturnsHashStringIfStringIsGiven() {
		$hash = $this->hashService->generateHmac('asdf');
		$this->assertTrue(is_string($hash));
	}

	/**
	 * @test
	 */
	public function generateHmacReturnsHashStringWhichContainsSomeSalt() {
		$hash = $this->hashService->generateHmac('asdf');
		$this->assertNotEquals(sha1('asdf'), $hash);
	}

	/**
	 * @test
	 */
	public function generateHmacReturnsDifferentHashStringsForDifferentInputStrings() {
		$hash1 = $this->hashService->generateHmac('asdf');
		$hash2 = $this->hashService->generateHmac('blubb');
		$this->assertNotEquals($hash1, $hash2);
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException
	 */
	public function generateHmacThrowsExceptionIfNoStringGiven() {
		$this->hashService->generateHmac(NULL);
	}

	/**
	 * @test
	 */
	public function generatedHashCanBeValidatedAgain() {
		$string = 'asdf';
		$hash = $this->hashService->generateHmac($string);
		$this->assertTrue($this->hashService->validateHmac($string, $hash));
	}

	/**
	 * @test
	 */
	public function generatedHashWillNotBeValidatedIfHashHasBeenChanged() {
		$string = 'asdf';
		$hash = 'myhash';
		$this->assertFalse($this->hashService->validateHmac($string, $hash));
	}

	/**
	 * @test
	 */
	public function hashPasswordWithoutStrategyIdentifierUsesConfiguredDefaultStrategy() {
		$settings = array(
			'security' => array(
				'cryptography' => array(
					'hashingStrategies' => array(
						'default' => 'TestStrategy',
						'fallback' => 'LegacyStrategy',
						'TestStrategy' => 'TYPO3\Flow\Test\TestStrategy',
						'LegacyStrategy' => 'TYPO3\Flow\Test\LegacyStrategy'
					)
				)
			)
		);
		$this->hashService->injectSettings($settings);

		$mockStrategy = $this->getMock('TYPO3\Flow\Security\Cryptography\PasswordHashingStrategyInterface');
		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($this->hashService, 'objectManager', $mockObjectManager, TRUE);

		$mockObjectManager->expects($this->atLeastOnce())->method('get')->with('TYPO3\Flow\Test\TestStrategy')->will($this->returnValue($mockStrategy));
		$mockStrategy->expects($this->atLeastOnce())->method('hashPassword')->will($this->returnValue('---hashed-password---'));

		$this->hashService->hashPassword('myTestPassword');
	}

	/**
	 * @test
	 */
	public function validatePasswordWithoutStrategyIdentifierAndConfiguredFallbackUsesFallbackStrategy() {
		$settings = array(
			'security' => array(
				'cryptography' => array(
					'hashingStrategies' => array(
						'default' => 'TestStrategy',
						'fallback' => 'LegacyStrategy',
						'TestStrategy' => 'TYPO3\Flow\Test\TestStrategy',
						'LegacyStrategy' => 'TYPO3\Flow\Test\LegacyStrategy'
					)
				)
			)
		);
		$this->hashService->injectSettings($settings);

		$mockStrategy = $this->getMock('TYPO3\Flow\Security\Cryptography\PasswordHashingStrategyInterface');
		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($this->hashService, 'objectManager', $mockObjectManager, TRUE);

		$mockObjectManager->expects($this->atLeastOnce())->method('get')->with('TYPO3\Flow\Test\LegacyStrategy')->will($this->returnValue($mockStrategy));
		$mockStrategy->expects($this->atLeastOnce())->method('validatePassword')->will($this->returnValue(TRUE));

		$this->hashService->validatePassword('myTestPassword', '---hashed-password---');
	}

	/**
	 * @test
	 */
	public function hashPasswordWillIncludeStrategyIdentifierInHashedPassword() {
		$settings = array(
			'security' => array(
				'cryptography' => array(
					'hashingStrategies' => array(
						'TestStrategy' => 'TYPO3\Flow\Test\TestStrategy'
					)
				)
			)
		);
		$this->hashService->injectSettings($settings);

		$mockStrategy = $this->getMock('TYPO3\Flow\Security\Cryptography\PasswordHashingStrategyInterface');
		$mockStrategy->expects($this->any())->method('hashPassword')->will($this->returnValue('---hashed-password---'));
		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockStrategy));
		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($this->hashService, 'objectManager', $mockObjectManager, TRUE);

		$result = $this->hashService->hashPassword('myTestPassword', 'TestStrategy');
		$this->assertEquals('TestStrategy=>---hashed-password---', $result);
	}

	/**
	 * @test
	 */
	public function validatePasswordWillUseStrategyIdentifierFromHashedPassword() {
		$settings = array(
			'security' => array(
				'cryptography' => array(
					'hashingStrategies' => array(
						'TestStrategy' => 'TYPO3\Flow\Test\TestStrategy'
					)
				)
			)
		);
		$this->hashService->injectSettings($settings);

		$mockStrategy = $this->getMock('TYPO3\Flow\Security\Cryptography\PasswordHashingStrategyInterface');
		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockStrategy));
		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($this->hashService, 'objectManager', $mockObjectManager, TRUE);

		$mockStrategy->expects($this->atLeastOnce())->method('validatePassword')->with('myTestPassword', '---hashed-password---')->will($this->returnValue(TRUE));

		$result = $this->hashService->validatePassword('myTestPassword', 'TestStrategy=>---hashed-password---');
		$this->assertEquals(TRUE, $result);
	}

	/**
	 * @test
	 */
	public function generatedHashReturnsAHashOf40Characters() {
		$hash = $this->hashService->generateHmac('asdf');
		$this->assertSame(40, strlen($hash));
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException
	 */
	public function appendHmacThrowsExceptionIfNoStringGiven() {
		$this->hashService->appendHmac(NULL);
	}

	/**
	 * @test
	 */
	public function appendHmacAppendsHmacToGivenString() {
		$string = 'This is some arbitrary string ';
		$hashedString = $this->hashService->appendHmac($string);
		$this->assertSame($string, substr($hashedString, 0, -40));
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException
	 */
	public function validateAndStripHmacThrowsExceptionIfNoStringGiven() {
		$this->hashService->validateAndStripHmac(NULL);
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException
	 */
	public function validateAndStripHmacThrowsExceptionIfGivenStringIsTooShort() {
		$this->hashService->validateAndStripHmac('string with less than 40 characters');
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Security\Exception\InvalidHashException
	 */
	public function validateAndStripHmacThrowsExceptionIfGivenStringHasNoHashAppended() {
		$this->hashService->validateAndStripHmac('string with exactly a length 40 of chars');
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Security\Exception\InvalidHashException
	 */
	public function validateAndStripHmacThrowsExceptionIfTheAppendedHashIsInvalid() {
		$this->hashService->validateAndStripHmac('some Stringac43682075d36592d4cb320e69ff0aa515886eab');
	}

	/**
	 * @test
	 */
	public function validateAndStripHmacReturnsTheStringWithoutHmac() {
		$string = ' Some arbitrary string with special characters: öäüß!"§$ ';
		$hashedString = $this->hashService->appendHmac($string);
		$actualResult = $this->hashService->validateAndStripHmac($hashedString);
		$this->assertSame($string, $actualResult);
	}
}
?>
