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
    protected $className;

    public static function forClassName(string $className): self
    {
        $exception = new self(sprintf('Required class "%s" could not be loaded properly for reflection.%2$s%2$sPossible reasons are:%2$s%2$s * Requiring non-existent classes%2$s * Using non-supported annotations%2$s * Class-/filename missmatch.%2$s%2$sThe "Neos.Flow.object.excludeClasses" setting can be used to skip classes from being reflected.', $className, chr(10)));
        $exception->className = $className;
        return $exception;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }
}
