<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Utility\Unicode;

/*                                                                        *
 * This script belongs to the FLOW3 package "PHP6".                       *
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
 * Testcase for the PHP6 Functions backport
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class FunctionsTest extends \F3\Testing\BaseTestCase {

	/**
	 * Checks if strtotitle() at least works with latin characters.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function strtotitleWorksWithLatinCharacters() {
		$testString = 'this Is - my TestString.';
		$this->assertEquals('This Is - My Teststring.', \F3\FLOW3\Utility\Unicode\Functions::strtotitle($testString), 'strtotitle() did not return the expected string.');
	}

	/**
	 * Checks if strtotitle() works with unicode strings
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function strtotitleWorksWithUnicodeStrings() {
		$testString = ' öl Ist nicht das GLEICHE wie øl.';
		$expectedString = ' Öl Ist Nicht Das Gleiche Wie Øl.';
		$this->assertEquals($expectedString, \F3\FLOW3\Utility\Unicode\Functions::strtotitle($testString), 'strtotitle() did not return the expected string for the unicode test.');
	}

	/**
	 * Checks if substr() basically works with latin characters.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function substrWorksWithLatinCharacters() {
		$testString = 'I say "hello world".';
		$this->assertEquals('hello world', \F3\FLOW3\Utility\Unicode\Functions::substr($testString, 7, 11), 'substr() with latin characters did not return the expected string.');
	}

	/**
	 * Checks if substr() can handle UTF8 strings
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function substrWorksWithUTF8Characters() {
		$testString = 'Kasper Skårhøj implemented most versions of TYPO3.';
		$this->assertEquals('Kasper Skårhøj', \F3\FLOW3\Utility\Unicode\Functions::substr($testString, 0, 14), 'substr() with UTF8 characters did not return the expected string.');
	}

	/**
	 * Checks if substr() can handle UTF8 strings, specifying no length
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function substrWorksWithUTF8CharactersSpecifyingNoLength() {
		$testString = 'Kasper Skårhøj implemented most versions of TYPO3.';
		$this->assertEquals('implemented most versions of TYPO3.', \F3\FLOW3\Utility\Unicode\Functions::substr($testString, 15), 'substr() with UTF8 characters did not return the expected string after specifying no length.');
	}

	/**
	 * Checks if our version of \F3\FLOW3\Utility\Unicode\Functions::strtoupper basically works
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function strtoupperWorksWithLatinCharacters() {
		$testString = 'typo3';
		$this->assertEquals('TYPO3', \F3\FLOW3\Utility\Unicode\Functions::strtoupper($testString), 'F3\PHP6\Functions::strtoupper() with latin characters didn\'t work out.');
	}

	/**
	 * Checks if our version of \F3\FLOW3\Utility\Unicode\Functions::strtoupper can at least handle some common special chars
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function strtoupperWorksWithCertainSpecialChars() {
		$testString = 'Here are some characters: äöüÄÖÜßéèêåÅøØæÆœŒ ...';
		$expectedResult = 'HERE ARE SOME CHARACTERS: ÄÖÜÄÖÜSSÉÈÊÅÅØØÆÆŒŒ ...';
		$result = \F3\FLOW3\Utility\Unicode\Functions::strtoupper($testString);
		$this->assertEquals($expectedResult, $result, 'F3\PHP6\Functions::strtoupper() could not convert our selection of special characters.');
	}

	/**
	 * Checks if our version of strtolower basically works
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function strtolowerWorksWithLatinCharacters() {
		$testString = 'TYPO3';
		$this->assertEquals('typo3', \F3\FLOW3\Utility\Unicode\Functions::strtolower($testString), 'strtolower() with latin characters didn\'t work out.');
	}

	/**
	 * Checks if our version of strtolower can at least handle some common special chars
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function strtolowerWorksWithCertainSpecialChars() {
		$testString = 'HERE ARE SOME CHARACTERS: ÄÖÜÄÖÜßÉÈÊÅÅØØÆÆŒŒ ...';
		$expectedResult = 'here are some characters: äöüäöüßéèêååøøææœœ ...';
		$result = \F3\FLOW3\Utility\Unicode\Functions::strtolower($testString);
		$this->assertEquals($expectedResult, $result, 'strtolower() could not convert our selection of special characters.');
	}

	/**
	 * Checks if our version of strlen can handle some regular latin characters.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function strlenWorksWithLatinCharacters() {
		$testString = 'Feugiat tincidunt duo id, 23 quam delenit vocibus nam eu';
		$this->assertEquals(56, \F3\FLOW3\Utility\Unicode\Functions::strlen($testString), 'strlen() did not return the correct string length for latin character string.');
	}

	/**
	 * Checks if our version of strlen can handle some common special chars
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function strlenWorksWithCertainSpecialChars() {
		$testString = 'here are some characters: äöüäöüßéèêååøøææœœ“” ...';
		$this->assertEquals(50, \F3\FLOW3\Utility\Unicode\Functions::strlen($testString), 'strlen() did not return the correct string length for unicode string.');
	}

	/**
	 * Checks if our version of ucfirst can handle some regular latin characters.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function ucfirstWorksWithLatinCharacters() {
		$testString = 'feugiat tincidunt duo id, 23 quam delenit vocibus nam eu';
		$expectedResult = 'Feugiat tincidunt duo id, 23 quam delenit vocibus nam eu';
		$this->assertEquals($expectedResult, \F3\FLOW3\Utility\Unicode\Functions::ucfirst($testString), 'ucfirst() did not return the correct string for latin string.');
	}

	/**
	 * Checks if our version of ucfirst can handle some common special chars.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function ucfirstWorksWithCertainSpecialChars() {
		$testString = 'äeugiat tincidunt duo id, 23 quam delenit vocibus nam eu';
		$expectedResult = 'Äeugiat tincidunt duo id, 23 quam delenit vocibus nam eu';
		$this->assertEquals($expectedResult, \F3\FLOW3\Utility\Unicode\Functions::ucfirst($testString), 'ucfirst() did not return the correct string for a umlaut.');

		$testString = 'åeugiat tincidunt duo id, 23 quam delenit vocibus nam eu';
		$expectedResult = 'Åeugiat tincidunt duo id, 23 quam delenit vocibus nam eu';
		$this->assertEquals($expectedResult, \F3\FLOW3\Utility\Unicode\Functions::ucfirst($testString), 'ucfirst() did not return the correct string for danish a.');
	}

	/**
	 * Checks if our version of lcfirst can handle some regular latin characters.
	 *
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function lcfirstWorksWithLatinCharacters() {
		$testString = 'FEUGIAT TINCIDUNT DUO ID, 23 QUAM DELENIT VOCIBUS NAM EU';
		$expectedResult = 'fEUGIAT TINCIDUNT DUO ID, 23 QUAM DELENIT VOCIBUS NAM EU';
		$this->assertEquals($expectedResult, \F3\FLOW3\Utility\Unicode\Functions::lcfirst($testString), 'lcfirst() did not return the correct string for latin string.');
	}

	/**
	 * Checks if our version of lcfirst can handle some common special chars.
	 *
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function lcfirstWorksWithCertainSpecialChars() {
		$testString = 'ÄEUGIAT TINCIDUNT DUO ID, 23 QUAM DELENIT VOCIBUS NAM EU';
		$expectedResult = 'äEUGIAT TINCIDUNT DUO ID, 23 QUAM DELENIT VOCIBUS NAM EU';
		$this->assertEquals($expectedResult, \F3\FLOW3\Utility\Unicode\Functions::lcfirst($testString), 'lcfirst() did not return the correct string for a umlaut.');

		$testString = 'ÅEUGIAT TINCIDUNT DUO ID, 23 QUAM DELENIT VOCIBUS NAM EU';
		$expectedResult = 'åEUGIAT TINCIDUNT DUO ID, 23 QUAM DELENIT VOCIBUS NAM EU';
		$this->assertEquals($expectedResult, \F3\FLOW3\Utility\Unicode\Functions::lcfirst($testString), 'lcfirst() did not return the correct string for danish a.');
	}

	/**
	 * Checks if our version of strpos can handle some regular latin characters.
	 *
	 * @test
	 * @author karsten Dambekalns <karsten@typo3.org>
	 */
	public function strposWorksWithLatinCharacters() {
		$testString = 'Feugiat tincidunt duo id, 23 quam delenit vocibus nam eu';
		$this->assertEquals(8, strpos($testString, 'tincidunt'), 'strpos() did not return the correct position for a latin character string.');
	}

	/**
	 * Checks if our version of strpos can handle some common special characters
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function strposWorksWithCertainSpecialChars() {
		$testString = 'Åeugiat tincidunt duo id, 23 quam delenit vocibus nam eu';
		$this->assertEquals(8, \F3\FLOW3\Utility\Unicode\Functions::strpos($testString, 'tincidunt'), 'strpos() did not return the correct positions for a unicode string.');
	}
}
?>