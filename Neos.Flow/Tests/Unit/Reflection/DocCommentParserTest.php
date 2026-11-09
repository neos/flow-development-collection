<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for DocCommentParser
 */
final class DocCommentParserTest extends UnitTestCase
{
    #[Test]
    public function descriptionWithOneLineIsParsedCorrectly()
    {
        $parser = new DocCommentParser();
        $parser->parseDocComment('/**' . chr(10) . ' * Testcase for DocCommentParser' . chr(10) . ' */');
        self::assertEquals('Testcase for DocCommentParser', $parser->getDescription());
    }

    #[Test]
    public function eolCharacterCanBeNewlineOrCarriageReturn()
    {
        $parser = new DocCommentParser();
        $parser->parseDocComment('/**' . chr(10) . ' * @var $foo integer' . chr(13) . chr(10) . ' * @var $bar string' . chr(10) . ' */');
        self::assertEquals(['$foo integer', '$bar string'], $parser->getTagValues('var'));
    }

    #[Test]
    public function singleLineTagIsParsedCorrectly()
    {
        $parser = new DocCommentParser();
        $parser->parseDocComment('/** @return Foo[] */');
        $this->assertEquals([ 'Foo[]' ], $parser->getTagValues('return'));
    }
    #[Test]
    public function singleLineDescriptionIsParsedCorrectly()
    {
        $parser = new DocCommentParser();
        $parser->parseDocComment('/** Description goes here */');

        $this->assertEquals('Description goes here', $parser->getDescription());
    }

    #[Test]
    public function annotationAsTagValue()
    {
        $parser = new DocCommentParser();
        $parser->parseDocComment('/** @Flow\SkipCsrfProtection */');

        // FIXME this automagic handling of annotations is wrong and causes bugs, this tests is just to document the current state, the concept of tags is overhauled and should be deprecated in favour of annotations and attributes
        $this->assertEquals(['skipcsrfprotection' => []], $parser->getTagsValues());
    }

    #[Test]
    public function annotationAsTagValueOmitsNamespace()
    {
        $parser = new DocCommentParser();
        $parser->parseDocComment('/** @SomeOtherNameSpace\SkipCsrfProtection */');

        // FIXME this automagic handling of annotations is wrong and causes bugs, this tests is just to document the current state, the concept of tags is overhauled and should be deprecated in favour of annotations and attributes
        $this->assertEquals(['skipcsrfprotection' => []], $parser->getTagsValues());
    }

    #[Test]
    public function annotationAsTagValueWithComments()
    {
        $parser = new DocCommentParser();
        $parser->parseDocComment('/** @Flow\SkipCsrfProtection Some comment in this line */');

        // FIXME this automagic handling of annotations is wrong and causes bugs, this tests is just to document the current state, the concept of tags is overhauled and should be deprecated in favour of annotations and attributes
        $this->assertEquals(['flow\skipcsrfprotection' => [
            'Some comment in this line'
        ]], $parser->getTagsValues());
    }
}
