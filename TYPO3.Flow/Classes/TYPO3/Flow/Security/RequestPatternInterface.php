<?php
namespace TYPO3\Flow\Security;

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
 * Contract for a request pattern.
 *
 */
interface RequestPatternInterface
{
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
