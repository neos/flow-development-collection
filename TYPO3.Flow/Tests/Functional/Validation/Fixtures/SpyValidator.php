<?php
namespace TYPO3\Flow\Tests\Functional\Validation\Fixtures;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * Spy Validator for functional tests
 * This validator checks that it was executed.
 *
 * @Flow\Scope("singleton")
 */
class SpyValidator extends \TYPO3\Flow\Validation\Validator\AbstractValidator
{
	/**
	 * @var int How often this validator was executed in total
	 */
	protected $executionCount = 0;

	/**
	 * Check if $value is valid. If it is not valid, needs to add an error
	 * to Result.
	 *
	 * @param mixed $value
	 * @return void
	 * @throws \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException if invalid validation options have been specified in the constructor
	 */
	protected function isValid($value)
	{
		$this->executionCount++;
	}

	/**
	 * @return bool TRUE if this validator was executed
	 */
	public function wasExecuted()
	{
		return $this->executionCount > 0;
	}

	/**
	 * @return int How often this validator was executed
	 */
	public function executionCount()
	{
		return $this->executionCount;
	}
}
