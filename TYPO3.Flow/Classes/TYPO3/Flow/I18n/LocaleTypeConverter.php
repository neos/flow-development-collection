<?php
namespace TYPO3\Flow\I18n;

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
use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;
use TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter;

/**
 * Converter which transforms strings to a Locale object.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class LocaleTypeConverter extends AbstractTypeConverter
{
    /**
     * @var string
     */
    protected $sourceTypes = ['string'];

    /**
     * @var string
     */
    protected $targetType = 'TYPO3\Flow\I18n\Locale';

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * Convert the given $source (a locale identifier string) to $targetType (Locale)
     *
     * @param string $source the locale string
     * @param Locale $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return Locale
     * @api
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        return new Locale($source);
    }
}
