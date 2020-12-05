<?php
namespace Neos\Flow\Reflection\Exception;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A "Class loading for reflection failed" exception
 *
 * @api
 */
class ClassLoadingForReflectionFailedException extends \Neos\Flow\Reflection\Exception
{
    public static function forClassName(string $className, string $reflectedClass): self
    {
        // @deprecated This check was added with Flow 7.0 to improve the developer experience for upgrading components and can be removed later
        if ($className === \Neos\Flow\Http\Component\ComponentInterface::class) {
            $message = sprintf('The class "%s" still implements the ComponentInterface. The component chain was replaced with a middleware chain in Flow 7. Please make sure you have read the upgrade instructions and converted your components to middlewares.', $reflectedClass);
        } else {
            $message = sprintf('Required class "%s" could not be loaded properly for reflection from "%s".%3$s%3$sPossible reasons are:%3$s%3$s * Requiring non-existent classes%3$s * Using non-supported annotations%3$s * Class-/filename missmatch.%3$s%3$sThe "Neos.Flow.object.excludeClasses" setting can be used to skip classes from being reflected.', $className, $reflectedClass, chr(10));
        }
        return new self($message);
    }
}
