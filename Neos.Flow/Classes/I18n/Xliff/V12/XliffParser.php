<?php
namespace Neos\Flow\I18n\Xliff\V12;

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
use Neos\Flow\I18n\AbstractXmlParser;
use Neos\Flow\I18n\Exception\InvalidXmlFileException;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Xliff\Exception\InvalidXliffDataException;

/**
 * A class which parses XLIFF file to simple but useful array representation.
 *
 * As for now, this class supports only basic XLIFF specification.
 * - it uses only first "file" tag
 * - it does support groups only as defined in [2] in order to support plural
 *   forms
 * - reads only "source" and "target" in "trans-unit" tags
 *
 * @Flow\Scope("singleton")
 * @throws InvalidXliffDataException
 * @see http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html [1]
 * @see http://docs.oasis-open.org/xliff/v1.2/xliff-profile-po/xliff-profile-po-1.2-cd02.html#s.detailed_mapping.tu [2]
 */
class XliffParser extends AbstractXmlParser
{
    /**
     * Returns array representation of XLIFF data, starting from a root node.
     *
     * @param \SimpleXMLElement $root A root node
     * @return array An array representing parsed XLIFF
     * @throws InvalidXliffDataException
     * @todo Support "approved" attribute
     */
    protected function doParsingFromRoot(\SimpleXMLElement $root): array
    {
        $parsedData = [];

        foreach ($root->children() as $file) {
            $parsedData[] = $this->getFileData($file);
        }

        return $parsedData;
    }

    /**
     * @param \SimpleXMLElement $file
     * @return array
     * @throws InvalidXliffDataException
     */
    protected function getFileData(\SimpleXMLElement $file): array
    {
        /**
         * If an XLIFF file has no target-language set the source element of a trans-unit is
         * used to fill the target element, if the target element is left out (as is common
         * for "source" XLIFF files.)
         *
         * @param \SimpleXMLElement $element
         * @return string
         */
        $getTarget = function (\SimpleXMLElement $element) use ($file): string {
            $hasTargetLanguage = ((string)$file['target-language']) !== '';
            if ($hasTargetLanguage) {
                return (string)$element->target;
            }
            return (string)($element->target ?? $element->source);
        };

        $parsedFile = [
            'sourceLocale' => new Locale((string)$file['source-language'])
        ];
        foreach ($file->body->children() as $translationElement) {
            switch ($translationElement->getName()) {
                case 'trans-unit':
                    // If restype would be set, it could be metadata from Gettext to XLIFF conversion (and we don't need this data)
                    if (!isset($translationElement['restype'])) {
                        if (!isset($translationElement['id'])) {
                            throw new InvalidXliffDataException('A trans-unit tag without id attribute was found, validate your XLIFF files.', 1329399257);
                        }
                        $parsedFile['translationUnits'][(string)$translationElement['id']][0] = [
                            'source' => (string)$translationElement->source,
                            'target' => $getTarget($translationElement),
                        ];
                    }
                    break;
                case 'group':
                    if (isset($translationElement['restype']) && (string)$translationElement['restype'] === 'x-gettext-plurals') {
                        $parsedTranslationElement = [];
                        foreach ($translationElement->children() as $translationPluralForm) {
                            if ($translationPluralForm->getName() === 'trans-unit') {
                                // When using plural forms, ID looks like this: 1[0], 1[1] etc
                                $formIndex = substr((string)$translationPluralForm['id'], strpos((string)$translationPluralForm['id'], '[') + 1, -1);

                                $parsedTranslationElement[(int)$formIndex] = [
                                    'source' => (string)$translationPluralForm->source,
                                    'target' => $getTarget($translationPluralForm),
                                ];
                            }
                        }

                        if (!empty($parsedTranslationElement)) {
                            if (isset($translationElement->{'trans-unit'}[0]['id'])) {
                                $id = (string)$translationElement->{'trans-unit'}[0]['id'];
                                $id = substr($id, 0, strpos($id, '['));
                            } else {
                                throw new InvalidXliffDataException('A trans-unit tag without id attribute was found, validate your XLIFF files.', 1329399258);
                            }

                            $parsedFile['translationUnits'][$id] = $parsedTranslationElement;
                        }
                    }
                    break;
            }
        }

        return $parsedFile;
    }

    /**
     * @param $sourcePath
     * @param $fileOffset
     * @return array|null
     * @throws InvalidXliffDataException
     * @throws InvalidXmlFileException
     */
    public function getFileDataFromDocument($sourcePath, $fileOffset)
    {
        $document = $this->getRootNode($sourcePath);
        $files = $document->children();
        if (isset($files[$fileOffset])) {
            return $this->getFileData($files[$fileOffset]);
        }

        return null;
    }
}
