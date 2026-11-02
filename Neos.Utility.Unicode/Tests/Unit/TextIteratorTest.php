<?php

declare(strict_types=1);

namespace Neos\Utility\Unicode\Tests\Unit;

/*
 * This file is part of the Neos.Utility.Unicode package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\Unicode;
use Neos\Utility\Unicode\Exception;
use Neos\Utility\Unicode\TextIterator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Testcase for the TextIterator port
 */
final class TextIteratorTest extends TestCase
{
    /**
     * Checks if a new instance with the default iterator type can be created
     */
    #[Test]
    public function canCreateIteratorOfDefaultType()
    {
        $iterator = new TextIterator('Some string');
        self::assertInstanceOf(TextIterator::class, $iterator);
    }

    /**
     * Checks if a new instance iterating over characters can be created
     */
    #[Test]
    public function instantiatingCharacterIteratorWorks()
    {
        $characterIterator = new TextIterator('Some string', TextIterator::CHARACTER);
        self::assertInstanceOf(TextIterator::class, $characterIterator);
    }

    /**
     * Checks if a new instance iterating over words can be created
     */
    #[Test]
    public function instantiatingWordIteratorWorks()
    {
        $wordIterator = new TextIterator('Some string', TextIterator::WORD);
        self::assertInstanceOf(TextIterator::class, $wordIterator);
    }

    /**
     * Checks if a new instance iterating over sentences can be created
     */
    #[Test]
    public function instantiatingSentenceIteratorWorks()
    {
        $sentenceIterator = new TextIterator('Some string', TextIterator::SENTENCE);
        self::assertInstanceOf(TextIterator::class, $sentenceIterator);
    }

    /**
     * Checks if a new instance iterating over lines can be created
     */
    #[Test]
    public function instantiatingLineIteratorWorks()
    {
        $lineIterator = new TextIterator('Some string', TextIterator::LINE);
        self::assertInstanceOf(TextIterator::class, $lineIterator);
    }

    /**
     * Checks if the constructor rejects an invalid iterator type
     */
    #[Test]
    public function instantiatingIteratorWithInvalidTypeThrowsError()
    {
        try {
            new TextIterator('Some string', 948);
            $this->fail('Constructor did not reject invalid TextIterator type.');
        } catch (Exception $exception) {
            self::assertStringContainsString('Invalid iterator type in TextIterator constructor', $exception->getMessage(), 'Wrong error message.');
        }
    }

    /**
     * Checks if character iteration basically works
     */
    #[Test]
    public function characterIterationBasicallyWorks()
    {
        $iterator = new TextIterator('This is a test string. Let\'s iterate it by character...', TextIterator::CHARACTER);
        $iterator->rewind();
        $result = '';
        foreach ($iterator as $currentCharacter) {
            $result .= $currentCharacter;
        }
        self::assertSame('This is a test string. Let\'s iterate it by character...', $result, 'Character iteration didn\'t return the right values.');
    }

    /**
     * Checks if word iteration basically works
     */
    #[Test]
    public function wordIterationBasicallyWorks()
    {
        $iterator = new TextIterator('This is a test string. Let\'s iterate it by word...', TextIterator::WORD);
        $iterator->rewind();
        $result = '';
        foreach ($iterator as $currentWord) {
            $result .= $currentWord;
        }
        self::assertSame('This is a test string. Let\'s iterate it by word...', $result, 'Word iteration didn\'t return the right values.');
    }

    /**
     * Checks if sentence iteration basically works
     */
    #[Test]
    public function sentenceIterationBasicallyWorks()
    {
        $iterator = new TextIterator('This is a test string. Let\'s iterate it by sentence...', TextIterator::SENTENCE);
        $iterator->rewind();
        $result = '';
        foreach ($iterator as $currentSentence) {
            $result .= $currentSentence;
        }
        self::assertSame('This is a test string. Let\'s iterate it by sentence...', $result, 'Sentence iteration didn\'t return the right values.');
    }

    /**
     * Checks if line iteration basically works
     */
    #[Test]
    public function lineIterationBasicallyWorks()
    {
        $iterator = new TextIterator("This is a test string. \nLet's iterate \nit by line...", TextIterator::LINE);
        $iterator->rewind();
        $result = '';
        foreach ($iterator as $currentLine) {
            $result .= $currentLine;
        }
        self::assertSame("This is a test string. \nLet's iterate \nit by line...", $result, 'Line iteration didn\'t return the right values.');
    }

    /**
     * Checks if the offset method basically works with character iteration
     */
    #[Test]
    public function offsetInCharacterIterationBasicallyWorks()
    {
        $iterator = new TextIterator('This is a test string. Let\'s iterate it by character...', TextIterator::CHARACTER);
        foreach ($iterator as $currentCharacter) {
            if ($currentCharacter === 'L') {
                break;
            }
        }
        self::assertSame(23, $iterator->offset(), 'Wrong offset returned in character iteration.');
    }

