<?php
namespace TYPO3\Flow\Tests\Functional\I18n\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A dummy I18n formatter class
 */
class SampleFormatter implements \TYPO3\Flow\I18n\Formatter\FormatterInterface
{
    /**
     */
    public function format($value, \TYPO3\Flow\I18n\Locale $locale, array $styleProperties = array())
    {
        return $value . '+Formatted42';
    }
}
