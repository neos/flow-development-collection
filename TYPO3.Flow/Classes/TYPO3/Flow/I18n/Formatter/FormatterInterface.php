<?php
namespace TYPO3\Flow\I18n\Formatter;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
     * @param \TYPO3\Flow\I18n\Locale $locale Locale to use
     * @param array $styleProperties Integer-indexed array of formatter-specific style properties (can be empty)
     * @return string String representation of $value provided, or (string)$value
     * @api
     */
    public function format($value, \TYPO3\Flow\I18n\Locale $locale, array $styleProperties = array());
}
