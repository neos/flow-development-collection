<?php
namespace TYPO3\Flow\Security;

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
 * Contract for a request pattern.
 *
 */
interface RequestPatternInterface {

	/**
	 * Returns the set pattern
	 *
	 * @return string The set pattern
	 */
	public function getPattern();

	/**
	 * Sets the pattern (match) configuration
	 *
	 * @param object $pattern The pattern (match) configuration
	 * @return void
	 */
	public function setPattern($pattern);

	/**
	 * Matches a \TYPO3\Flow\Mvc\RequestInterface against its set pattern rules
	 *
	 * @param \TYPO3\Flow\Mvc\RequestInterface $request The request that should be matched
	 * @return boolean TRUE if the pattern matched, FALSE otherwise
	 */
	public function matchRequest(\TYPO3\Flow\Mvc\RequestInterface $request);
}
