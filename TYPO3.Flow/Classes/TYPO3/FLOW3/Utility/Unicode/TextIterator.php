<?php
namespace TYPO3\FLOW3\Utility\Unicode;

/*                                                                        *
 * This script belongs to the FLOW3 package "PHP6".                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A PHP-based port of PHP6's built in TextIterator
 *
 * @FLOW3\Scope("singleton")
 */
class TextIterator implements \Iterator {

	const
		CODE_POINT = 1,
		COMB_SEQUENCE = 2,
		CHARACTER = 3,
		WORD = 4,
		LINE = 5,
		SENTENCE = 6,

		DONE = 'DONE',

		WORD_NONE = 'WORD_NONE',
		WORD_NONE_LIMIT = 'WORD_NONE_LIMIT',
		WORD_NUMBER = 'WORD_NUMBER',
		WORD_NUMBER_LIMIT = 'WORD_NUMBER_LIMIT',
		WORD_LETTER = 'WORD_LETTER',
		WORD_LETTER_LIMIT = 'WORD_LETTER_LIMIT',
		WORD_KANA = 'WORD_KANA',
		WORD_KANA_LIMIT = 'WORD_KANA_LIMIT',

		LINE_SOFT = 'LINE_SOFT',
		LINE_SOFT_LIMIT = 'LINE_SOFT_LIMIT',
		LINE_HARD = 'LINE_HARD',
		LINE_HARD_LIMIT = 'LINE_HARD_LIMIT',

		SENTENCE_TERM = 'SENTENCE_TERM',
		SENTENCE_TERM_LIMIT = 'SENTENCE_TERM_LIMIT',
		SENTENCE_SEP = 'SENTENCE_SEP',
		SENTENCE_SEP_LIMIT = 'SENTENCE_SEP_LIMIT',

		REGEXP_SENTENCE_DELIMITERS = '[\.|,|!|\?|;]';

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
	 * @var \TYPO3\FLOW3\Utility\Unicode\TextIteratorElement
	 */
	protected $previousElement;

