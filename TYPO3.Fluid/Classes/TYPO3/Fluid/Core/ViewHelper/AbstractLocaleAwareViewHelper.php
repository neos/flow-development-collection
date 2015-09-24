<?php
namespace TYPO3\Fluid\Core\ViewHelper;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Fluid\Core\ViewHelper\Exception\InvalidVariableException;
use TYPO3\Flow\I18n;

/**
 * Abstract view helper with locale awareness.
 *
 * @api
 */
abstract class AbstractLocaleAwareViewHelper extends AbstractViewHelper
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\I18n\Service
     */
    protected $localizationService;

    /**
     * Constructor
     *
     * @api
     */
    public function __construct()
    {
        $this->registerArgument('forceLocale', 'mixed', 'Whether if, and what, Locale should be used. May be boolean, string or \TYPO3\Flow\I18n\Locale', false);
    }

    /**
     * Get the locale to use for all locale specific functionality.
     *
     * @throws InvalidVariableException
     * @return I18n\Locale The locale to use or NULL if locale should not be used
     */
    protected function getLocale()
    {
        if (!$this->hasArgument('forceLocale')) {
            return null;
        }
        $forceLocale = $this->arguments['forceLocale'];
        $useLocale = null;
        if ($forceLocale instanceof I18n\Locale) {
            $useLocale = $forceLocale;
        } elseif (is_string($forceLocale)) {
            try {
                $useLocale = new I18n\Locale($forceLocale);
            } catch (I18n\Exception $exception) {
                throw new InvalidVariableException('"' . $forceLocale . '" is not a valid locale identifier.', 1342610148, $exception);
            }
        } elseif ($forceLocale === true) {
            $useLocale = $this->localizationService->getConfiguration()->getCurrentLocale();
        }

        return $useLocale;
    }
}
