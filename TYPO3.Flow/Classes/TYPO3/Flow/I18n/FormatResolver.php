<?php
namespace TYPO3\Flow\I18n;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A class for replacing placeholders in strings with formatted values.
 *
 * Placeholders have following syntax:
 * {id[,name[,attribute1[,attribute2...]]]}
 *
 * Where 'id' is an index of argument to insert in place of placeholder, an
 * optional 'name' is a name of formatter to use for formatting the argument
 * (if no name given, provided argument will be just string-casted), and
 * optional attributes are strings directly passed to the formatter (what they
 * do depends on concrete formatter which is being used).
 *
 * Examples:
 * {0}
 * {0,number,decimal}
 * {1,datetime,time,full}
 *
 * @Flow\Scope("singleton")
 * @api
 */
class FormatResolver {

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\Flow\I18n\Service
	 */
	protected $localizationService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * Array of concrete formatters used by this class.
	 *
	 * @var array<\TYPO3\Flow\I18n\Formatter\FormatterInterface>
	 */
	protected $formatters;

	/**
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\Flow\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \TYPO3\Flow\I18n\Service $localizationService
	 * @return void
	 */
	public function injectLocalizationService(\TYPO3\Flow\I18n\Service $localizationService) {
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
	 * @param string $textWithPlaceholders String message with placeholder(s)
	 * @param array $arguments An array of values to replace placeholders with
	 * @param \TYPO3\Flow\I18n\Locale $locale Locale to use (NULL for default one)
	 * @return string The $text with placeholders resolved
	 * @throws \TYPO3\Flow\I18n\Exception\InvalidFormatPlaceholderException When encountered incorrectly formatted placeholder
	 * @throws \TYPO3\Flow\I18n\Exception\IndexOutOfBoundsException When trying to format nonexistent value
	 * @api
	 */
	public function resolvePlaceholders($textWithPlaceholders, array $arguments, \TYPO3\Flow\I18n\Locale $locale = NULL) {
		if ($locale === NULL) {
			$locale = $this->localizationService->getConfiguration()->getDefaultLocale();
		}

		while (($startOfPlaceholder = strpos($textWithPlaceholders, '{')) !== FALSE) {
			$endOfPlaceholder = strpos($textWithPlaceholders, '}');
			$startOfNextPlaceholder = strpos($textWithPlaceholders, '{', $startOfPlaceholder + 1);

			if ($endOfPlaceholder === FALSE || ($startOfPlaceholder + 1) >= $endOfPlaceholder || ($startOfNextPlaceholder !== FALSE && $startOfNextPlaceholder < $endOfPlaceholder)) {
					// There is no closing bracket, or it is placed before the opening bracket, or there is nothing between brackets
				throw new \TYPO3\Flow\I18n\Exception\InvalidFormatPlaceholderException('Text provided contains incorrectly formatted placeholders. Please make sure you conform the placeholder\'s syntax.', 1278057790);
			}

			$contentBetweenBrackets = substr($textWithPlaceholders, $startOfPlaceholder + 1, $endOfPlaceholder - $startOfPlaceholder - 1);
			$placeholderElements = explode(',', str_replace(' ', '', $contentBetweenBrackets));

			$valueIndex = $placeholderElements[0];
			if (!array_key_exists($valueIndex, $arguments)) {
				throw new \TYPO3\Flow\I18n\Exception\IndexOutOfBoundsException('Placeholder "' . $valueIndex . '" was not provided, make sure you provide values for every placeholder.', 1278057791);
			}

			if (isset($placeholderElements[1])) {
				$formatterName = $placeholderElements[1];
				$formatter = $this->getFormatter($formatterName);
				$formattedPlaceholder = $formatter->format($arguments[$valueIndex], $locale, array_slice($placeholderElements, 2));
			} else {
					// No formatter defined, just string-cast the value
				$formattedPlaceholder = (string)($arguments[$valueIndex]);
			}

			$textWithPlaceholders = str_replace('{' . $contentBetweenBrackets . '}', $formattedPlaceholder, $textWithPlaceholders);
		}

		return $textWithPlaceholders;
	}

	/**
	 * Returns instance of concrete formatter.
	 *
	 * The type provided has to be either a name of existing class placed in
	 * \TYPO3\Flow\I18n\Formatter namespace or a fully qualified class name;
	 * in both cases implementing this' package's FormatterInterface.
	 * For example, when $formatterName is 'number',
	 * the \TYPO3\Flow\I18n\Formatter\NumberFormatter class has to exist; when
	 * $formatterName is 'Acme\Foobar\I18nFormatter\SampleFormatter', this class
	 * must exist and implement TYPO3\Flow\I18n\Formatter\FormatterInterface.
	 *
	 * Throws exception if there is no formatter for name given or one could be
	 * retrieved but does not satisfy the FormatterInterface.
	 *
	 * @param string $formatterType Either one of the built-in formatters or fully qualified formatter class name
	 * @return \TYPO3\Flow\I18n\Formatter\FormatterInterface The concrete formatter class
	 * @throws \TYPO3\Flow\I18n\Exception\UnknownFormatterException When formatter for a name given does not exist
	 * @throws \TYPO3\Flow\I18n\Exception\InvalidFormatterException When formatter for a name given does not exist
	 */
	protected function getFormatter($formatterType) {
		$foundFormatter = FALSE;
		$formatterType = ltrim($formatterType, '\\');

		if (isset($this->formatters[$formatterType])) {
			$foundFormatter = $this->formatters[$formatterType];
		}

		if ($foundFormatter === FALSE) {
			if ($this->objectManager->isRegistered($formatterType)) {
				$possibleClassName = $formatterType;
			} else {
				$possibleClassName = sprintf('TYPO3\Flow\I18n\Formatter\%sFormatter', ucfirst($formatterType));
				if (!$this->objectManager->isRegistered($possibleClassName)) {
					throw new \TYPO3\Flow\I18n\Exception\UnknownFormatterException('Could not find formatter for "' . $formatterType . '".', 1278057791);
				}
			}
			if (!$this->reflectionService->isClassImplementationOf($possibleClassName, 'TYPO3\Flow\I18n\Formatter\FormatterInterface')) {
				throw new \TYPO3\Flow\I18n\Exception\InvalidFormatterException('The resolved internationalization formatter class name "' . $possibleClassName . '" does not implement "TYPO3\Flow\I18n\Formatter\FormatterInterface" as required.', 1358162557);
			}
			$foundFormatter = $this->objectManager->get($possibleClassName);
		}

		$this->formatters[$formatterType] = $foundFormatter;
		return $foundFormatter;
	}
}
