<?php
namespace TYPO3\Flow\I18n\TranslationProvider\Exception;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * An "Unsupported Translation Method" exception
 *
 * This exception can be thrown by a concrete class implementing
 * \TYPO3\Flow\I18n\TranslationProvider\TranslationProviderInterface when one
 * of the interface methods is not supported (eg. when storage method makes it
 * impossible).
 *
 * @api
 */
class UnsupportedTranslationMethodException extends \TYPO3\Flow\I18n\Exception
{
}
