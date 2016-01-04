<?php
namespace TYPO3\Flow\Tests\Unit\Utility\Unicode;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the TextIterator port
 *
 */
class TextIteratorTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * Checks if a new instance with the default iterator type can be created
     *
     * @test
     */
    public function canCreateIteratorOfDefaultType()
    {
        $iterator = new \TYPO3\Flow\Utility\Unicode\TextIterator('Some string');
        $this->assertInstanceOf(\TYPO3\Flow\Utility\Unicode\TextIterator::class, $iterator);
    }

    /**
     * Checks if a new instance iterating over characters can be created
     *
     * @test
     */
    public function instantiatingCharacterIteratorWorks()
    {
        $characterIterator = new \TYPO3\Flow\Utility\Unicode\TextIterator('Some string', \TYPO3\Flow\Utility\Unicode\TextIterator::CHARACTER);
        $this->assertInstanceOf(\TYPO3\Flow\Utility\Unicode\TextIterator::class, $characterIterator);
    }

    /**
     * Checks if a new instance iterating over words can be created
     *
     * @test
     */
    public function instantiatingWordIteratorWorks()
    {
        $wordIterator = new \TYPO3\Flow\Utility\Unicode\TextIterator('Some string', \TYPO3\Flow\Utility\Unicode\TextIterator::WORD);
        $this->assertInstanceOf(\TYPO3\Flow\Utility\Unicode\TextIterator::class, $wordIterator);
    }


    /**
     * Checks if a new instance iterating over sentences can be created
     *
     * @test
     */
    public function instantiatingSentenceIteratorWorks()
    {
        $sentenceIterator = new \TYPO3\Flow\Utility\Unicode\TextIterator('Some string', \TYPO3\Flow\Utility\Unicode\TextIterator::SENTENCE);
        $this->assertInstanceOf(\TYPO3\Flow\Utility\Unicode\TextIterator::class, $sentenceIterator);
    }

    /**
     * Checks if a new instance iterating over lines can be created
     *
     * @test
     */
    public function instantiatingLineIteratorWorks()
    {
        $lineIterator = new \TYPO3\Flow\Utility\Unicode\TextIterator('Some string', \TYPO3\Flow\Utility\Unicode\TextIterator::LINE);
        $this->assertInstanceOf(\TYPO3\Flow\Utility\Unicode\TextIterator::class, $lineIterator);
    }


    /**
     * Checks if the constructor rejects an invalid iterator type
     *
     * @test
     */
    public function instantiatingIteratorWithInvalidTypeThrowsError()
    {
        try {
            new \TYPO3\Flow\Utility\Unicode\TextIterator('Some string', 948);
            $this->fail('Constructor did not reject invalid TextIterator type.');
        } catch (\TYPO3\Flow\Error\Exception $exception) {
            $this->assertContains('Invalid iterator type in TextIterator constructor', $exception->getMessage(), 'Wrong error message.');
        }
    }

    /**
     * Checks if character iteration basically works
     *
     * @test
     */
    public function characterIterationBasicallyWorks()
    {
        $iterator = new \TYPO3\Flow\Utility\Unicode\TextIterator('This is a test string. Let\'s iterate it by character...', \TYPO3\Flow\Utility\Unicode\TextIterator::CHARACTER);
        $iterator->rewind();
        $result = '';
        foreach ($iterator as $currentCharacter) {
            $result .= $currentCharacter;
        }
        $this->assertEquals('This is a test string. Let\'s iterate it by character...', $result, 'Character iteration didn\'t return the right values.');
    }

    /**
     * Checks if word iteration basically works
     *
     * @test
     */
    public function wordIterationBasicallyWorks()
    {
        $iterator = new \TYPO3\Flow\Utility\Unicode\TextIterator('This is a test string. Let\'s iterate it by word...', \TYPO3\Flow\Utility\Unicode\TextIterator::WORD);
        $iterator->rewind();
        $result = '';
        foreach ($iterator as $currentWord) {
            $result .= $currentWord;
        }
        $this->assertEquals('This is a test string. Let\'s iterate it by word...', $result, 'Word iteration didn\'t return the right values.');
    }

    /**
     * Checks if sentence iteration basically works
     *
     * @test
     */
    public function sentenceIterationBasicallyWorks()
    {
        $iterator = new \TYPO3\Flow\Utility\Unicode\TextIterator('This is a test string. Let\'s iterate it by sentence...', \TYPO3\Flow\Utility\Unicode\TextIterator::SENTENCE);
        $iterator->rewind();
        $result = '';
        foreach ($iterator as $currentSentence) {
            $result .= $currentSentence;
        }
        $this->assertEquals('This is a test string. Let\'s iterate it by sentence...', $result, 'Sentence iteration didn\'t return the right values.');
    }

    /**
     * Checks if line iteration basically works
     *
     * @test
     */
    public function lineIterationBasicallyWorks()
    {
        $iterator = new \TYPO3\Flow\Utility\Unicode\TextIterator("This is a test string. \nLet's iterate \nit by line...", \TYPO3\Flow\Utility\Unicode\TextIterator::LINE);
        $iterator->rewind();
        $result = '';
        foreach ($iterator as $currentLine) {
            $result .= $currentLine;
        }
        $this->assertEquals("This is a test string. \nLet's iterate \nit by line...", $result, 'Line iteration didn\'t return the right values.');
    }

    /**
     * Checks if the offset method basically works with character iteration
     *
     * @test
     */
    public function offsetInCharacterIterationBasicallyWorks()
    {
        $iterator = new \TYPO3\Flow\Utility\Unicode\TextIterator('This is a test string. Let\'s iterate it by character...', \TYPO3\Flow\Utility\Unicode\TextIterator::CHARACTER);
        foreach ($iterator as $currentCharacter) {
            if ($currentCharacter == 'L') {
                break;
            }
        }
        $this->assertEquals($iterator->offset(), 23, 'Wrong offset returned in character iteration.');
    }

    /**
     * Checks if the offset method basically works with word iteration
     *
     * @test
     */
    public function offsetInWordIterationBasicallyWorks()
    {
        $iterator = new \TYPO3\Flow\Utility\Unicode\TextIterator('This is a test string. Let\'s iterate it by word...', \TYPO3\Flow\Utility\Unicode\TextIterator::WORD);
        foreach ($iterator as $currentWord) {
            if ($currentWord == 'iterate') {
                break;
            }
        }
        $this->assertEquals($iterator->offset(), 29, 'Wrong offset returned in word iteration.');
    }

    /**
     * Checks if the offset method basically works with sentence iteration
     *
     * @test
     */
    public function offsetInSentenceIterationBasicallyWorks()
    {
        $iterator = new \TYPO3\Flow\Utility\Unicode\TextIterator('This is a test string. Let\'s iterate it by word...', \TYPO3\Flow\Utility\Unicode\TextIterator::SENTENCE);
        foreach ($iterator as $currentSentence) {
            if ($currentSentence == 'Let\'s iterate it by word.') {
                break;
            }
        }
        $this->assertEquals($iterator->offset(), 23, 'Wrong offset returned in sentence iteration.');
    }

    /**
     * Checks if the "first" method basically works
     *
     * @test
     */
    public function firstBasicallyWorks()
    {
        $iterator = new \TYPO3\Flow\Utility\Unicode\TextIterator('This is a test string. Let\'s iterate it by word...', \TYPO3\Flow\Utility\Unicode\TextIterator::WORD);
        $iterator->next();
        $this->assertEquals($iterator->first(), 'This', 'Wrong element returned by first().');
    }

    /**
     * Checks if the "last" method basically works
     *
     * @test
     */
    public function lastBasicallyWorks()
    {
        $iterator = new \TYPO3\Flow\Utility\Unicode\TextIterator('This is a test string. Let\'s iterate it by word', \TYPO3\Flow\Utility\Unicode\TextIterator::WORD);
        $iterator->rewind();
        $this->assertEquals($iterator->last(), 'word', 'Wrong element returned by last().');
    }

    /**
     * Checks if the "getAll" method basically works
     *
     * @test
     */
    public function getAllBasicallyWorks()
    {
        $iterator = new \TYPO3\Flow\Utility\Unicode\TextIterator('This is a test string.', \TYPO3\Flow\Utility\Unicode\TextIterator::WORD);

        $expectedResult = array(
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
        );

        $this->assertEquals($iterator->getAll(), $expectedResult, 'Wrong element returned by getAll().');
    }

    /**
     * Checks if the "isBoundary" method basically works with character iteration
     *
     * @test
     */
    public function isBoundaryInCharacterIterationBasicallyWorks()
    {
        $iterator = new \TYPO3\Flow\Utility\Unicode\TextIterator('This is a test string. Let\'s iterate it by character', \TYPO3\Flow\Utility\Unicode\TextIterator::CHARACTER);
        $iterator->rewind();
        while ($iterator->valid()) {
            $this->assertFalse($iterator->isBoundary(), 'Character iteration has no boundary elements.');
            $iterator->next();
        }
    }

    /**
     * Checks if the "isBoundary" method basically works with word iteration
     *
     * @test
     */
    public function isBoundaryInWordIterationBasicallyWorks()
    {
        $iterator = new \TYPO3\Flow\Utility\Unicode\TextIterator('This is a test string. Let\'s iterate it by word', \TYPO3\Flow\Utility\Unicode\TextIterator::WORD);
        $iterator->rewind();
        $this->assertFalse($iterator->isBoundary(), 'This element was a boundary element.');

        $iterator->next();
        $this->assertTrue($iterator->isBoundary(), 'This element was no boundary element.');
    }

    /**
     * Checks if the "isBoundary" method basically works with sentence iteration
     *
     * @test
     */
    public function isBoundaryInSentenceIterationBasicallyWorks()
    {
        $iterator = new \TYPO3\Flow\Utility\Unicode\TextIterator('This is a test string. Let\'s iterate it by sentence', \TYPO3\Flow\Utility\Unicode\TextIterator::SENTENCE);
        $iterator->rewind();
        $this->assertFalse($iterator->isBoundary(), 'This element was a boundary element.');

        $iterator->next();
        $this->assertTrue($iterator->isBoundary(), 'This element was no boundary element.');
    }

    /**
     * Checks if the "isBoundary" method basically works with line iteration
     *
     * @test
     */
    public function isBoundaryInLineIterationBasicallyWorks()
    {
        $iterator = new \TYPO3\Flow\Utility\Unicode\TextIterator("This is a test string. \nLet\'s iterate \nit by line", \TYPO3\Flow\Utility\Unicode\TextIterator::LINE);
        $iterator->rewind();
        $this->assertFalse($iterator->isBoundary(), 'This element was a boundary element.');

        $iterator->next();
        $this->assertTrue($iterator->isBoundary(), 'This element was no boundary element.');
    }

    /**
     * Checks if the "following" method basically works with word iteration
     *
     * @test
     */
    public function followingBasicallyWorks()
    {
        $iterator = new \TYPO3\Flow\Utility\Unicode\TextIterator('This is a test string. Let\'s iterate it by word', \TYPO3\Flow\Utility\Unicode\TextIterator::WORD);

        $this->assertEquals($iterator->following(11), 14, 'Wrong offset for the following element returned.');
    }

    /**
     * Checks if the "preceding" method basically works with word iteration
     *
     * @test
     */
    public function precedingBasicallyWorks()
    {
        $iterator = new \TYPO3\Flow\Utility\Unicode\TextIterator('This is a test string. Let\'s iterate it by word', \TYPO3\Flow\Utility\Unicode\TextIterator::WORD);

        $this->assertEquals($iterator->preceding(11), 10, 'Wrong offset for the preceding element returned.' . $iterator->preceding(11));
    }
}
