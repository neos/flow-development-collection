<?php
namespace TYPO3\Flow\Error;

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
 * Result object for operations dealing with objects, such as the Property Mapper or the Validators.
 *
 * @api
 */
class Result {

	/**
	 * @var array<\TYPO3\Flow\Error\Error>
	 */
	protected $errors = array();

	/**
	 * Caches the existence of errors
	 * @var boolean
	 */
	protected $errorsExist = FALSE;

	/**
	 * @var array<\TYPO3\Flow\Error\Warning>
	 */
	protected $warnings = array();

	/**
	 * Caches the existence of warning
	 * @var boolean
	 */
	protected $warningsExist = FALSE;

	/**
	 * @var array<\TYPO3\Flow\Error\Notice>
	 */
	protected $notices = array();

	/**
	 * Caches the existence of notices
	 * @var boolean
	 */
	protected $noticesExist = FALSE;

	/**
	 * The result objects for the sub properties
	 *
	 * @var array<\TYPO3\Flow\Error\Result>
	 */
	protected $propertyResults = array();

	/**
	 * @var Result
	 */
	protected $parent = NULL;

	/**
	 * Injects the parent result and propagates the
	 * cached error states upwards
	 *
	 * @param Result $parent
	 * @return void
	 */
	public function setParent(Result $parent) {
		if ($this->parent !== $parent) {
			$this->parent = $parent;
			if ($this->hasErrors()) {
				$parent->setErrorsExist();
			}
			if ($this->hasWarnings()) {
				$parent->setWarningsExist();
			}
			if ($this->hasNotices()) {
				$parent->setNoticesExist();
			}
		}
	}

	/**
	 * Add an error to the current Result object
	 *
	 * @param Error $error
	 * @return void
	 * @api
	 */
	public function addError(Error $error) {
		$this->errors[] = $error;
		$this->setErrorsExist();
	}

	/**
	 * Add a warning to the current Result object
	 *
	 * @param Warning $warning
	 * @return void
	 * @api
	 */
	public function addWarning(Warning $warning) {
		$this->warnings[] = $warning;
		$this->setWarningsExist();
	}

	/**
	 * Add a notice to the current Result object
	 *
	 * @param Notice $notice
	 * @return void
	 * @api
	 */
	public function addNotice(Notice $notice) {
		$this->notices[] = $notice;
		$this->setNoticesExist();
	}

	/**
	 * Get all errors in the current Result object (non-recursive)
	 *
	 * @param string $messageTypeFilter if specified only errors implementing the given class are returned
	 * @return array<\TYPO3\Flow\Error\Error>
	 * @api
	 */
	public function getErrors($messageTypeFilter = NULL) {
		return $this->filterMessages($this->errors, $messageTypeFilter);
	}

	/**
	 * Get all warnings in the current Result object (non-recursive)
	 *
	 * @param string $messageTypeFilter if specified only warnings implementing the given class are returned
	 * @return array<\TYPO3\Flow\Error\Warning>
	 * @api
	 */
	public function getWarnings($messageTypeFilter = NULL) {
		return $this->filterMessages($this->warnings, $messageTypeFilter);
	}

	/**
	 * Get all notices in the current Result object (non-recursive)
	 *
	 * @param string $messageTypeFilter if specified only notices implementing the given class are returned
	 * @return array<\TYPO3\Flow\Error\Notice>
	 * @api
	 */
	public function getNotices($messageTypeFilter = NULL) {
		return $this->filterMessages($this->notices, $messageTypeFilter);
	}

	/**
	 * Get the first error object of the current Result object (non-recursive)
	 *
	 * @param string $messageTypeFilter if specified only errors implementing the given class are considered
	 * @return Error
	 * @api
	 */
	public function getFirstError($messageTypeFilter = NULL) {
		$matchingErrors = $this->filterMessages($this->errors, $messageTypeFilter);
		reset($matchingErrors);
		return current($matchingErrors);
	}

	/**
	 * Get the first warning object of the current Result object (non-recursive)
	 *
	 * @param string $messageTypeFilter if specified only warnings implementing the given class are considered
	 * @return Warning
	 * @api
	 */
	public function getFirstWarning($messageTypeFilter = NULL) {
		$matchingWarnings = $this->filterMessages($this->warnings, $messageTypeFilter);
		reset($matchingWarnings);
		return current($matchingWarnings);
	}

