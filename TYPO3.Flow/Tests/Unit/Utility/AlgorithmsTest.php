<?php
namespace TYPO3\Flow\Tests\Unit\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Utility\Algorithms;

/**
 * Testcase for the Utility Algorithms class
 *
 */
class AlgorithmsTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function generateUUIDGeneratesUuidLikeString() {
		$this->assertRegExp('/^[[:xdigit:]]{8}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{12}$/', Algorithms::generateUUID());
	}

	/**
	 * @test
	 */
	public function generateRandomBytesGeneratesRandomBytes() {
		$this->assertEquals(20, strlen(Algorithms::generateRandomBytes(20)));
	}

	/**
	 * @test
	 */
	public function generateRandomTokenGeneratesRandomToken() {
		$this->assertRegExp('/^[[:xdigit:]]{64}$/', Algorithms::generateRandomToken(32));
	}

	/**
	 * @test
	 */
	public function generateRandomStringGeneratesAlnumCharactersPerDefault() {
		$this->assertRegExp('/^[a-z0-9]{64}$/i', Algorithms::generateRandomString(64));
	}

	/**
	 * signature: $regularExpression, $charactersClass
	 */
	public function randomStringCharactersDataProvider() {
		return array(
			array('/^[#~+]{64}$/', '#~+'),
			array('/^[a-f2-4%]{64}$/', 'abcdef234%'),
		);
	}

	/**
	 * @test
	 * @dataProvider randomStringCharactersDataProvider
	 */
	public function generateRandomStringGeneratesOnlyDefinedCharactersRange($regularExpression, $charactersClass) {
		$this->assertRegExp($regularExpression, Algorithms::generateRandomString(64, $charactersClass));
	}
}
?>