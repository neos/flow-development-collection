<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\I18n;

/* *
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
 * A class for replacing placeholders in strings with formatted values.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class FormatResolver {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\I18n\Service
	 */
	protected $localizationService;

	/**
	 * Array of concrete formatters used by this class.
	 *
	 * @var array<\F3\FLOW3\I18n\Formatter\FormatterInterface>
	 */
	protected $formatters;

	/**
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \F3\FLOW3\I18n\Service $localizationService
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectLocalizationService(\F3\FLOW3\I18n\Service $localizationService) {
		$this->localizationService = $localizationService;
	}

	/**
	 * Replaces all placeholders in text with corresponding values.
	 *
	 * A placeholder is a group of elements separated with comma. First element
	 * is required and defines index of value to insert (numeration starts from
	 * 0, and is directly used to access element from $values array). Second
	 * element is a name of formatter to use. It's optional, and if not given,
	 * value will be simply string-casted. Remaining elements are formatter-
	 * specific and they are directly passed to the formatter class.
	 *
	 * Examples of placeholder's syntax:
	 * {0}
	 * {1,number}
	 * {0,date,full}
	 * 
	 * @param string $text String message with placeholder(s)
	 * @param array $values An array of values to replace placeholders with
	 * @param \F3\FLOW3\I18n\Locale $locale Locale to use (NULL for default one)
	 * @return string The $text with placeholders resolved
	 * @throws \F3\FLOW3\I18n\Exception\InvalidFormatPlaceholderException When encountered incorrectly formatted placeholder
	 * @throws \F3\FLOW3\I18n\Exception\IndexOutOfBoundsException When trying to format nonexistent value
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @api
	 */
	public function resolvePlaceholders($text, array $values, \F3\FLOW3\I18n\Locale $locale = NULL) {
		if ($locale === NULL) {
			$locale = $this->localizationService->getDefaultLocale();
		}

		while (($startOfPlaceholder = strpos($text, '{')) !== FALSE) {
			$endOfPlaceholder = strpos($text, '}');

			if ($endOfPlaceholder === FALSE || ($startOfPlaceholder + 1) >= $endOfPlaceholder) {
					// There is no closing bracket, it is placed before the opening bracket, or there is nothing between brackets
				throw new \F3\FLOW3\I18n\Exception\InvalidFormatPlaceholderException('Text provided contains incorrectly formatted placeholders. Please make sure you conform the placeholder\'s syntax.', 1278057790);
			}

			$contentBetweenBrackets = substr($text, $startOfPlaceholder + 1, $endOfPlaceholder - $startOfPlaceholder - 1);
			$placeholderElements = explode(',', $contentBetweenBrackets);

			$valueIndex = (int)$placeholderElements[0];
			if ($valueIndex < 0 || $valueIndex >= count($values)) {
				throw new \F3\FLOW3\I18n\Exception\IndexOutOfBoundsException('Placeholder has incorrect index or not enough values provided. Please make sure you try to access existing values.', 1278057791);
			}

			if (isset($placeholderElements[1])) {
				$formatterName = $placeholderElements[1];
				$formatter = $this->getFormatter($formatterName);
				$formattedPlaceholder = $formatter->format($values[$valueIndex], $locale, array_slice($placeholderElements, 2));
			} else {
					// No formatter defined, just string-cast the value
				$formattedPlaceholder = (string)($values[$valueIndex]);
			}

			$text = str_replace('{' . $contentBetweenBrackets . '}', $formattedPlaceholder, $text);
		}

		return $text;
	}

	/**
	 * Returns instance of concrete formatter.
	 *
	 * Throws exception if there is no formatter for name given.
	 *
	 * @param string $text String message with placeholder(s)
	 * @return \F3\FLOW3\I18n\Formatter\FormatterInterface The concrete formatter class
	 * @throws \F3\FLOW3\I18n\Exception\UnknownFormatterException When formatter for a name given does not exist
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	protected function getFormatter($formatterName) {
		$formatterName = ucfirst($formatterName);

		if (isset($this->formatters[$formatterName])) {
			return $this->formatters[$formatterName];
		}

		try {
			$formatter = $this->objectManager->get('F3\\FLOW3\\I18n\\Formatter\\' . $formatterName . 'Formatter');
		} catch (\F3\FLOW3\Object\Exception\UnknownObjectException $exception) {
			throw new \F3\FLOW3\I18n\Exception\UnknownFormatterException('Could not find formatter for "' . $formatterName . '".', 1278057791);
		}

		return $this->formatters[$formatterName] = $formatter;
	}
}

?>