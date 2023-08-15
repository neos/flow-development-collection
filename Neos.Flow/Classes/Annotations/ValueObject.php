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

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Marks the annotate class as a value object.
 *
 * The schema will be embedded into parent entities by default, unless "embedded=false" is specified.
 * In that case, regarding Doctrine the object is treated like an entity, but Flow
 * applies some optimizations internally, e.g. to store only one instance
 * of the value object.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class ValueObject
{
    /**
     * Whether the value object should be embedded.
     * @var boolean
     */
    public $embedded = true;

    public function __construct(bool $embedded = true)
    {
        $this->embedded = $embedded;
    }
}
