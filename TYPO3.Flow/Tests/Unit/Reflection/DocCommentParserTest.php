<?php
namespace TYPO3\Flow\Tests\Unit\Reflection;

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
 * Testcase for DocCommentParser
 */
class DocCommentParserTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function descriptionWithOneLineIsParsedCorrectly() {
		$parser = new \TYPO3\Flow\Reflection\DocCommentParser();
		$parser->parseDocComment('/**' . chr(10) . ' * Testcase for DocCommentParser' . chr(10) . ' */');
		$this->assertEquals('Testcase for DocCommentParser', $parser->getDescription());
	}

	/**
	 * @test
	 */
	public function eolCharacterCanBeNewlineOrCarriageReturn() {
		$parser = new \TYPO3\Flow\Reflection\DocCommentParser();
		$parser->parseDocComment('/**' . chr(10) . ' * @var $foo integer' . chr(13) . chr(10) . ' * @var $bar string' . chr(10) . ' */');
		$this->assertEquals(array('$foo integer', '$bar string'), $parser->getTagValues('var'));
	}

}
