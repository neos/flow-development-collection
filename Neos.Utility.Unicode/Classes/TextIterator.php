<?php
namespace Neos\Utility\Unicode;

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

/**
 * A UTF8-aware TextIterator
 *
 */
class TextIterator implements \Iterator
{
    const CODE_POINT = 1;
    const COMB_SEQUENCE = 2;
    const CHARACTER = 3;
    const WORD = 4;
    const LINE = 5;
    const SENTENCE = 6;

    const DONE = 'DONE';

    const WORD_NONE = 'WORD_NONE';
    const WORD_NONE_LIMIT = 'WORD_NONE_LIMIT';
    const WORD_NUMBER = 'WORD_NUMBER';
    const WORD_NUMBER_LIMIT = 'WORD_NUMBER_LIMIT';
    const WORD_LETTER = 'WORD_LETTER';
    const WORD_LETTER_LIMIT = 'WORD_LETTER_LIMIT';
    const WORD_KANA = 'WORD_KANA';
    const WORD_KANA_LIMIT = 'WORD_KANA_LIMIT';

    const LINE_SOFT = 'LINE_SOFT';
    const LINE_SOFT_LIMIT = 'LINE_SOFT_LIMIT';
    const LINE_HARD = 'LINE_HARD';
    const LINE_HARD_LIMIT = 'LINE_HARD_LIMIT';

    const SENTENCE_TERM = 'SENTENCE_TERM';
    const SENTENCE_TERM_LIMIT = 'SENTENCE_TERM_LIMIT';
    const SENTENCE_SEP = 'SENTENCE_SEP';
    const SENTENCE_SEP_LIMIT = 'SENTENCE_SEP_LIMIT';

    const REGEXP_SENTENCE_DELIMITERS = '[\.|,|!|\?|;]';

    /**
     * @var integer
     */
    protected $iteratorType;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var integer
     */
    protected $currentPosition;

    /**
     * @var \ArrayObject
     */
    protected $iteratorCache;

    /**
     * @var \ArrayIterator
     */
    protected $iteratorCacheIterator;

    /**
     * @var TextIteratorElement
     */
    protected $previousElement;

    /**
     * Constructs the TextIterator
     *
     * @param string $subject
     * @param integer $iteratorType The type of iterator
     * @throws Exception
     */
    public function __construct($subject, $iteratorType = self::CHARACTER)
    {
        if ($iteratorType < 1 || $iteratorType > 6) {
            throw new Exception('Fatal error: Invalid iterator type in TextIterator constructor', 1210849014);
        }

        $this->iteratorType = $iteratorType;
        $this->subject = (string)$subject;
        $this->currentPosition = 0;
        $this->iteratorCache = new \ArrayObject();
        $this->iteratorCacheIterator = $this->iteratorCache->getIterator();

        $this->generateIteratorElements();
        $this->iteratorCacheIterator->rewind();
        $this->previousElement = $this->iteratorCacheIterator->current();
    }

    /**
     * Returns the current element
     *
     * @return string The value of the current element
     */
    public function current()
    {
        return $this->getCurrentElement()->getValue();
    }

    /**
     * Advances the iterator to the next element
     *
     * @return void
     */
    public function next()
    {
        $this->previousElement = $this->getCurrentElement();
        $this->iteratorCacheIterator->next();
    }

    /**
     * Returns the key of the current element. That means the number of the
     * current element starting with 0.
     *
     * @return mixed Key (number) of the current element
     */
    public function key()
    {
        return $this->iteratorCacheIterator->key();
    }

    /**
     * Returns true, if the current element is not the end of the iterator
     *
     * @return boolean True if the iterator has not reached it's end
     */
    public function valid()
    {
        if ($this->getCurrentElement() && $this->getCurrentElement()->getValue() != self::DONE && $this->getCurrentElement()->getOffset() != -1) {
            return true;
        }
        return false;
    }

    /**
     * Sets the iterator back to the first element
     *
     * @return void
     */
    public function rewind()
    {
        $this->iteratorCacheIterator->rewind();
    }

    /**
     * Returns the offset in the original given string of the current element
     *
     * @return integer The offset of the current element
     */
    public function offset()
    {
        return $this->getCurrentElement()->getOffset();
    }

    /**
     * Returns the previous element
     *
     * @return string The previous element of the iterator
     */
    public function previous()
    {
        return $this->previousElement->getValue();
    }