	/**
	 * Constructs the TextIterator
	 *
	 * @param string $subject
	 * @param integer $iteratorType The type of iterator
	 * @throws \TYPO3\FLOW3\Error\Exception
	 */
	public function __construct($subject, $iteratorType = self::CHARACTER) {
		if ($iteratorType < 1 || $iteratorType > 6) throw new \TYPO3\FLOW3\Error\Exception('Fatal error: Invalid iterator type in TextIterator constructor', 1210849014);

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
	public function current() {
		return $this->getCurrentElement()->getValue();
	}

	/**
	 * Advances the iterator to the next element
	 *
	 * @return void
	 */
	public function next() {
		$this->previousElement = $this->getCurrentElement();
		$this->iteratorCacheIterator->next();
	}

	/**
	 * Returns the key of the current element. That means the number of the
	 * current element starting with 0.
	 *
	 * @return mixed Key (number) of the current element
	 */
	public function key() {
		return $this->iteratorCacheIterator->key();
	}

	/**
	 * Returns true, if the current element is not the end of the iterator
	 *
	 * @return boolean True if the iterator has not reached it's end
	 */
	public function valid() {
		if ($this->getCurrentElement()->getValue() != self::DONE && $this->getCurrentElement()->getOffset() != -1) return TRUE;
		return FALSE;
	}

	/**
	 * Sets the iterator back to the first element
	 *
	 * @return void
	 */
	public function rewind() {
		$this->iteratorCacheIterator->rewind();
	}

	/**
	 * Returns the offset in the original given string of the current element
	 *
	 * @return integer The offset of the current element
	 */
	public function offset() {
		return $this->getCurrentElement()->getOffset();
	}

	/**
	 * Returns the previous element
	 *
	 * @return string The previous element of the iterator
	 */
	public function previous() {
		return $this->previousElement->getValue();
	}

	/**
	 * Returns the last element of the iterator
	 *
	 * @return string the last element of the iterator
	 */
	public function last() {
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
	public function following($offset) {
		$this->rewind();
		while ($this->valid()) {
			$this->next();
			$nextElement = $this->getCurrentElement();
			if ($nextElement->getOffset() >= $offset) return $nextElement->getOffset();
		}
		return $this->offset();
	}

	/**
	 * Returns the element preceding the character of the original string given by its offset
	 *
	 * @param integer $offset The offset of the character
	 * @return string The element preceding this character
	 */
	public function preceding($offset) {
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
	public function isBoundary() {
		return $this->getCurrentElement()->isBoundary();
	}

	/**
	 * Returns all elements of the iterator in an array
	 *
	 * @return array All elements of the iterator
	 */
	public function getAll() {
		$this->rewind();
		$allValues = array();
		while ($this->valid()) {
			$allValues[] = $this->getCurrentElement()->getValue();
			$this->next();
		}
		return $allValues;
	}

	/**
	 * @throws UnsupportedFeatureException
	 */
	public function getRuleStatus() {
		throw new \TYPO3\FLOW3\Utility\Unicode\UnsupportedFeatureException('getRuleStatus() is not supported.', 1210849057);
	}

	/**
	 * @throws UnsupportedFeatureException
	 */
	public function getRuleStatusArray() {
		throw new \TYPO3\FLOW3\Utility\Unicode\UnsupportedFeatureException('getRuleStatusArray() is not supported.', 1210849076);
	}

	/**
	 * @throws UnsupportedFeatureException
	 */
	public function getAvailableLocales() {
		throw new \TYPO3\FLOW3\Utility\Unicode\UnsupportedFeatureException('getAvailableLocales() is not supported.', 1210849105);
	}

	/**
	 * Returns the first element
	 *
	 * @return string The first element of the iterator
	 */
	public function first() {
		$this->rewind();
		return $this->getCurrentElement()->getValue();
	}

	/**
	 * Helper function to coordinate the "string splitting"
	 *
	 * @return void
	 * @throws UnsupportedFeatureException
	 */
	private function generateIteratorElements() {

		if ($this->subject == '') {
			$this->iteratorCache->append(new \TYPO3\FLOW3\Utility\Unicode\TextIteratorElement(self::DONE, -1));
			return;
		}

		if ($this->iteratorType == self::CODE_POINT) throw new \TYPO3\FLOW3\Utility\Unicode\UnsupportedFeatureException('Unsupported iterator type.', 1210849150);
		elseif ($this->iteratorType == self::COMB_SEQUENCE)throw new \TYPO3\FLOW3\Utility\Unicode\UnsupportedFeatureException('Unsupported iterator type.', 1210849151);
		elseif ($this->iteratorType == self::CHARACTER) $this->parseSubjectByCharacter();
		elseif ($this->iteratorType == self::WORD) $this->parseSubjectByWord();
		elseif ($this->iteratorType == self::LINE) $this->parseSubjectByLine();
		elseif ($this->iteratorType == self::SENTENCE) $this->parseSubjectBySentence();

		$this->iteratorCache->append(new \TYPO3\FLOW3\Utility\Unicode\TextIteratorElement(self::DONE, -1));
	}

	/**
	 * Helper function to do the splitting by character
	 *
	 */
	private function parseSubjectByCharacter() {
		$i = 0;
		foreach (preg_split('//u', $this->subject) as $currentCharacter) {
			if ($currentCharacter == '') continue;
			$this->iteratorCache->append(new \TYPO3\FLOW3\Utility\Unicode\TextIteratorElement($currentCharacter, $i, 1, FALSE));
			$i++;
		}
	}

	/**
	 * Helper function to do the splitting by word. Note: punctuation marks are
	 * treated as words, spaces as boundary elements
	 *
	 */
	private function parseSubjectByWord() {
		$i = 0;
		$isFirstIteration = TRUE;
		foreach (explode(' ', $this->subject) as $currentWord) {
			$delimitersMatches = array();
			$haveProcessedCurrentWord = FALSE;

			if (preg_match_all('/' . self::REGEXP_SENTENCE_DELIMITERS . '/', $currentWord, $delimitersMatches)) {
				$this->iteratorCache->append(new \TYPO3\FLOW3\Utility\Unicode\TextIteratorElement(' ', $i, 1, TRUE));

				$j = 0;
				$splittedWord = preg_split('/' . self::REGEXP_SENTENCE_DELIMITERS . '/', $currentWord);
				foreach ($splittedWord as $currentPart) {
					if ($currentPart != '') {
						$this->iteratorCache->append(new \TYPO3\FLOW3\Utility\Unicode\TextIteratorElement($currentPart, $i, \TYPO3\FLOW3\Utility\Unicode\Functions::strlen($currentPart), FALSE));
						$i += \TYPO3\FLOW3\Utility\Unicode\Functions::strlen($currentPart);
					}
					if ($j < count($delimitersMatches[0])) $this->iteratorCache->append(new \TYPO3\FLOW3\Utility\Unicode\TextIteratorElement($delimitersMatches[0][$j], $i, 1, TRUE));
					$i++;
					$j++;
				}
				$haveProcessedCurrentWord = TRUE;
			}

			if (!$isFirstIteration && !$haveProcessedCurrentWord) {
				$this->iteratorCache->append(new \TYPO3\FLOW3\Utility\Unicode\TextIteratorElement(' ', $i, 1, TRUE));
				$i++;
			} else {
				$isFirstIteration = FALSE;
			}

			if (!$haveProcessedCurrentWord) {
				$this->iteratorCache->append(new \TYPO3\FLOW3\Utility\Unicode\TextIteratorElement($currentWord, $i, \TYPO3\FLOW3\Utility\Unicode\Functions::strlen($currentWord), FALSE));
				$i += \TYPO3\FLOW3\Utility\Unicode\Functions::strlen($currentWord);
			}

			unset($delimitersMatches);
		}
	}

	/**
	 * Helper function to do the splitting by line. Note: one punctuations mark
	 * belongs to the preceding sentence.
	 * "\n" is boundary element.
	 *
	 */
	private function parseSubjectByLine() {
		$i = 0;
		$j = 0;
		$lines = explode("\n", $this->subject);
		foreach ($lines as $currentLine) {

			$this->iteratorCache->append(new \TYPO3\FLOW3\Utility\Unicode\TextIteratorElement($currentLine, $i, \TYPO3\FLOW3\Utility\Unicode\Functions::strlen($currentLine), FALSE));
			$i += \TYPO3\FLOW3\Utility\Unicode\Functions::strlen($currentLine);

			if (count($lines) - 1 > $j) {
				$this->iteratorCache->append(new \TYPO3\FLOW3\Utility\Unicode\TextIteratorElement("\n", $i, 1, TRUE));
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
	 */
	private function parseSubjectBySentence() {
		$i = 0;
		$j = 0;
		$count = 0;
		$delimitersMatches = array();
		preg_match_all('/' . self::REGEXP_SENTENCE_DELIMITERS . '/', $this->subject, $delimitersMatches);
		$splittedSentence = preg_split('/' . self::REGEXP_SENTENCE_DELIMITERS . '/', $this->subject);

		if (count($splittedSentence) == 1) {
			$this->iteratorCache->append(new \TYPO3\FLOW3\Utility\Unicode\TextIteratorElement($splittedSentence[0], 0, \TYPO3\FLOW3\Utility\Unicode\Functions::strlen($splittedSentence[0]), FALSE));
			return;
		}

		foreach ($splittedSentence as $currentPart) {
			$currentPart = preg_replace('/^\s|\s$/', '', $currentPart, -1, $count);

			$whiteSpace = '';
			for ($k = 0; $k < $count; $k++) $whiteSpace .= ' ';
			if ($whiteSpace != '') $this->iteratorCache->append(new \TYPO3\FLOW3\Utility\Unicode\TextIteratorElement($whiteSpace, $i, $count, TRUE));
			$i += $count;

			if ($currentPart != '' && $j < count($delimitersMatches[0])) {
				$this->iteratorCache->append(new \TYPO3\FLOW3\Utility\Unicode\TextIteratorElement($currentPart . $delimitersMatches[0][$j], $i, \TYPO3\FLOW3\Utility\Unicode\Functions::strlen($currentPart . $delimitersMatches[0][$j]), FALSE));
				$i += \TYPO3\FLOW3\Utility\Unicode\Functions::strlen($currentPart . $delimitersMatches[0][$j]);
				$j++;
			}
			elseif ($j < count($delimitersMatches[0])) {
				$this->iteratorCache->append(new \TYPO3\FLOW3\Utility\Unicode\TextIteratorElement($delimitersMatches[0][$j], $i, 1, TRUE));
				$i++;
				$j++;
			}
		}
	}

	/**
	 * Helper function to get the current element from the cache.
	 *
	 * @return \TYPO3\FLOW3\Utility\Unicode\TextIteratorElement The current element of the cache
	 */
	private function getCurrentElement() {
		return $this->iteratorCacheIterator->current();
	}
}

?>