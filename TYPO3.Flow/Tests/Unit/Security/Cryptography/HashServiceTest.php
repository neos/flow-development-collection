<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Cryptography;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Hash Service
 *
 * @version $Id: RSAWalletServicePHPTest.php 2813 2009-07-16 14:02:34Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class HashServiceTest extends \F3\Testing\BaseTestCase {

	protected $hashService;

	public function setUp() {
		$this->hashService = new \F3\FLOW3\Security\Cryptography\HashService();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst
	 */
	public function generateHashReturnsHashStringIfStringIsGiven() {
		$hash = $this->hashService->generateHash('asdf');
		$this->assertTrue(is_string($hash));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst
	 */
	public function generateHashReturnsHashStringWhichContainsSomeSalt() {
		$hash = $this->hashService->generateHash('asdf');
		$this->assertNotEquals(sha1('asdf'), $hash);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst
	 */
	public function generateHashReturnsDifferentHashStringsForDifferentInputStrings() {
		$hash1 = $this->hashService->generateHash('asdf');
		$hash2 = $this->hashService->generateHash('blubb');
		$this->assertNotEquals($hash1, $hash2);
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\Security\Exception\InvalidArgumentForHashGeneration
	 * @author Sebastian Kurfürst
	 */
	public function generateHashThrowsExceptionIfNoStringGiven() {
		$hash = $this->hashService->generateHash(NULL);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function generatedHashCanBeValidatedAgain() {
		$string = 'asdf';
		$hash = $this->hashService->generateHash($string);
		$this->assertTrue($this->hashService->validateHash($string, $hash));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function generatedHashWillNotBeValidatedIfHashHasBeenChanged() {
		$string = 'asdf';
		$hash = 'myhash';
		$this->assertFalse($this->hashService->validateHash($string, $hash));
	}
}
?>