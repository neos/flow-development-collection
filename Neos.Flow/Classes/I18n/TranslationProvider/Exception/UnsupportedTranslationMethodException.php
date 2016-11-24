<?php
namespace Neos\Flow\I18n\TranslationProvider\Exception;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\I18n;

/**
 * An "Unsupported Translation Method" exception
 *
 * This exception can be thrown by a concrete class implementing
 * \Neos\Flow\I18n\TranslationProvider\TranslationProviderInterface when one
 * of the interface methods is not supported (eg. when storage method makes it
 * impossible).
 *
 * @api
 */
class UnsupportedTranslationMethodException extends I18n\Exception
{
}
