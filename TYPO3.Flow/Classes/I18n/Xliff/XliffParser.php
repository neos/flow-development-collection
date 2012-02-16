<?php
namespace TYPO3\FLOW3\I18n\Xliff;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A class which parses XLIFF file to simple but useful array representation.
 *
 * As for now, this class supports only basic XLIFF specification.
 * - it uses only first "file" tag
 * - it does support groups only as defined in [2] in order to support plural
 *   forms
 * - reads only "source" and "target" in "trans-unit" tags
 *
 * @FLOW3\Scope("singleton")
 * @throws Exception\InvalidXliffDataException
 * @see http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html [1]
 * @see http://docs.oasis-open.org/xliff/v1.2/xliff-profile-po/xliff-profile-po-1.2-cd02.html#s.detailed_mapping.tu [2]
 */
class XliffParser extends \TYPO3\FLOW3\I18n\AbstractXmlParser {

	/**
	 * Returns array representation of XLIFF data, starting from a root node.
	 *
	 * @param \SimpleXMLElement $root A root node
	 * @return array An array representing parsed XLIFF
	 * @todo Support "approved" attribute
	 */
	protected function doParsingFromRoot(\SimpleXMLElement $root) {
		$parsedData = array(
			'sourceLocale' => new \TYPO3\FLOW3\I18n\Locale((string)$root->file['source-language'])
		);

		foreach ($root->file->body->children() as $translationElement) {
			switch ($translationElement->getName()) {
				case 'trans-unit':
						// If restype would be set, it could be metadata from Gettext to XLIFF conversion (and we don't need this data)
					if (!isset($translationElement['restype'])) {
						if (!isset($translationElement['id'])) {
							throw new Exception\InvalidXliffDataException('A trans-unit tag without id attribute was found, validate your XLIFF files.', 1329399257);
						}
						$parsedData['translationUnits'][(string)$translationElement['id']][0] = array(
							'source' => (string)$translationElement->source,
							'target' => (string)$translationElement->target,
						);
					}
					break;
				case 'group':
					if (isset($translationElement['restype']) && (string)$translationElement['restype'] === 'x-gettext-plurals') {
						$parsedTranslationElement = array();
						foreach ($translationElement->children() as $translationPluralForm) {
							if ($translationPluralForm->getName() === 'trans-unit') {
									// When using plural forms, ID looks like this: 1[0], 1[1] etc
								$formIndex = substr((string)$translationPluralForm['id'], strpos((string)$translationPluralForm['id'], '[') + 1, -1);

								$parsedTranslationElement[(int)$formIndex] = array(
									'source' => (string)$translationPluralForm->source,
									'target' => (string)$translationPluralForm->target,
								);
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

?>