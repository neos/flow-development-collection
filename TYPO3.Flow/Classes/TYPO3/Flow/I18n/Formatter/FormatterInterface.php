<?php
namespace TYPO3\Flow\I18n\Formatter;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\I18n\Locale;

/**
 * An interface for formatters.
 *
 * @api
 */
interface FormatterInterface
{
    /**
     * Formats provided value using optional style properties
     *
     * @param mixed $value Formatter-specific variable to format (can be integer, \DateTime, etc)
     * @param Locale $locale Locale to use
     * @param array $styleProperties Integer-indexed array of formatter-specific style properties (can be empty)
     * @return string String representation of $value provided, or (string)$value
     * @api
     */
    public function format($value, Locale $locale, array $styleProperties = []);
}
