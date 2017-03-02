<?php
namespace Neos\Flow\I18n\TranslationProvider;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\I18n\Locale;

/**
 * An interface for providers of translation labels (messages).
 *
 * Concrete implementation may throw an UnsupportedTranslationMethodException
 * if particular method is not available / implemented.
 *
 */
interface TranslationProviderInterface
{
    /**
     * Returns translated label of $originalLabel from a file defined by $sourceName.
     *
     * Chooses particular form of label if available and defined in $pluralForm.
     *
     * @param string $originalLabel Label used as a key in order to find translation
     * @param Locale $locale Locale to use
     * @param string $pluralForm One of RULE constants of PluralsReader
     * @param string $sourceName Name of file with translations, base path is $packageKey/Resources/Private/Locale/Translations/
     * @param string $packageKey Key of the package containing the source file
     * @return mixed Translated label or FALSE on failure
     */
    public function getTranslationByOriginalLabel($originalLabel, Locale $locale, $pluralForm = null, $sourceName = 'Main', $packageKey = 'Neos.Flow');

    /**
     * Returns label for a key ($labelId) from a file defined by $sourceName.
     *
     * Chooses particular form of label if available and defined in $pluralForm.
     *
     * @param string $labelId Key used to find translated label
     * @param Locale $locale Locale to use
     * @param string $pluralForm One of RULE constants of PluralsReader
     * @param string $sourceName Name of file with translations, base path is $packageKey/Resources/Private/Locale/Translations/
     * @param string $packageKey Key of the package containing the source file
     * @return mixed Translated label or FALSE on failure
     */
    public function getTranslationById($labelId, Locale $locale, $pluralForm = null, $sourceName = 'Main', $packageKey = 'Neos.Flow');
}
