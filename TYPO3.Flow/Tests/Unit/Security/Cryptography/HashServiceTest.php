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
		$hash = $this->hashService->generateHmac(NULL);
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
}
?>