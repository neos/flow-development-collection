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
 * Marks the annotate class as a value object.
 *
 * Regarding Doctrine the object is treated like an entity, but Flow
 * applies some optimizations internally, e.g. to store only one instance
 * of a value object.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class ValueObject
{
    /**
     * Whether the value object should be embedded.
     * @var boolean
     */
    public $embedded = false;
}
