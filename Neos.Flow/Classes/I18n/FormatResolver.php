<?php
namespace Neos\Flow\I18n;

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
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\I18n\Formatter\FormatterInterface;
use Neos\Flow\I18n;

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
class FormatResolver
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var I18n\Service
     */
    protected $localizationService;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * Array of concrete formatters used by this class.
     *
     * @var array<FormatterInterface>
     */
    protected $formatters;

    /**
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param I18n\Service $localizationService
     * @return void
     */
    public function injectLocalizationService(I18n\Service $localizationService)
    {
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
     * @param Locale $locale Locale to use (NULL for default one)
     * @return string The $text with placeholders resolved
     * @throws Exception\InvalidFormatPlaceholderException When encountered incorrectly formatted placeholder
     * @throws Exception\IndexOutOfBoundsException When trying to format nonexistent value
     * @api
     */
    public function resolvePlaceholders($textWithPlaceholders, array $arguments, Locale $locale = null)
    {
        if ($locale === null) {
            $locale = $this->localizationService->getConfiguration()->getDefaultLocale();
        }

        $lastPlaceHolderAt = 0;
        while ($lastPlaceHolderAt < strlen($textWithPlaceholders) && ($startOfPlaceholder = strpos($textWithPlaceholders, '{', $lastPlaceHolderAt)) !== false) {
            $endOfPlaceholder = strpos($textWithPlaceholders, '}', $lastPlaceHolderAt);
            $startOfNextPlaceholder = strpos($textWithPlaceholders, '{', $startOfPlaceholder + 1);

            if ($endOfPlaceholder === false || ($startOfPlaceholder + 1) >= $endOfPlaceholder || ($startOfNextPlaceholder !== false && $startOfNextPlaceholder < $endOfPlaceholder)) {
                // There is no closing bracket, or it is placed before the opening bracket, or there is nothing between brackets
                throw new Exception\InvalidFormatPlaceholderException('Text provided contains incorrectly formatted placeholders. Please make sure you conform the placeholder\'s syntax.', 1278057790);
            }

            $contentBetweenBrackets = substr($textWithPlaceholders, $startOfPlaceholder + 1, $endOfPlaceholder - $startOfPlaceholder - 1);
            $placeholderElements = explode(',', str_replace(' ', '', $contentBetweenBrackets));

            $valueIndex = $placeholderElements[0];
            if (!array_key_exists($valueIndex, $arguments)) {
                throw new Exception\IndexOutOfBoundsException('Placeholder "' . $valueIndex . '" was not provided, make sure you provide values for every placeholder.', 1278057791);
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
            $lastPlaceHolderAt = $startOfPlaceholder + strlen($formattedPlaceholder);
        }

        return $textWithPlaceholders;
    }

    /**
     * Returns instance of concrete formatter.
     *
     * The type provided has to be either a name of existing class placed in
     * I18n\Formatter namespace or a fully qualified class name;
     * in both cases implementing this' package's FormatterInterface.
     * For example, when $formatterName is 'number',
     * the I18n\Formatter\NumberFormatter class has to exist; when
     * $formatterName is 'Acme\Foobar\I18nFormatter\SampleFormatter', this class
     * must exist and implement I18n\Formatter\FormatterInterface.
     *
     * Throws exception if there is no formatter for name given or one could be
     * retrieved but does not satisfy the FormatterInterface.
     *
     * @param string $formatterType Either one of the built-in formatters or fully qualified formatter class name
     * @return Formatter\FormatterInterface The concrete formatter class
     * @throws Exception\UnknownFormatterException When formatter for a name given does not exist
     * @throws Exception\InvalidFormatterException When formatter for a name given does not exist
     */
    protected function getFormatter($formatterType)
    {
        $foundFormatter = false;
        $formatterType = ltrim($formatterType, '\\');

        if (isset($this->formatters[$formatterType])) {
            $foundFormatter = $this->formatters[$formatterType];
        }

        if ($foundFormatter === false) {
            if ($this->objectManager->isRegistered($formatterType)) {
                $possibleClassName = $formatterType;
            } else {
                $possibleClassName = sprintf('Neos\Flow\I18n\Formatter\%sFormatter', ucfirst($formatterType));
                if (!$this->objectManager->isRegistered($possibleClassName)) {
                    throw new Exception\UnknownFormatterException('Could not find formatter for "' . $formatterType . '".', 1278057791);
                }
            }
            if (!$this->reflectionService->isClassImplementationOf($possibleClassName, Formatter\FormatterInterface::class)) {
                throw new Exception\InvalidFormatterException(sprintf('The resolved internationalization formatter class name "%s" does not implement "%s" as required.', $possibleClassName, FormatterInterface::class), 1358162557);
            }
            $foundFormatter = $this->objectManager->get($possibleClassName);
        }

        $this->formatters[$formatterType] = $foundFormatter;
        return $foundFormatter;
    }
}
