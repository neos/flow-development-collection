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
     * Note: This constructor is commented by intention due to the way PHPs inheritance works.
     * But request pattern implementations relying on custom options should implement it
     *
     * @param array $options Additional configuration options
     * @return void
     */
    // public function __construct(array $options);

    /**
     * Returns the set pattern
     *
     * @return string The set pattern
     * @deprecated since 3.1 this is not used - use options instead (@see __construct())
     */
    // public function getPattern();

    /**
     * Sets the pattern (match) configuration
     *
     * @param object $pattern The pattern (match) configuration
     * @return void
     * @deprecated since 3.1 specify options using the constructor instead
     */
    // public function setPattern($pattern);

    /**
     * Matches a \TYPO3\Flow\Mvc\RequestInterface against its set pattern rules
     *
     * @param \TYPO3\Flow\Mvc\RequestInterface $request The request that should be matched
     * @return boolean TRUE if the pattern matched, FALSE otherwise
     */
    public function matchRequest(\TYPO3\Flow\Mvc\RequestInterface $request);
}
