<?php
namespace Neos\Flow\I18n\Xliff;

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
use Neos\Flow\I18n\Locale;

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
 * @throws Exception\InvalidXliffDataException
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
     * @throws Exception\InvalidXliffDataException
     * @todo Support "approved" attribute
     */
    protected function doParsingFromRoot(\SimpleXMLElement $root)
    {
        $parsedData = [
            'sourceLocale' => new Locale((string)$root->file['source-language'])
        ];

        foreach ($root->file->body->children() as $translationElement) {
            switch ($translationElement->getName()) {
                case 'trans-unit':
                    // If restype would be set, it could be metadata from Gettext to XLIFF conversion (and we don't need this data)
                    if (!isset($translationElement['restype'])) {
                        if (!isset($translationElement['id'])) {
                            throw new Exception\InvalidXliffDataException('A trans-unit tag without id attribute was found, validate your XLIFF files.', 1329399257);
                        }
                        $parsedData['translationUnits'][(string)$translationElement['id']][0] = [
                            'source' => (string)$translationElement->source,
                            'target' => (string)$translationElement->target,
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
                                    'target' => (string)$translationPluralForm->target,
                                ];
                            }
                        }

                        if (!empty($parsedTranslationElement)) {
                            if (isset($translationElement->{'trans-unit'}[0]['id'])) {
                                $id = (string)$translationElement->{'trans-unit'}[0]['id'];
                                $id = substr($id, 0, strpos($id, '['));
                            } else {
                                throw new Exception\InvalidXliffDataException('A trans-unit tag without id attribute was found, validate your XLIFF files.', 1329399258);
                            }

                            $parsedData['translationUnits'][$id] = $parsedTranslationElement;
                        }
                    }
                    break;
            }
        }

        return $parsedData;
    }
}
