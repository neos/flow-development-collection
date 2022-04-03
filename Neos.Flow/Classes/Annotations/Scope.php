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
 * Used to set the scope of an object.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Scope
{
    /**
     * The scope of an object: prototype, singleton, session. (Usually given as anonymous argument.)
     * @var string
     */
    public $value = 'prototype';

    public function __construct(?string $value = null)
    {
        if ($value !== null) {
            $this->value = $value;
        }
    }
}
