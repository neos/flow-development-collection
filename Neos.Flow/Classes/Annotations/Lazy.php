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
 * Marks a property or class as lazy-loaded.
 *
 * This is only relevant for anything based on the generic persistence
 * layer of Flow. For Doctrine based persistence this is ignored.
 *
 * @Annotation
 * @Target({"CLASS", "PROPERTY"})
 */
final class Lazy
{
}
