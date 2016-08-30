<?php
namespace TYPO3\Flow\I18n\Xliff\Model;

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
use TYPO3\Flow\I18n\Locale;
use TYPO3\Flow\Log\LoggerInterface;

/**
 * A model representing data from an XLIFF file object that may be distributed
 * over several documents in different versions.
 *
 * Please note that plural forms for particular translation unit are accessed
 * with integer index (and not string like 'zero', 'one', 'many' etc). This is
 * because they are indexed such way in XLIFF files in order to not break tools'
 * support.
 *
 * There are very few XLIFF editors, but they are nice Gettext's .po editors
 * available. Gettext supports plural forms, but it indexes them using integer
 * numbers. Leaving it this way in .xlf files, makes it possible to easily convert
 * them to .po (e.g. using xliff2po from Translation Toolkit), edit with Poedit,
 * and convert back to .xlf without any information loss (using po2xliff).
 */
class FileAdapter
{
    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $i18nLogger;


    /**
     * @var array
     */
    protected $fileData = [];

    /**
     * @var Locale
     */
    protected $requestedLocale;


    /**
     * @param array $fileData
     * @param Locale $requestedLocale
     */
    public function __construct(array $fileData, Locale $requestedLocale)
    {
        $this->fileData = $fileData;
        $this->requestedLocale = $requestedLocale;
    }


    /**
     * Returns translated label ("target" tag in XLIFF) from source-target
     * pair where "source" tag equals to $source parameter.
     *
     * @param string $source Label in original language ("source" tag in XLIFF)
     * @param integer $pluralFormIndex Index of plural form to use (starts with 0)
     * @return mixed Translated label or FALSE on failure
     */
    public function getTargetBySource($source, $pluralFormIndex = 0)
    {
        if (!isset($this->fileData['translationUnits'])) {
            $this->i18nLogger->log(sprintf('No trans-unit elements were found in "%s". This is allowed per specification, but no translation can be applied then.', $this->sourcePath), LOG_DEBUG);
            return false;
        }
        foreach ($this->fileData['translationUnits'] as $translationUnit) {
            // $source is always singular (or only) form, so compare with index 0
            if (!isset($translationUnit[0]) || $translationUnit[0]['source'] !== $source) {
                continue;
            }

            if (count($translationUnit) <= $pluralFormIndex) {
                $this->i18nLogger->log('The plural form index "' . $pluralFormIndex . '" for the source translation "' . $source . '"  in ' . $this->sourcePath . ' is not available.', LOG_DEBUG);
                return false;
            }

            return $translationUnit[$pluralFormIndex]['target'] ?: false;
        }

        return false;
    }

    /**
     * Returns translated label ("target" tag in XLIFF) for the id given.
     * Id is compared with "id" attribute of "trans-unit" tag (see XLIFF
     * specification for details).
     *
     * @param string $transUnitId The "id" attribute of "trans-unit" tag in XLIFF
     * @param integer $pluralFormIndex Index of plural form to use (starts with 0)
     * @return mixed Translated label or FALSE on failure
     */
    public function getTargetByTransUnitId($transUnitId, $pluralFormIndex = 0)
    {
        if (!isset($this->fileData['translationUnits'][$transUnitId])) {
            $this->i18nLogger->log('No trans-unit element with the id "' . $transUnitId . '" was found in ' . $this->fileData['fileIdentifier'] . '. Either this translation has been removed or the id in the code or template referring to the translation is wrong.', LOG_DEBUG);
            return false;
        }

        if (!isset($this->fileData['translationUnits'][$transUnitId][$pluralFormIndex])) {
            $this->i18nLogger->log('The plural form index "' . $pluralFormIndex . '" for the trans-unit element with the id "' . $transUnitId . '" in ' . $this->fileData['fileIdentifier'] . ' is not available.', LOG_DEBUG);
            return false;
        }

        if ($this->fileData['translationUnits'][$transUnitId][$pluralFormIndex]['target']) {
            return $this->fileData['translationUnits'][$transUnitId][$pluralFormIndex]['target'];
        } elseif ($this->requestedLocale->getLanguage() === $this->fileData['sourceLocale']->getLanguage()) {
            return $this->fileData['translationUnits'][$transUnitId][$pluralFormIndex]['source'] ?: false;
        } else {
            $this->i18nLogger->log('The target translation was empty and the source translation language (' . $this->fileData['sourceLocale']->getLanguage() . ') does not match the current locale (' . $this->requestedLocale->getLanguage() . ') for the trans-unit element with the id "' . $transUnitId . '" in ' . $this->fileData['fileIdentifier'], LOG_DEBUG);
            return false;
        }
    }
}
