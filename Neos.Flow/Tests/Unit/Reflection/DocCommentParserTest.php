<?php
namespace Neos\Flow\Tests\Unit\Reflection;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Reflection\DocCommentParser;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for DocCommentParser
 */
class DocCommentParserTest extends UnitTestCase
{
    /**
     * @test
     */
    public function descriptionWithOneLineIsParsedCorrectly()
    {
        $parser = new DocCommentParser();
        $parser->parseDocComment('/**' . chr(10) . ' * Testcase for DocCommentParser' . chr(10) . ' */');
        $this->assertEquals('Testcase for DocCommentParser', $parser->getDescription());
    }

    /**
     * @test
     */
    public function eolCharacterCanBeNewlineOrCarriageReturn()
    {
        $parser = new DocCommentParser();
        $parser->parseDocComment('/**' . chr(10) . ' * @var $foo integer' . chr(13) . chr(10) . ' * @var $bar string' . chr(10) . ' */');
        $this->assertEquals(['$foo integer', '$bar string'], $parser->getTagValues('var'));
    }
}
