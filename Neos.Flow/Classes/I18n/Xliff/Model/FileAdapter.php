<?php
namespace Neos\Flow\I18n\Xliff\Model;

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
use Neos\Flow\I18n\Locale;
use Psr\Log\LoggerInterface;

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
     * @Flow\Inject(name="Neos.Flow:I18nLogger")
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
        if (!isset($this->fileData['translationUnits'])) {
            $this->fileData['translationUnits'] = [];
        }
        $this->requestedLocale = $requestedLocale;
    }


    /**
     * Returns translated label ("target" tag in XLIFF) from source-target
     * pair where "source" tag equals to $source parameter.
     *
     * @param string $source Label in original language ("source" tag in XLIFF)
     * @param integer $pluralFormIndex Index of plural form to use (starts with 0)
     * @return mixed Translated label or false on failure
     */
    public function getTargetBySource($source, $pluralFormIndex = 0)
    {
        if (empty($this->fileData['translationUnits'])) {
            $this->i18nLogger->debug(sprintf('No trans-unit elements were found in "%s". This is allowed per specification, but no translation can be applied then.', $this->fileData['fileIdentifier']));

            return false;
        }
        foreach ($this->fileData['translationUnits'] as $translationUnit) {
            // $source is always singular (or only) form, so compare with index 0
            if (!isset($translationUnit[0]) || $translationUnit[0]['source'] !== $source) {
                continue;
            }

            if (count($translationUnit) <= $pluralFormIndex) {
                $this->i18nLogger->debug('The plural form index "' . $pluralFormIndex . '" for the source translation "' . $source . '"  in ' . $this->fileData['fileIdentifier'] . ' is not available.');

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
     * @return mixed Translated label or false on failure
     */
    public function getTargetByTransUnitId($transUnitId, $pluralFormIndex = 0)
    {
        if (!isset($this->fileData['translationUnits'][$transUnitId])) {
            $this->i18nLogger->debug('No trans-unit element with the id "' . $transUnitId . '" was found in ' . $this->fileData['fileIdentifier'] . '. Either this translation has been removed or the id in the code or template referring to the translation is wrong.');
            return false;
        }

        if (!isset($this->fileData['translationUnits'][$transUnitId][$pluralFormIndex])) {
            $this->i18nLogger->debug('The plural form index "' . $pluralFormIndex . '" for the trans-unit element with the id "' . $transUnitId . '" in ' . $this->fileData['fileIdentifier'] . ' is not available.');
            return false;
        }

        if (!isset($this->fileData['translationUnits'][$transUnitId][$pluralFormIndex]['target'])) {
            $this->i18nLogger->log(
                'The target translation was empty for the trans-unit element with the id "' . $transUnitId . '" and the plural form index "' . $pluralFormIndex . '" in ' . $this->fileData['fileIdentifier'],
                LOG_DEBUG
            );
        }

        return $this->fileData['translationUnits'][$transUnitId][$pluralFormIndex]['target'];
    }

    /**
     * @return array
     */
    public function getTranslationUnits(): array
    {
        return $this->fileData['translationUnits'];
    }
}
