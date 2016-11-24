<?php
namespace Neos\Flow\Annotations;

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
 * Marks a property as transient - it will never be considered by the
 * persistence layer for storage and retrieval.
 *
 * Useful for calculated values and any other properties only needed
 * during runtime.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Transient
{
}