    /**
     * Checks if the offset method basically works with word iteration
     */
    #[Test]
    public function offsetInWordIterationBasicallyWorks()
    {
        $iterator = new TextIterator('This is a test string. Let\'s iterate it by word...', TextIterator::WORD);
        foreach ($iterator as $currentWord) {
            if ($currentWord === 'iterate') {
                break;
            }
        }
        self::assertSame(29, $iterator->offset(), 'Wrong offset returned in word iteration.');
    }

    /**
     * Checks if the offset method basically works with sentence iteration
     */
    #[Test]
    public function offsetInSentenceIterationBasicallyWorks()
    {
        $iterator = new TextIterator('This is a test string. Let\'s iterate it by word...', TextIterator::SENTENCE);
        foreach ($iterator as $currentSentence) {
            if ($currentSentence === 'Let\'s iterate it by word.') {
                break;
            }
        }
        self::assertSame(23, $iterator->offset(), 'Wrong offset returned in sentence iteration.');
    }

    /**
     * Checks if the "first" method basically works
     */
    #[Test]
    public function firstBasicallyWorks()
    {
        $iterator = new TextIterator('This is a test string. Let\'s iterate it by word...', TextIterator::WORD);
        $iterator->next();
        self::assertSame('This', $iterator->first(), 'Wrong element returned by first().');
    }

    /**
     * Checks if the "last" method basically works
     */
    #[Test]
    public function lastBasicallyWorks()
    {
        $iterator = new TextIterator('This is a test string. Let\'s iterate it by word', TextIterator::WORD);
        $iterator->rewind();
        self::assertSame('word', $iterator->last(), 'Wrong element returned by last().');
    }

    /**
     * Checks if the "getAll" method basically works
     */
    #[Test]
    public function getAllBasicallyWorks()
    {
        $iterator = new TextIterator('This is a test string.', TextIterator::WORD);

        $expectedResult = [
            0 => 'This',
            1 => ' ',
            2 => 'is',
            3 => ' ',
            4 => 'a',
            5 => ' ',
            6 => 'test',
            7 => ' ',
            8 => 'string',
            9 => '.',
        ];

        self::assertEquals($iterator->getAll(), $expectedResult, 'Wrong element returned by getAll().');
    }

    /**
     * Checks if the "isBoundary" method basically works with character iteration
     */
    #[Test]
    public function isBoundaryInCharacterIterationBasicallyWorks()
    {
        $iterator = new TextIterator('This is a test string. Let\'s iterate it by character', TextIterator::CHARACTER);
        $iterator->rewind();
        while ($iterator->valid()) {
            self::assertFalse($iterator->isBoundary(), 'Character iteration has no boundary elements.');
            $iterator->next();
        }
    }

    /**
     * Checks if the "isBoundary" method basically works with word iteration
     */
    #[Test]
    public function isBoundaryInWordIterationBasicallyWorks()
    {
        $iterator = new TextIterator('This is a test string. Let\'s iterate it by word', TextIterator::WORD);
        $iterator->rewind();
        self::assertFalse($iterator->isBoundary(), 'This element was a boundary element.');

        $iterator->next();
        self::assertTrue($iterator->isBoundary(), 'This element was no boundary element.');
    }

    /**
     * Checks if the "isBoundary" method basically works with sentence iteration
     */
    #[Test]
    public function isBoundaryInSentenceIterationBasicallyWorks()
    {
        $iterator = new TextIterator('This is a test string. Let\'s iterate it by sentence', TextIterator::SENTENCE);
        $iterator->rewind();
        self::assertFalse($iterator->isBoundary(), 'This element was a boundary element.');

        $iterator->next();
        self::assertTrue($iterator->isBoundary(), 'This element was no boundary element.');
    }

    /**
     * Checks if the "isBoundary" method basically works with line iteration
     */
    #[Test]
    public function isBoundaryInLineIterationBasicallyWorks()
    {
        $iterator = new TextIterator("This is a test string. \nLet\'s iterate \nit by line", TextIterator::LINE);
        $iterator->rewind();
        self::assertFalse($iterator->isBoundary(), 'This element was a boundary element.');

        $iterator->next();
        self::assertTrue($iterator->isBoundary(), 'This element was no boundary element.');
    }

    /**
     * Checks if the "following" method basically works with word iteration
     */
    #[Test]
    public function followingBasicallyWorks()
    {
        $iterator = new TextIterator('This is a test string. Let\'s iterate it by word', TextIterator::WORD);
        self::assertSame(14, $iterator->following(11), 'Wrong offset for the following element returned.');
    }

    /**
     * Checks if the "preceding" method basically works with word iteration
     */
    #[Test]
    public function precedingBasicallyWorks()
    {
        $iterator = new TextIterator('This is a test string. Let\'s iterate it by word', TextIterator::WORD);

        self::assertSame(10, $iterator->preceding(11), 'Wrong offset for the preceding element returned.' . $iterator->preceding(11));
    }
}
