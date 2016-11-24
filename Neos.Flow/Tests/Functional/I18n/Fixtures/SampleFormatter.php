<?php
namespace Neos\Flow\Tests\Functional\I18n\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Formatter\FormatterInterface;
use Neos\Flow\I18n;

/**
 * A dummy I18n formatter class
 */
class SampleFormatter implements FormatterInterface
{
    /**
     * @param mixed $value
     * @param I18n\Locale $locale
     * @param array $styleProperties
     * @return string
     */
    public function format($value, I18n\Locale $locale, array $styleProperties = [])
    {
        return $value . '+Formatted42';
    }
}