	/**
	 * Get the first notice object of the current Result object (non-recursive)
	 *
	 * @param string $messageTypeFilter if specified only notices implementing the given class are considered
	 * @return Notice
	 * @api
	 */
	public function getFirstNotice($messageTypeFilter = NULL) {
		$matchingNotices = $this->filterMessages($this->notices, $messageTypeFilter);
		reset($matchingNotices);
		return current($matchingNotices);
	}

	/**
	 * Return a Result object for the given property path. This is
	 * a fluent interface, so you will probably use it like:
	 * $result->forProperty('foo.bar')->getErrors() -- to get all errors
	 * for property "foo.bar"
	 *
	 * @param string $propertyPath
	 * @return Result
	 * @api
	 */
	public function forProperty($propertyPath) {
		if ($propertyPath === '' || $propertyPath === NULL) {
			return $this;
		}
		if (strpos($propertyPath, '.') !== FALSE) {
			return $this->recurseThroughResult(explode('.', $propertyPath));
		}
		if (!isset($this->propertyResults[$propertyPath])) {
			$newResult = new Result();
			$newResult->setParent($this);
			$this->propertyResults[$propertyPath] = $newResult;
		}
		return $this->propertyResults[$propertyPath];
	}

	/**
	 * Internal use only!
	 *
	 * @param array $pathSegments
	 * @return Result
	 */
	public function recurseThroughResult(array $pathSegments) {
		if (count($pathSegments) === 0) {
			return $this;
		}

		$propertyName = array_shift($pathSegments);

		if (!isset($this->propertyResults[$propertyName])) {
			$newResult = new Result();
			$newResult->setParent($this);
			$this->propertyResults[$propertyName] = $newResult;
		}

		/** @var Result $result */
		$result = $this->propertyResults[$propertyName];
		return $result->recurseThroughResult($pathSegments);
	}

	/**
	 * Does the current Result object have Errors? (Recursively)
	 *
	 * @return boolean
	 * @api
	 */
	public function hasErrors() {
		return $this->errorsExist;
	}

	/**
	 * Sets the error cache to TRUE and propagates the information
	 * upwards the Result-Object Tree
	 *
	 * @return void
	 */
	protected function setErrorsExist() {
		$this->errorsExist = TRUE;
		if ($this->parent !== NULL) {
			$this->parent->setErrorsExist();
		}
	}

	/**
	 * Does the current Result object have Warnings? (Recursively)
	 *
	 * @return boolean
	 * @api
	 */
	public function hasWarnings() {
		return $this->warningsExist;
	}

	/**
	 * Sets the warning cache to TRUE and propagates the information
	 * upwards the Result-Object Tree
	 *
	 * @return void
	 */
	protected function setWarningsExist() {
		$this->warningsExist = TRUE;
		if ($this->parent !== NULL) {
			$this->parent->setWarningsExist();
		}
	}

	/**
	 * Does the current Result object have Notices? (Recursively)
	 *
	 * @return boolean
	 * @api
	 */
	public function hasNotices() {
		return $this->noticesExist;
	}

	/**
	 * Sets the notices cache to TRUE and propagates the information
	 * upwards the Result-Object Tree
	 *
	 * @return void
	 */
	protected function setNoticesExist() {
		$this->noticesExist = TRUE;
		if ($this->parent !== NULL) {
			$this->parent->setNoticesExist();
		}
	}


	/**
	 * Does the current Result object have Notices, Errors or Warnings? (Recursively)
	 *
	 * @return bool
	 */
	public function hasMessages() {
		return $this->errorsExist || $this->noticesExist || $this->warningsExist;
	}

	/**
	 * Get a list of all Error objects recursively. The result is an array,
	 * where the key is the property path where the error occurred, and the
	 * value is a list of all errors (stored as array)
	 *
	 * @return array<\TYPO3\Flow\Error\Error>
	 * @api
	 */
	public function getFlattenedErrors() {
		$result = array();
		$this->flattenTree('errors', $result);
		return $result;
	}

	/**
	 * Get a list of all Error objects recursively. The result is an array,
	 * where the key is the property path where the error occurred, and the
	 * value is a list of all errors (stored as array)
	 *
	 * @param string $type
	 * @return array<\TYPO3\Flow\Error\Error>
	 * @api
	 */
	public function getFlattenedErrorsOfType($type) {
		$result = array();
		$this->flattenTree('errors', $result, array(), $type);
		return $result;
	}