    /**
     * Returns the last element of the iterator
     *
     * @return string the last element of the iterator
     */
    public function last()
    {
        $this->rewind();
        $previousElement = $this->getCurrentElement();
        while ($this->valid()) {
            $previousElement = $this->getCurrentElement();
            $this->next();
        }
        return $previousElement->getValue();
    }

    /**
     * Returns the next elment following the character of the original string
     * given by its offset
     *
     * @param integer $offset The offset of the character
     * @return string The element following this character
     */
    public function following($offset)
    {
        $this->rewind();
        while ($this->valid()) {
            $this->next();
            $nextElement = $this->getCurrentElement();
            if ($nextElement->getOffset() >= $offset) {
                return $nextElement->getOffset();
            }
        }
        return $this->offset();
    }

    /**
     * Returns the element preceding the character of the original string given by its offset
     *
     * @param integer $offset The offset of the character
     * @return string The element preceding this character
     */
    public function preceding($offset)
    {
        $this->rewind();
        while ($this->valid()) {
            $previousElement = $this->getCurrentElement();
            $this->next();
            $currentElement = $this->getCurrentElement();
            if (($currentElement->getOffset() + $currentElement->getLength()) >= $offset) {
                return $previousElement->getOffset() + $previousElement->getLength();
            }
        }
        return $currentElement->getOffset() + $currentElement->getLength();
    }

    /**
     * Returns true if the current element is a boundary element.
     *
     * Boundaries are:
     * CHARACTER: none
     * WORD:      <space>.,!?;
     * SENTENCE:  .,!?;
     * LINE:      <\n>
     *
     * @return boolean True if the current element is a boundary element
     */
    public function isBoundary()
    {
        return $this->getCurrentElement()->isBoundary();
    }

    /**
     * Returns all elements of the iterator in an array
     *
     * @return array All elements of the iterator
     */
    public function getAll()
    {
        $this->rewind();
        $allValues = [];
        while ($this->valid()) {
            $allValues[] = $this->getCurrentElement()->getValue();
            $this->next();
        }
        return $allValues;
    }

    /**
     * @throws UnsupportedFeatureException
     */
    public function getRuleStatus()
    {
        throw new UnsupportedFeatureException('getRuleStatus() is not supported.', 1210849057);
    }

    /**
     * @throws UnsupportedFeatureException
     */
    public function getRuleStatusArray()
    {
        throw new UnsupportedFeatureException('getRuleStatusArray() is not supported.', 1210849076);
    }

    /**
     * @throws UnsupportedFeatureException
     */
    public function getAvailableLocales()
    {
        throw new UnsupportedFeatureException('getAvailableLocales() is not supported.', 1210849105);
    }

    /**
     * Returns the first element
     *
     * @return string The first element of the iterator
     */
    public function first()
    {
        $this->rewind();
        return $this->getCurrentElement()->getValue();
    }

    /**
     * Helper function to coordinate the "string splitting"
     *
     * @return void
     * @throws UnsupportedFeatureException
     */
    private function generateIteratorElements()
    {
        if ($this->subject == '') {
            $this->iteratorCache->append(new TextIteratorElement(self::DONE, -1));
            return;
        }

        if ($this->iteratorType == self::CODE_POINT) {
            throw new UnsupportedFeatureException('Unsupported iterator type.', 1210849150);
        } elseif ($this->iteratorType == self::COMB_SEQUENCE) {
            throw new UnsupportedFeatureException('Unsupported iterator type.', 1210849151);
        } elseif ($this->iteratorType == self::CHARACTER) {
            $this->parseSubjectByCharacter();
        } elseif ($this->iteratorType == self::WORD) {
            $this->parseSubjectByWord();
        } elseif ($this->iteratorType == self::LINE) {
            $this->parseSubjectByLine();
        } elseif ($this->iteratorType == self::SENTENCE) {
            $this->parseSubjectBySentence();
        }

        $this->iteratorCache->append(new TextIteratorElement(self::DONE, -1));
        $this->iteratorCacheIterator = $this->iteratorCache->getIterator();
    }

    /**
     * Helper function to do the splitting by character
     *
     */
    private function parseSubjectByCharacter()
    {
        $i = 0;
        foreach (preg_split('//u', $this->subject) as $currentCharacter) {
            if ($currentCharacter == '') {
                continue;
            }
            $this->iteratorCache->append(new TextIteratorElement($currentCharacter, $i, 1, false));
            $i++;
        }
    }

