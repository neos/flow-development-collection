<?php
namespace TYPO3\Flow\Tests\Functional\I18n\Fixtures;

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