	/**
	 * Get a list of all Warning objects recursively. The result is an array,
	 * where the key is the property path where the warning occurred, and the
	 * value is a list of all warnings (stored as array)
	 *
	 * @return array<\TYPO3\Flow\Error\Warning>
	 * @api
	 */
	public function getFlattenedWarnings() {
		$result = array();
		$this->flattenTree('warnings', $result);
		return $result;
	}

	/**
	 * Get a list of all Notice objects recursively. The result is an array,
	 * where the key is the property path where the notice occurred, and the
	 * value is a list of all notices (stored as array)
	 *
	 * @return array<\TYPO3\Flow\Error\Notice>
	 * @api
	 */
	public function getFlattenedNotices() {
		$result = array();
		$this->flattenTree('notices', $result);
		return $result;
	}

	/**
	 * Only use internally!
	 * Flatten a tree of Result objects, based on a certain property.
	 *
	 * @param string $propertyName
	 * @param array $result The current result to be flattened
	 * @param array $level The property path in the format array('level1', 'level2', ...) for recursion
	 * @param string $messageTypeFilter If specified only messages implementing the given class name are taken into account
	 * @return void
	 */
	public function flattenTree($propertyName, array &$result, array $level = array(), $messageTypeFilter = NULL) {
		if (count($this->$propertyName) > 0) {
			$propertyPath = implode('.', $level);
			$result[$propertyPath] = $this->filterMessages($this->$propertyName, $messageTypeFilter);
		}
		/** @var Result $subResult */
		foreach ($this->propertyResults as $subPropertyName => $subResult) {
			array_push($level, $subPropertyName);
			$subResult->flattenTree($propertyName, $result, $level, $messageTypeFilter);
			array_pop($level);
		}
	}

	/**
	 * @param Message[] $messages an array of Message instances to filter
	 * @param string $messageTypeFilter If specified only messages implementing the given class name are taken into account
	 * @return array the filtered message instances
	 */
	protected function filterMessages(array $messages, $messageTypeFilter = NULL) {
		if ($messageTypeFilter === NULL) {
			return $messages;
		}
		return array_filter($messages, function(Message $message) use ($messageTypeFilter) {
			return $message instanceof $messageTypeFilter;
		});
	}

	/**
	 * Merge the given Result object into this one.
	 *
	 * @param Result $otherResult
	 * @return void
	 * @api
	 */
	public function merge(Result $otherResult) {
		if ($otherResult->errorsExist) {
			$this->mergeProperty($otherResult, 'getErrors', 'addError');
		}
		if ($otherResult->warningsExist) {
			$this->mergeProperty($otherResult, 'getWarnings', 'addWarning');
		}
		if ($otherResult->noticesExist) {
			$this->mergeProperty($otherResult, 'getNotices', 'addNotice');
		}

		/** @var $subResult Result */
		foreach ($otherResult->getSubResults() as $subPropertyName => $subResult) {
			if (array_key_exists($subPropertyName, $this->propertyResults) && $this->propertyResults[$subPropertyName]->hasMessages()) {
				$this->forProperty($subPropertyName)->merge($subResult);
			} else {
				$this->propertyResults[$subPropertyName] = $subResult;
				$subResult->setParent($this);
			}
		}
	}

	/**
	 * Merge a single property from the other result object.
	 *
	 * @param Result $otherResult
	 * @param string $getterName
	 * @param string $adderName
	 * @return void
	 */
	protected function mergeProperty(Result $otherResult, $getterName, $adderName) {
		foreach ($otherResult->$getterName() as $messageInOtherResult) {
			$this->$adderName($messageInOtherResult);
		}
	}

	/**
	 * Get a list of all sub Result objects available.
	 *
	 * @return array<\TYPO3\Flow\Error\Result>
	 */
	public function getSubResults() {
		return $this->propertyResults;
	}

	/**
	 * Clears the result
	 *
	 * @return void
	 */
	public function clear() {
		$this->errors = array();
		$this->notices = array();
		$this->warnings = array();

		$this->warningsExist = FALSE;
		$this->noticesExist = FALSE;
		$this->errorsExist = FALSE;

		$this->propertyResults = array();
	}
}