    /**
     * Helper function to do the splitting by word. Note: punctuation marks are
     * treated as words, spaces as boundary elements
     *
     * @return void
     */
    private function parseSubjectByWord()
    {
        $i = 0;
        $isFirstIteration = true;
        foreach (explode(' ', $this->subject) as $currentWord) {
            $delimitersMatches = [];
            $haveProcessedCurrentWord = false;

            if (preg_match_all('/' . self::REGEXP_SENTENCE_DELIMITERS . '/', $currentWord, $delimitersMatches)) {
                $this->iteratorCache->append(new TextIteratorElement(' ', $i, 1, true));

                $j = 0;
                $splittedWord = preg_split('/' . self::REGEXP_SENTENCE_DELIMITERS . '/', $currentWord);
                foreach ($splittedWord as $currentPart) {
                    if ($currentPart != '') {
                        $this->iteratorCache->append(new TextIteratorElement($currentPart, $i, Unicode\Functions::strlen($currentPart), false));
                        $i += Unicode\Functions::strlen($currentPart);
                    }
                    if ($j < count($delimitersMatches[0])) {
                        $this->iteratorCache->append(new TextIteratorElement($delimitersMatches[0][$j], $i, 1, true));
                    }
                    $i++;
                    $j++;
                }
                $haveProcessedCurrentWord = true;
            }

            if (!$isFirstIteration && !$haveProcessedCurrentWord) {
                $this->iteratorCache->append(new TextIteratorElement(' ', $i, 1, true));
                $i++;
            } else {
                $isFirstIteration = false;
            }

            if (!$haveProcessedCurrentWord) {
                $this->iteratorCache->append(new TextIteratorElement($currentWord, $i, Unicode\Functions::strlen($currentWord), false));
                $i += Unicode\Functions::strlen($currentWord);
            }

            unset($delimitersMatches);
        }
    }

    /**
     * Helper function to do the splitting by line. Note: one punctuations mark
     * belongs to the preceding sentence.
     * "\n" is boundary element.
     *
     * @return void
     */
    private function parseSubjectByLine()
    {
        $i = 0;
        $j = 0;
        $lines = explode("\n", $this->subject);
        foreach ($lines as $currentLine) {
            $this->iteratorCache->append(new TextIteratorElement($currentLine, $i, Unicode\Functions::strlen($currentLine), false));
            $i += Unicode\Functions::strlen($currentLine);

            if (count($lines) - 1 > $j) {
                $this->iteratorCache->append(new TextIteratorElement("\n", $i, 1, true));
                $i++;
            }
            $j++;
        }
    }

    /**
     * Helper function to do the splitting by sentence. Note: one punctuations
     * mark belongs to the preceding sentence. Whitespace between sentences is
     * marked as boundary.
     *
     * @return void
     */
    private function parseSubjectBySentence()
    {
        $i = 0;
        $j = 0;
        $count = 0;
        $delimitersMatches = [];
        preg_match_all('/' . self::REGEXP_SENTENCE_DELIMITERS . '/', $this->subject, $delimitersMatches);
        $splittedSentence = preg_split('/' . self::REGEXP_SENTENCE_DELIMITERS . '/', $this->subject);

        if (count($splittedSentence) == 1) {
            $this->iteratorCache->append(new TextIteratorElement($splittedSentence[0], 0, Unicode\Functions::strlen($splittedSentence[0]), false));
            return;
        }

        foreach ($splittedSentence as $currentPart) {
            $currentPart = preg_replace('/^\s|\s$/', '', $currentPart, -1, $count);

            $whiteSpace = '';
            for ($k = 0; $k < $count; $k++) {
                $whiteSpace .= ' ';
            }
            if ($whiteSpace != '') {
                $this->iteratorCache->append(new TextIteratorElement($whiteSpace, $i, $count, true));
            }
            $i += $count;

            if ($currentPart != '' && $j < count($delimitersMatches[0])) {
                $this->iteratorCache->append(new TextIteratorElement($currentPart . $delimitersMatches[0][$j], $i, Unicode\Functions::strlen($currentPart . $delimitersMatches[0][$j]), false));
                $i += Unicode\Functions::strlen($currentPart . $delimitersMatches[0][$j]);
                $j++;
            } elseif ($j < count($delimitersMatches[0])) {
                $this->iteratorCache->append(new TextIteratorElement($delimitersMatches[0][$j], $i, 1, true));
                $i++;
                $j++;
            }
        }
    }

    /**
     * Helper function to get the current element from the cache.
     *
     * @return TextIteratorElement The current element of the cache
     */
    private function getCurrentElement()
    {
        return $this->iteratorCacheIterator->current();
    }
}
