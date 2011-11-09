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
 * Testcase for the Hash Service
 *
 */
class HashServiceTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Security\Cryptography\HashService
	 */
	protected $hashService;

	/**
	 * Set up test dependencies
	 *
	 * @return void
	 */
	public function setUp() {
		$this->hashService = new \TYPO3\FLOW3\Security\Cryptography\HashService();
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
	 * @expectedException TYPO3\FLOW3\Security\Exception\InvalidArgumentForHashGenerationException
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
	public function generatedHashReturnsAHashOf40Characters() {
		$hash = $this->hashService->generateHmac('asdf');
		$this->assertSame(40, strlen($hash));
	}

	/**
	 * @test
	 * @expectedException TYPO3\FLOW3\Security\Exception\InvalidArgumentForHashGenerationException
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
	 * @expectedException TYPO3\FLOW3\Security\Exception\InvalidArgumentForHashGenerationException
	 */
	public function validateAndStripHmacThrowsExceptionIfNoStringGiven() {
		$this->hashService->validateAndStripHmac(NULL);
	}

	/**
	 * @test
	 * @expectedException TYPO3\FLOW3\Security\Exception\InvalidArgumentForHashGenerationException
	 */
	public function validateAndStripHmacThrowsExceptionIfGivenStringIsTooShort() {
		$this->hashService->validateAndStripHmac('string with less than 40 characters');
	}

	/**
	 * @test
	 * @expectedException TYPO3\FLOW3\Security\Exception\InvalidHashException
	 */
	public function validateAndStripHmacThrowsExceptionIfGivenStringHasNoHashAppended() {
		$this->hashService->validateAndStripHmac('string with exactly a length 40 of chars');
	}

	/**
	 * @test
	 * @expectedException TYPO3\FLOW3\Security\Exception\InvalidHashException
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