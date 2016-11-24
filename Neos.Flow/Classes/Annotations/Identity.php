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
 * Marks a property as being (part of) the identity of an object.
 *
 * If multiple properties are annotated as Identity, a compound
 * identity is created.
 *
 * For Doctrine a unique key over all involved properties will be
 * created - thus the limitations of that need to be observed.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Identity
{
}
