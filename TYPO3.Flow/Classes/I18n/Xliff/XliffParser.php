<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\I18n\Xliff;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A class which parses XLIFF file to simple but useful array representation.
 *
 * As for now, this class supports only basic XLIFF specification.
 * - it uses only first "file" tag
 * - it does support groups only as defined in [2] in order to support plural
 *   forms
 * - reads only "source" and "target" in "trans-unit" tags
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @see http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html [1]
 * @see http://docs.oasis-open.org/xliff/v1.2/xliff-profile-po/xliff-profile-po-1.2-cd02.html#s.detailed_mapping.tu [2]
 */
class XliffParser extends \F3\FLOW3\I18n\Xml\AbstractXmlParser {

	/**
	 * Returns array representation of XLIFF data, starting from a root node.
	 *
	 * @param \SimpleXMLElement $root A root node
	 * @return array An array representing parsed XLIFF
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @todo: Support "approved" attribute
	 */
	protected function doParsingFromRoot(\SimpleXMLElement $root) {
		$parsedData = array();
		$bodyOfFileTag = $root->file->body;

		foreach ($bodyOfFileTag->children() as $translationElement) {
			if ($translationElement->getName() === 'trans-unit' && !isset($translationElement['restype'])) {
					// If restype would be set, it could be metadata from Gettext to XLIFF conversion (and we don't need this data)

				$parsedData[(string)$translationElement['id']][0] = array(
					'source' => (string)$translationElement->source,
					'target' => (string)$translationElement->target,
				);
			} elseif ($translationElement->getName() === 'group' && isset($translationElement['restype']) && (string)$translationElement['restype'] === 'x-gettext-plurals') {
					// This is a translation with plural forms
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
					if (isset($translationElement['id'])) {
						$id = (string)$translationElement['id'];
					} else {
						$id = (string)($translationElement->{'trans-unit'}[0]['id']);
						$id = substr($id, 0, strpos($id, '['));
					}

					$parsedData[$id] = $parsedTranslationElement;
				}
			}
		}

		return $parsedData;
	}
}

?>