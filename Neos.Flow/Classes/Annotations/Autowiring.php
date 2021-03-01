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
 * Used to disable autowiring for Dependency Injection on the
 * whole class or on the annotated property only.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"METHOD", "CLASS"})
 */
#[\Attribute(\Attribute::TARGET_METHOD|\Attribute::TARGET_CLASS)]
final class Autowiring
{
    /**
     * Whether autowiring is enabled. (Can be given as anonymous argument.)
     * @var boolean
     */
    public $enabled = true;

    public function __construct(bool $enabled = true)
    {
        $this->enabled = $enabled;
    }
}
