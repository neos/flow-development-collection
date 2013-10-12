<?php
namespace TYPO3\Kickstart\Tests\Unit\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Kickstart".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(__DIR__ . '/../../../Resources/Private/PHP/Sho_Inflect.php');

/**
 * Testcase for the Inflector
 *
 */
class InflectorTest extends \TYPO3\Flow\Tests\UnitTestCase {
	/**
	 * @test
	 */
	public function humanizeCamelCaseConvertsCamelCaseToSpacesAndUppercasesFirstWord() {
		$inflector = new \TYPO3\Kickstart\Utility\Inflector();
		$humanized = $inflector->humanizeCamelCase('BlogAuthor');
		$this->assertEquals('Blog author', $humanized);
	}

	/**
	 * @test
	 */
	public function pluralizePluralizesWords() {
		$inflector = new \TYPO3\Kickstart\Utility\Inflector();
		$this->assertEquals('boxes', $inflector->pluralize('box'));
		$this->assertEquals('foos', $inflector->pluralize('foo'));
	}
}
